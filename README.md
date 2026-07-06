# ExperientIA · WebPRO

Sitio web oficial de **ExperientIA** — *Automatización · Growth · IA*.

Construido según la **Especificación Web V2 (Julio 2026)** y el **Manual de Identidad Visual 2026**: dark premium nativo, ejecutivo, tecnológico, con la paleta oficial midnight blue + cian eléctrico + violeta.

## Stack

- [Astro](https://astro.build) — sitio 100% estático, performance first (cero JS de framework; solo scripts ligeros de motion y menú).
- [Inter Variable](https://rsms.me/inter/) self-hosted vía Fontsource.
- CSS puro con design tokens de marca (sin frameworks).

## Comandos

```bash
npm install     # instalar dependencias
npm run dev     # servidor de desarrollo en http://localhost:4321
npm run build   # build de producción en ./dist
npm run preview # previsualizar el build
```

## Arquitectura

| Ruta | Contenido |
| --- | --- |
| `/` | Redirección por idioma del navegador (ES por defecto) |
| `/es/` `/en/` `/pt/` | Home por idioma |
| `/{lang}/{slug}` | Soluciones, Tablero de Crecimiento, Productos, Casos, Recursos, Nosotros, Contacto (slugs localizados) |

- **i18n:** diccionarios completos en `src/i18n/{es,en,pt}.js`. Todo el contenido editable vive ahí.
- **Design system:** tokens y utilidades en `src/styles/global.css` (paleta oficial §4 de la especificación).
- **Marca:** `src/components/BrandSymbol.astro` y `BrandLogo.astro` (variantes gradiente / blanco / negro).
- **Motion:** reveals con IntersectionObserver, glows y pulsos suaves; respeta `prefers-reduced-motion` y funciona sin JavaScript.

## Pendientes de configuración (TODO)

1. **Dominio de producción:** reemplazar `SITE_URL` en `astro.config.mjs` (hoy `experientia.example.com`) — afecta canonical, hreflang y Open Graph.
2. **Correo de contacto:** reemplazar `CONTACT_EMAIL` en `src/i18n/index.js` (hoy `contacto@experientia.com`).
3. **Logos oficiales:** el símbolo y el lockup son una recreación vectorial fiel hecha a partir del manual. Cuando existan los exports SVG oficiales, sustituir la geometría en `BrandSymbol.astro` y `public/favicon.svg`.
4. **Métricas de casos:** las cifras de la sección Casos son representativas (marcadas como anonimizadas). Sustituir por datos reales de clientes cuando estén disponibles.
5. **Formulario de contacto:** hoy compone un `mailto:`; conectar a un backend o servicio de formularios cuando se defina.
6. **Analytics:** integrar la herramienta de analítica cuando se defina la propiedad (analytics first).
