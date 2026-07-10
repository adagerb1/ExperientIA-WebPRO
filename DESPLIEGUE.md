# Guía de despliegue · ExperientIA (Zero-Config)

Plataforma **sin paso de build**: los archivos se suben tal cual (FTP / cPanel File
Manager / Git). No hay `npm run build`, ni Node.js, ni Webpack/Vite. Vue 3 y Vue
Router se cargan por `importmap` desde `public/assets/vendor/` (ya versionados en el
repo). El backend es PHP 8 (API REST, solo JSON) con MySQL vía PDO.

## Requisitos del servidor

| Requisito | Mínimo |
|-----------|--------|
| PHP | 8.0+ (recomendado 8.2+) con `pdo_mysql`, `mbstring`, `json`, `curl` |
| MySQL / MariaDB | 5.7+ / 10.4+ |
| Apache | con `mod_rewrite` (el `.htaccess` ya viene incluido) |
| HTTPS | certificado activo (el `.htaccess` fuerza redirección a HTTPS) |

> No requiere Node.js en el servidor. SQLite solo se usa para desarrollo local.

## 1. Subir los archivos

Sube **todo el repositorio** al servidor. El *document root* del dominio debe
apuntar a la carpeta **`public/`**.

- **cPanel con dominio principal:** normalmente el root es `public_html/`. Opción A
  (recomendada): coloca el proyecto fuera de `public_html` (p. ej. en
  `~/experientia/`) y apunta el *Document Root* del dominio a
  `~/experientia/public`. Opción B: sube el contenido de `public/` dentro de
  `public_html/` y el resto del proyecto (`app/`, `lang/`, `storage/`, `.env`,
  `install.php`) en el nivel superior `~/experientia/`, ajustando en tal caso las
  rutas `dirname(__DIR__)` no es necesario tocarlas si mantienes la estructura
  `public/` + carpetas hermanas.

La estructura desplegada debe conservar esta jerarquía:

```
experientia/
├── app/              # backend PHP (no accesible por web)
├── lang/             # diccionarios de idioma fuente
├── storage/          # logs y BD sqlite de desarrollo (no accesible por web)
├── public/           # ← DOCUMENT ROOT del dominio
│   ├── index.php     # shell SPA con meta SEO por ruta
│   ├── api.php       # front controller de la API REST
│   ├── sitemap.php   # sitemap dinámico (servido en /sitemap.xml)
│   ├── robots.txt
│   ├── .htaccess
│   └── assets/       # css, js, vendor (Vue), fonts, img
├── .env              # ← crear a partir de .env.example (NO se sube al repo)
└── install.php       # ejecutar una vez, luego BORRAR
```

## 2. Crear la base de datos

En cPanel → *MySQL Databases*: crea una base de datos y un usuario, y asígnale
todos los permisos sobre ella. Anota `nombre_bd`, `usuario`, `contraseña` y `host`
(normalmente `localhost`).

## 3. Configurar `.env`

Copia `.env.example` a `.env` y complétalo:

```bash
cp .env.example .env
```

Claves imprescindibles:

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

Los conectores (OpenAI/AlexIA, SendGrid, Telegram, WhatsApp, pasarelas de pago,
Google Calendar) pueden dejarse vacíos aquí y configurarse después desde el panel
admin (**Plataforma → Conectores**). El `.env` es solo el *fallback*.

> El `.htaccess` bloquea el acceso web a `.env`, `.sqlite` y `.log`.

## 4. Instalar (una sola vez)

Desde el *Terminal* de cPanel o por SSH, en la raíz del proyecto:

```bash
php install.php "Tu Nombre" hello@experientia.pro "TuContraseñaSegura"
```

Esto crea las tablas, siembra el contenido inicial (soluciones, productos, casos,
FAQs, recursos, disponibilidad), registra los conectores y plantillas de email, y
crea el usuario **propietario** del portal.

**Borra `install.php` del servidor** cuando termine.

## 5. Verificar

- Sitio público: `https://experientia.pro/` → redirige a `/es/`.
- Idiomas: `/es/`, `/en/`, `/pt/`.
- API salud: `https://experientia.pro/api/content` → responde JSON.
- SEO: `https://experientia.pro/sitemap.xml` y `/robots.txt`.
- Portal admin: `https://experientia.pro/admin` → login con el propietario creado.

## 6. Actualizaciones posteriores

Como no hay build, actualizar es sustituir archivos (FTP / `git pull`). Los cambios
en `.js`, `.css`, `.html` son inmediatos. Si cambia el esquema, aplica las
migraciones correspondientes en la BD. Si actualizas Vue, reemplaza los archivos en
`public/assets/vendor/` (no hay CDN externo en producción).

## Notas de seguridad

- CORS restringido a los orígenes de `CORS_ALLOWED_ORIGINS`.
- Rate limiting por IP (público) y por usuario (autenticado) — configurable en `.env`.
- Toda entrada de formularios se sanitiza (anti SQL injection vía PDO + validación).
- Autenticación Bearer por endpoint en el panel; `/admin` va con `noindex`.
