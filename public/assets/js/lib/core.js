// ExperientIA · Núcleo del frontend (Vue 3). Cliente API, i18n, store, toasts.
import { reactive } from 'vue';

const LOCALES = ['es', 'en', 'pt'];
function detectLocale() {
  const seg = location.pathname.split('/').filter(Boolean)[0];
  if (LOCALES.includes(seg)) return seg;
  const nav = (navigator.language || 'es').slice(0, 2);
  return LOCALES.includes(nav) ? nav : 'es';
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

// ---------- Cliente API ----------
let CSRF = null;
async function apiFetch(method, path, body, opts = {}) {
  const headers = { 'Accept': 'application/json' };
  if (store.token) headers['Authorization'] = 'Bearer ' + store.token;
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
export function setMeta(title, desc) {
  document.title = title;
  let m = document.querySelector('meta[name=description]');
  if (m && desc) m.setAttribute('content', desc);
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
