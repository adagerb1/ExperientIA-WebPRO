# ExperientIA · WebPRO

Plataforma digital de **ExperientIA** — *Automatización · Growth · IA*.
**PHP puro (sin frameworks) + MySQL + frontend que consume API JSON.**

## Estructura del repositorio

| Carpeta | Qué es |
| --- | --- |
| **`sitio/`** | **La plataforma**: sitio público trilingüe (ES/EN/PT) renderizado en PHP + API JSON (`/api/*`) + portal admin (`/admin`) hecho en JavaScript sobre la API. |
| Raíz (`src/`, `public/`, `astro.config.mjs`) | Versión estática original (Astro), conservada solo como referencia de diseño. |

## Arquitectura (`sitio/`)

```
sitio/
├── public/            ← document root (index.php enruta todo)
│   └── assets/        ← CSS/JS/fuentes/librerías, todo estático
├── app/               ← núcleo PHP: config, PDO, helpers, correo, lógica
│   └── controllers/   ← páginas públicas, API pública, API admin, portal
├── templates/         ← plantillas PHP del sitio público (SEO server-side)
├── lang/              ← diccionarios ES/EN/PT
├── config/            ← diagnóstico (preguntas/scoring), países
├── datos/             ← SQLite dev, PDFs descargables, semillas
└── instalar.php       ← instalador de un solo uso (tablas + contenido + admin)
```

- **Sitio público**: PHP renderiza el HTML (SEO: hreflang, sitemap, schema
  Organization + FAQPage); todo lo dinámico va por `fetch()` a la API.
- **API pública**: `POST /api/contacto|newsletter|descarga|diagnostico|reserva`,
  `GET /api/slots` — validación server-side, CSRF, honeypot, rate limiting.
- **CRM**: leads deduplicados por correo/WhatsApp con historial de touchpoints,
  pipeline de estados, notas y notificación por correo en cada interacción.
- **Portal admin** (`/admin`): SPA vanilla JS sobre `/api/admin/*` — leads,
  reservas, disponibilidad y CRUD trilingüe de todo el contenido (fallback a ES).
- **Diagnóstico**: wizard de 8 preguntas → solución recomendada + lead calificado.
- **Agenda 1:1**: franjas configurables, horarios en la zona del visitante,
  confirmación por correo.
- **Formularios**: país/industria con combobox buscable, teléfono WhatsApp con
  bandera e indicativo (se muestra `+57`, se guarda `57300…` para `wa.me`),
  rango de empleados.

## Desarrollo local

```bash
cd sitio
# config.local.php con driver sqlite (ver DESPLIEGUE.md)
php instalar.php "Admin" admin@experientia.pro "clave"
php -S localhost:8091 -t public
```

## Despliegue

Ver **`sitio/DESPLIEGUE.md`** — subir, apuntar document root, ejecutar
`instalar.php` una vez. Sin Composer, sin npm, sin build.
