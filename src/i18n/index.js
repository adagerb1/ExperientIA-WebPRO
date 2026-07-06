import es from './es.js';
import en from './en.js';
import pt from './pt.js';

export const LANGS = ['es', 'en', 'pt'];
export const DEFAULT_LANG = 'es';
export const DICTS = { es, en, pt };

/** Correo de contacto oficial. TODO: reemplazar por la casilla definitiva. */
export const CONTACT_EMAIL = 'contacto@experientia.com';
export const TONNY_DAGER_URL = 'https://tonnydager.com';

export function getDict(lang) {
  return DICTS[lang] ?? DICTS[DEFAULT_LANG];
}

/** Claves internas de página (independientes del idioma). */
export const PAGE_KEYS = ['soluciones', 'tablero', 'productos', 'casos', 'recursos', 'nosotros', 'contacto'];

/** URL absoluta-relativa de una página en un idioma dado. */
export function pagePath(lang, pageKey) {
  const dict = getDict(lang);
  if (!pageKey || pageKey === 'home') return `/${lang}/`;
  return `/${lang}/${dict.slugs[pageKey]}/`;
}

/** Dado un slug localizado, devuelve la clave interna de página. */
export function keyFromSlug(lang, slug) {
  const dict = getDict(lang);
  return PAGE_KEYS.find((k) => dict.slugs[k] === slug) ?? null;
}
