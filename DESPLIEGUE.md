# Guía de despliegue · ExperientIA (Zero-Config, todo en `public_html`)

Se descarga el repositorio, se sube el ZIP al hosting, **se descomprime dentro de
`public_html` y funciona**. No hay que cambiar el *document root* del dominio, no
hay paso de compilación (`npm`, Webpack, Vite) ni Node.js en el servidor.

- **Frontend** 100 % estático (HTML + CSS + JS). Vue 3 y Vue Router se cargan por
  `importmap` desde `assets/vendor/` (ya versionados en el repo). Ninguna vista
  depende de PHP.
- **Backend** 100 % API REST en PHP 8 (solo JSON, nunca renderiza HTML), bajo
  `api/`. El mismo backend sirve al sitio, al portal admin, a Telegram/WhatsApp,
  a AlexIA y a cualquier app móvil o servicio externo.
- **Datos** MySQL vía PDO (SQLite solo en desarrollo local).

## Requisitos del servidor

| Requisito | Mínimo |
|-----------|--------|
| PHP | 8.0+ (recomendado 8.2+) con `pdo_mysql`, `mbstring`, `json`, `curl` |
| MySQL / MariaDB | 5.7+ / 10.4+ (se administra con phpMyAdmin) |
| Apache | con `mod_rewrite` (los `.htaccess` ya vienen incluidos) |
| HTTPS | certificado activo |

## Estructura una vez descomprimido en `public_html`

```
public_html/                 ← aquí se descomprime el ZIP (document root del dominio)
├── index.html               # Shell del sitio público (SPA, sin PHP)
├── admin/
│   └── index.html           # Shell del portal admin (SPA, sin PHP)
├── assets/                  # css, js, vendor (Vue), fonts, img  ← FRONTEND
├── api/                     # BACKEND: API REST JSON (única parte en PHP)
│   ├── index.php            #   front controller de /api/*
│   ├── sitemap.php          #   /sitemap.xml
│   ├── .htaccess            #   blinda el resto de api/ (Core, db, config…)
│   ├── Core/ Controllers/ Services/ Models/ config/ db/
│   ├── bootstrap.php · helpers.php
├── storage/                 # logs, uploads, recursos (no accesible por web)
├── .htaccess                # ruteo: /api → backend, resto → SPA
├── .env                     # ← crear a partir de .env.example
├── favicon.svg · robots.txt
└── install.php              # ejecutar una vez y BORRAR
```

> Los `.htaccess` bloquean el acceso web a `.env`, `.sqlite`, `.log`, `.sql` y a
> todo el interior de `api/` salvo `index.php` y `sitemap.php`. Aunque el backend
> vive dentro de `public_html`, no es accesible ni descargable.

## Pasos

### 1. Subir y descomprimir
Sube el ZIP del repositorio a `public_html` (cPanel → *File Manager* → *Upload*) y
**Extract** ahí mismo. No muevas nada ni cambies el *document root*.

### 2. Crear la base de datos
cPanel → *MySQL Databases*: crea base de datos y usuario, asígnale todos los
permisos. Anota `nombre_bd`, `usuario`, `contraseña` y `host` (normalmente
`localhost`). La administras con **phpMyAdmin**.

### 3. Configurar `.env`
Copia `.env.example` a `.env` (File Manager → *Copy* / *Rename*) y edítalo:

```ini
APP_ENV=production
APP_URL=https://experientia.pro
CORS_ALLOWED_ORIGINS=https://experientia.pro,https://www.experientia.pro
APP_SECRET=<genera con: php -r "echo bin2hex(random_bytes(32));">
DB_DRIVER=mysql
DB_HOST=localhost
DB_NAME=nombre_bd
DB_USER=usuario
DB_PASS=contraseña
```

Los conectores (OpenAI/AlexIA, SendGrid, Telegram, WhatsApp, pasarelas, Google
Calendar) pueden quedar vacíos y configurarse luego desde el panel
(**Plataforma → Conectores**). El `.env` es solo el *fallback*.

### 4. Instalar (una sola vez) — asistente en el navegador
Abre **`https://experientia.pro/install.php`** en el navegador. El asistente
(wizard) comprueba los requisitos del servidor y te pide en un solo paso: datos de
la base de datos, URL del sitio y el usuario administrador. Al enviar, **escribe el
`.env` automáticamente**, crea las tablas, siembra el contenido inicial, registra
conectores y plantillas, y crea el usuario **propietario** del portal.

Si prefieres consola (opcional), desde *Terminal* de cPanel o SSH en `public_html`:

```bash
php install.php "Tu Nombre" hello@experientia.pro "TuContraseñaSegura"
```

En cualquiera de los dos casos, **borra `install.php`** al terminar (el propio
asistente te lo recuerda; si ya hay una instalación activa, se bloquea solo).

### 5. Verificar
- Sitio: `https://experientia.pro/` → redirige a `/es/` (idiomas `/es/ /en/ /pt/`).
- API: `https://experientia.pro/api/content/soluciones` → responde JSON.
- SEO: `https://experientia.pro/sitemap.xml` y `/robots.txt`.
- Portal: `https://experientia.pro/admin` → login con el propietario.

## Actualizaciones
Como no hay build, actualizar es reemplazar archivos (FTP / File Manager / `git
pull`). Los cambios en `.html`, `.js`, `.css` son inmediatos. Para actualizar Vue,
reemplaza los archivos de `assets/vendor/` (no hay CDN externo).

## Consumir el backend desde otros canales
El backend es una API JSON estándar. Una app móvil (iOS/Android), un bot o
cualquier servicio consume los mismos endpoints `https://experientia.pro/api/*`
con **Bearer Token** — sin duplicar lógica ni crear otro backend.

## Automatizaciones (nurturing) — cron opcional

Las secuencias de nurturing (Admin → Automatizaciones) envían sus mensajes cuando
vence el retraso de cada paso. Para que corran solas, añade un **cron job** en cPanel:

```
*/15 * * * * curl -s "https://TU-DOMINIO/api/cron/run?key=TU_CRON_KEY" >/dev/null 2>&1
```

- Define `CRON_KEY` en tu `.env` (si no la defines, se usa `APP_SECRET`).
- Sin cron, igual puedes disparar los envíos con el botón **“Procesar ahora”** del panel.
- Requiere un método de envío configurado: **SendGrid** (correo) o **WhatsApp Business**.
- La secuencia de ejemplo se instala **pausada**; actívala cuando quieras que envíe.
