// ExperientIA · Núcleo del frontend (Vue 3). Cliente API, i18n, store, toasts.
import { reactive } from 'vue';

const LOCALES = ['es', 'en', 'pt'];
function detectLocale() {
  // 1) el idioma explícito de la URL manda
  const seg = location.pathname.split('/').filter(Boolean)[0];
  if (LOCALES.includes(seg)) return seg;
  // 2) elección previa del visitante
  try { const s = localStorage.getItem('exp_locale'); if (LOCALES.includes(s)) return s; } catch (e) {}
  // 3) idioma(s) del navegador
  const langs = (navigator.languages && navigator.languages.length) ? navigator.languages : [navigator.language || 'es'];
  for (const l of langs) { const base = (l || '').slice(0, 2).toLowerCase(); if (LOCALES.includes(base)) return base; }
  return 'es';
}

// País probable del visitante a partir de la región del idioma del navegador
// (es-CO → CO). Sin llamadas externas. Fallback por idioma (LATAM/Brasil/US).
export function detectCountry() {
  const langs = (navigator.languages && navigator.languages.length) ? navigator.languages : [navigator.language || ''];
  for (const l of langs) { const m = /[-_]([A-Za-z]{2})$/.exec(l || ''); if (m) return m[1].toUpperCase(); }
  const base = (navigator.language || 'es').slice(0, 2).toLowerCase();
  return ({ es: 'CO', pt: 'BR', en: 'US' })[base] || 'CO';
}

export const store = reactive({
  locale: detectLocale(),
  dict: {},
  token: localStorage.getItem('exp_token') || null,
  admin: JSON.parse(localStorage.getItem('exp_admin') || 'null'),
  toasts: [],
});

const dictCache = {};
export async function loadDict(locale) {
  if (dictCache[locale]) { store.dict = dictCache[locale]; return; }
  const r = await fetch(`/assets/js/lib/lang.${locale}.json`);
  dictCache[locale] = await r.json();
  store.dict = dictCache[locale];
}

// Metadatos de formularios (industrias, países…) desde la BD, cacheados.
let metaCache = null, metaPromise = null;
export async function loadMeta() {
  if (metaCache) return metaCache;
  if (!metaPromise) metaPromise = fetch('/api/meta').then(r => r.json()).then(j => (metaCache = (j.ok ? j.data : { industries: [], countries: [] }))).catch(() => ({ industries: [], countries: [] }));
  return metaPromise;
}

// t('home.hero_titulo1') con acceso por puntos; devuelve la clave si falta.
export function t(path) {
  let v = store.dict;
  for (const k of path.split('.')) { v = v && v[k]; if (v === undefined) return path; }
  return v;
}
// tr(campo {es,en,pt}) con fallback a español
export function tr(field) {
  if (field == null) return '';
  if (typeof field !== 'object') return String(field);
  return field[store.locale] || field.es || Object.values(field)[0] || '';
}
export function trLines(field) {
  const v = tr(field);
  return Array.isArray(v) ? v.filter(Boolean) : String(v).split('\n').map(s => s.trim()).filter(Boolean);
}

// URL localizada de una página
export function pageUrl(key, params = {}) {
  if (key === 'home') return `/${store.locale}/`;
  const slug = (store.dict.slugs && store.dict.slugs[key]) || key;
  return `/${store.locale}/${slug}` + (params.slug ? `/${params.slug}` : '');
}

// ---------- Atribución de marketing (first-touch) ----------
// Captura UTM + referrer + landing en el primer clic y la persiste, para que
// cada lead sepa qué campaña/pauta lo trajo. Se adjunta sola a los formularios.
const ATTRIB_KEY = 'exp_attrib';
const CAPTURE_PATHS = ['/interes', '/contacto', '/newsletter', '/diagnostico', '/reserva', '/descarga'];
function captureAttribution() {
  try {
    if (localStorage.getItem(ATTRIB_KEY)) return; // first-touch: no sobrescribir
    const p = new URLSearchParams(location.search);
    const a = {};
    for (const k of ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term']) {
      const v = p.get(k); if (v) a[k] = v.slice(0, 160);
    }
    // Referrer externo (no el propio sitio) y landing de entrada
    if (document.referrer && !document.referrer.includes(location.host)) a.referrer = document.referrer.slice(0, 255);
    a.landing_page = (location.pathname + location.search).slice(0, 255);
    // Solo persistir si hay señal de campaña o referrer (evita guardar navegación interna vacía)
    if (a.utm_source || a.utm_campaign || a.referrer) localStorage.setItem(ATTRIB_KEY, JSON.stringify(a));
  } catch (e) {}
}
export function attribution() {
  try { return JSON.parse(localStorage.getItem(ATTRIB_KEY) || '{}'); } catch (e) { return {}; }
}
captureAttribution();

// ---------- Cliente API ----------
let CSRF = null;
async function apiFetch(method, path, body, opts = {}) {
  const headers = { 'Accept': 'application/json' };
  if (store.token) headers['Authorization'] = 'Bearer ' + store.token;
  // Adjunta atribución a los envíos de formularios públicos (captación de leads).
  if (method === 'POST' && body && !(body instanceof FormData) && CAPTURE_PATHS.includes(path)) {
    const a = attribution();
    if (Object.keys(a).length) body = { ...a, ...body };
  }
  let payload;
  if (body instanceof FormData) { payload = body; }
  else if (body) { headers['Content-Type'] = 'application/json'; payload = JSON.stringify(body); }
  const r = await fetch('/api' + path, { method, headers, body: payload });
  let json = {};
  try { json = await r.json(); } catch (e) {}
  if (r.status === 401 && store.token) { logout(); }
  return { status: r.status, ok: json.ok === true, ...json };
}
export const api = {
  get: (p) => apiFetch('GET', p),
  post: (p, b) => apiFetch('POST', p, b),
  put: (p, b) => apiFetch('PUT', p, b),
  patch: (p, b) => apiFetch('PATCH', p, b),
  del: (p) => apiFetch('DELETE', p),
  upload: (p, formData) => apiFetch('POST', p, formData),
};

// ---------- Auth ----------
export function setAuth(token, admin) {
  store.token = token; store.admin = admin;
  localStorage.setItem('exp_token', token);
  localStorage.setItem('exp_admin', JSON.stringify(admin));
}
export function logout() {
  store.token = null; store.admin = null;
  localStorage.removeItem('exp_token'); localStorage.removeItem('exp_admin');
  if (location.pathname.startsWith('/admin') && location.pathname !== '/admin/login') {
    location.href = '/admin/login';
  }
}

// ---------- Toasts ----------
let toastId = 0;
export function toast(message, type = 'ok') {
  const id = ++toastId;
  store.toasts.push({ id, message, type });
  setTimeout(() => { store.toasts = store.toasts.filter(x => x.id !== id); }, 4000);
}

// ---------- Meta por ruta (SEO en cliente) ----------
const ORIGIN = 'https://experientia.pro';
const OG_LOCALE = { es: 'es_ES', en: 'en_US', pt: 'pt_BR' };

function upsertMeta(sel, attr, key, val) {
  let el = document.head.querySelector(sel);
  if (!el) { el = document.createElement('meta'); el.setAttribute(attr, key); document.head.appendChild(el); }
  el.setAttribute('content', val);
}
function upsertLink(rel, hreflang, href) {
  const q = hreflang ? `link[rel="${rel}"][hreflang="${hreflang}"]` : `link[rel="${rel}"]:not([hreflang])`;
  let el = document.head.querySelector(q);
  if (!el) { el = document.createElement('link'); el.setAttribute('rel', rel); if (hreflang) el.setAttribute('hreflang', hreflang); document.head.appendChild(el); }
  el.setAttribute('href', href);
}

export function setMeta(title, desc) {
  document.title = title;
  if (desc) upsertMeta('meta[name=description]', 'name', 'description', desc);

  const path = location.pathname;
  const canonical = ORIGIN + path;
  upsertLink('canonical', null, canonical);
  // Alternantes hreflang (misma ruta cambiando el prefijo de idioma)
  for (const l of ['es', 'en', 'pt']) {
    upsertLink('alternate', l, ORIGIN + path.replace(/^\/(es|en|pt)/, '/' + l));
  }
  upsertLink('alternate', 'x-default', ORIGIN + path.replace(/^\/(es|en|pt)/, '/es'));
  // Open Graph
  upsertMeta('meta[property="og:title"]', 'property', 'og:title', title);
  if (desc) upsertMeta('meta[property="og:description"]', 'property', 'og:description', desc);
  upsertMeta('meta[property="og:url"]', 'property', 'og:url', canonical);
  upsertMeta('meta[property="og:locale"]', 'property', 'og:locale', OG_LOCALE[store.locale] || 'es_ES');
  document.documentElement.lang = store.locale;
}

// ---------- CSV export (; + BOM UTF-8, Framework §3.D) ----------
export function exportCSV(filename, rows, headers) {
  const cols = headers || Object.keys(rows[0] || {});
  const esc = (v) => '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"';
  const lines = [cols.map(esc).join(';')];
  for (const row of rows) lines.push(cols.map(c => esc(row[c])).join(';'));
  const blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob); a.download = filename; a.click();
  URL.revokeObjectURL(a.href);
}
