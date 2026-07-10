# ExperientIA · Plataforma web + Portal admin

Sitio institucional y CRM/portal de operación de **ExperientIA SAS**
(*Automatización · Growth · IA*). Construido siguiendo el **Framework de Desarrollo
ExperientIA v1.0**: desacoplamiento estricto con **enfoque No-Build**.

- **Sin paso de compilación.** Vue 3 y Vue Router se cargan por `importmap` desde
  `public/assets/vendor/` (versionados en el repo). Nada de Node.js, Webpack o Vite
  en producción.
- **Backend solo-JSON.** PHP 8 orientado a objetos, API RESTful, MVC simplificado
  (Router → Controllers → Middlewares). El servidor **nunca** renderiza HTML de la
  app (excepto el shell SEO de `index.php`).
- **MySQL + PDO** con *prepared statements* e integridad referencial
  (`ON DELETE CASCADE / SET NULL`).

Estética **dark premium**: Glassmorphism + Liquid Glass + UI Espacial sobre la
identidad de marca (midnight blue `#051126` + cian eléctrico `#18D6F1` + violeta
`#7A63FF`). Multilenguaje **ES / EN / PT** con *fallback* a ES. Mobile-first,
AI-first, performance-first.

## Arquitectura

Frontend, backend y datos **totalmente separados**. Todo vive dentro de
`public_html` (se descomprime ahí y funciona, sin cambiar el document root). Las
vistas son HTML/CSS/JS puro; el backend es una API JSON que **nunca renderiza
HTML** y sirve por igual al sitio, al portal, a Telegram/WhatsApp, a AlexIA y a
apps móviles.

```
public_html/  (= raíz del repo)
├── index.html               # FRONTEND · shell del sitio público (SPA, sin PHP)
├── admin/index.html         # FRONTEND · shell del portal admin (SPA, sin PHP)
├── assets/                  # FRONTEND
│   ├── css/                 #   tokens.css, app.css (público), admin.css (portal)
│   ├── js/
│   │   ├── lib/             #   core (store/api/i18n/SEO/CSV), ui, forms, layout, lang.*.json
│   │   ├── views/           #   páginas públicas (pages1-4.js)
│   │   ├── admin/           #   portal (main, views1, views2)
│   │   └── main.js          #   bootstrap SPA público
│   ├── vendor/              #   vue.esm-browser.prod.js + vue-router.esm-browser.prod.js
│   └── fonts/ · img/
├── api/                     # BACKEND · API REST JSON (única parte en PHP)
│   ├── index.php            #   front controller de /api/*
│   ├── sitemap.php          #   /sitemap.xml (endpoint de datos)
│   ├── bootstrap.php        #   autoload PSR-4, .env, error handler
│   ├── helpers.php · .htaccess (blinda el resto)
│   ├── Core/                #   Router, Controller (bulletproof), Database (PDO),
│   │   │                    #     Token (Bearer), Validator, Cors, RateLimiter, Env
│   │   └── Middleware/      #   AuthMiddleware (RBAC por rol)
│   ├── Controllers/
│   │   ├── PublicApi/       #   Content, Lead, Resource, Diagnostic, Booking, Chat, Webhook
│   │   └── Admin/           #   Auth, Dashboard, Leads, Bookings, Content, Connectors, Chat
│   ├── Services/            #   LeadService (dedupe), BookingService, Mailer, AlexIA
│   │   └── Connectors/      #   OpenAI, SendGrid, Telegram, WhatsApp, Payment, GoogleCalendar
│   ├── config/ · Models/
│   └── db/                  #   schema.sql, semillas.php, seed_extra.php
├── storage/                 # DATOS · logs, uploads, recursos (no accesible por web)
├── .htaccess                # ruteo: /api → backend, /admin → admin, resto → SPA
├── .env                     # config (protegido por .htaccess)
├── favicon.svg · robots.txt
└── install.php              # instalador de un solo uso (borrar tras usar)
```

**SEO en SPA estática:** cada ruta actualiza `title`, `description`, `canonical`,
`hreflang`, Open Graph y `html lang` desde el cliente (`setMeta` en `core.js`);
`sitemap.xml` (con slugs traducidos por idioma) y `robots.txt` completan el SEO
técnico. Sin PHP en las vistas.

### Seguridad (aplicada de fábrica)

- **CORS** restringido a `CORS_ALLOWED_ORIGINS` (sitio + admin); orígenes ajenos → 403.
- **Auth Bearer por endpoint** (token tipo JWT HS256 firmado con `APP_SECRET`), RBAC por rol.
- **Rate limiting** por IP (público) y por usuario (autenticado), configurable en `.env`.
- **Sanitización** de toda entrada (Validator + `strip_tags` + charset) y **PDO
  prepared statements** contra SQL injection; honeypot anti-bots en formularios.
- **Backend Bulletproof:** cada método de controlador va en `try/catch(\Throwable)` →
  JSON 500 con `request_id` y log. Sin errores silenciosos.
- **Paginación** en listados; `.env`, `.sqlite`, `.log` bloqueados por `.htaccess`.

### Patrones del Framework

- **Short Polling** en el dashboard (refresco cada 20 s con `setInterval` en
  `onMounted`, destruido en `onUnmounted`).
- **Actionable UI:** las métricas del dashboard son botones que abren el *quirófano
  de datos* (modales operativos).
- **Exportación CSV nativa** (separador `;`, encoding `﻿` + UTF-8 para Excel ES).

## Endpoints principales

**Público** (`/api`): `GET /content/{seccion}`, `GET /slots`, `POST /contacto`,
`POST /newsletter`, `POST /descarga`, `POST /diagnostico`, `POST /reserva`,
`POST /alexia`, webhooks `telegram|whatsapp|pago`.

**Admin** (Bearer): `POST /admin/login`, `GET /admin/resumen`, `GET|PATCH|DELETE
/admin/leads[...]`, `GET /admin/leads-export`, `GET|PATCH /admin/reservas`, CRUD
`/admin/{tabla}` (soluciones, productos, casos, faqs, recursos, disponibilidad),
`GET|PUT|POST /admin/connectors[...]`, `/admin/email-templates`, `POST /admin/alexia`.

## Conectores (Plataforma → Conectores en el panel)

| Conector | Uso | Estado |
|----------|-----|--------|
| **OpenAI / AlexIA** | Agente (Responses API): texto/imagen/audio; comercial (web/WhatsApp) e interno (admin) | Operativo con API key |
| **SendGrid** | Email transaccional + plantillas por idioma | Operativo con API key |
| **Telegram** | 2 bots: comercial (capta leads al CRM, público) e interno (admin + AlexIA) | Operativo con tokens |
| **WhatsApp Business** | AlexIA comercial (Cloud API) | Adaptador + config (requiere credenciales) |
| **Pagos** | Wompi · ePayco · PayPal · Stripe | Adaptadores + webhooks (requiere credenciales) |
| **Google Calendar** | Agendamiento de reservas | Adaptador + config (requiere OAuth) |

Cada conector se configura, activa/desactiva y **prueba** desde el panel. El `.env`
es solo *fallback* de las credenciales.

## Desarrollo local

```bash
cp .env.example .env      # DB_DRIVER=sqlite para desarrollo sin MySQL
php install.php "Admin" hello@experientia.pro "ExperientIA2026!"
php -S localhost:8092 router-dev.php
```

Abrir `http://localhost:8092/` (público) y `http://localhost:8092/admin` (portal).

## Despliegue

Ver **[DESPLIEGUE.md](DESPLIEGUE.md)** — despliegue *zero-config*: descomprimir en
`public_html` y abrir `/install.php` en el navegador (**asistente/wizard** que
comprueba requisitos, escribe el `.env`, crea las tablas, siembra el contenido y
crea el administrador). Sin build, sin npm. Borrar `install.php` al terminar.
