# Despliegue en cPanel / WHM — Sistema ExperientIA

Guía para publicar la plataforma (web pública + panel /admin) en un VPS
administrado con WHM/cPanel.

## Requisitos del servidor

| Requisito | Cómo verificarlo en cPanel |
| --- | --- |
| PHP **8.3 o 8.4** | *MultiPHP Manager* → seleccionar la versión para el dominio |
| Extensiones PHP: `pdo_mysql`, `mbstring`, `xml`, `curl`, `intl`, `gd`, `zip` | *Select PHP Version* → Extensions |
| Base de datos MySQL/MariaDB | *MySQL® Databases* |
| Terminal (o acceso SSH) | *Terminal* en cPanel, o SSH desde WHM |

## Paso 1 · Base de datos

1. cPanel → **MySQL® Databases**.
2. Crear base de datos: `usuario_experientia`.
3. Crear usuario con contraseña fuerte y **agregarlo a la base con todos los privilegios**.
4. Anotar: nombre de BD, usuario y contraseña.

## Paso 2 · Subir el sistema

> El paquete `sistema-experientia.zip` ya incluye `vendor/` (dependencias PHP)
> y `public/build/` (CSS/JS compilados): **no hay que ejecutar Composer ni npm
> en el servidor**.

1. cPanel → **File Manager** → ir a la carpeta HOME (`/home/USUARIO/`), *no* a `public_html`.
2. Subir `sistema-experientia.zip` y extraerlo. Queda `/home/USUARIO/sistema/`.
3. Renombrar `.env.production` a `.env` y editarlo (clic derecho → Edit):

```dotenv
APP_URL=https://experientia.pro
DB_DATABASE=usuario_experientia
DB_USERNAME=usuario_experientia
DB_PASSWORD=la-contraseña-del-paso-1

MAIL_HOST=mail.experientia.pro     # servidor de correo del hosting
MAIL_USERNAME=hello@experientia.pro
MAIL_PASSWORD=contraseña-de-la-casilla
```

## Paso 3 · Apuntar el dominio a la carpeta `public/`

La seguridad de Laravel exige que el dominio sirva **solo** la carpeta
`sistema/public`, nunca la carpeta raíz del proyecto.

- cPanel → **Domains** → experientia.pro → **Document Root** →
  cambiarlo a `sistema/public`.
- Si el proveedor no permite editar el document root del dominio principal,
  hacerlo desde WHM, o crear el dominio de nuevo con ese document root.

## Paso 4 · Inicializar (una sola vez, en la Terminal de cPanel)

```bash
cd ~/sistema
php artisan key:generate --force        # genera APP_KEY
php artisan migrate --force             # crea las tablas
php artisan db:seed --class=ContentSeeder --force   # contenido inicial
php artisan make:filament-user          # crea SU usuario del panel /admin
php artisan optimize                    # cachea config y rutas
```

> Si la versión de PHP de la terminal no es 8.3+, usar la ruta completa,
> p. ej. `/opt/cpanel/ea-php84/root/usr/bin/php artisan migrate --force`.

## Paso 5 · Verificar

- `https://experientia.pro` → home en español.
- `https://experientia.pro/admin` → login del panel (usuario del paso 4).
- Enviar el formulario de contacto → debe aparecer en **CRM → Leads**.
- `https://experientia.pro/sitemap.xml` → sitemap con hreflang.

## Actualizaciones posteriores

1. Generar un nuevo zip del proyecto (con `vendor/` y `public/build/`).
2. Subir, extraer encima y ejecutar:

```bash
cd ~/sistema && php artisan migrate --force && php artisan optimize
```

## Notas de operación

- **Correos**: las notificaciones de leads llegan a `EXPERIENTIA_CONTACT_EMAIL`
  (por defecto hello@experientia.pro). Configurar la casilla en cPanel → Email Accounts.
- **Disponibilidad de agenda**: panel → CRM → Disponibilidad (franjas por día).
  La duración de sesión y el horizonte se ajustan en `config/experientia.php`.
- **Copias de seguridad**: incluir la base de datos y `storage/app/private/recursos`
  (archivos descargables subidos desde el panel).
- **Zona horaria de la agenda**: `config/experientia.php` → `booking.timezone`
  (por defecto America/Bogota).
