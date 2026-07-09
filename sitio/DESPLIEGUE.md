# Despliegue en cPanel — Plataforma ExperientIA (PHP puro)

Sin frameworks, sin Composer, sin npm: se sube la carpeta y funciona.

## Requisitos
- PHP **8.1 o superior** (cPanel → *MultiPHP Manager*).
- Extensiones habituales ya incluidas en cPanel: `pdo_mysql`, `mbstring`, `json`.
- Una base de datos MySQL.

## Paso 1 · Base de datos
1. cPanel → **MySQL® Databases** → crear base de datos y usuario con todos los privilegios.
2. Anotar nombre de BD, usuario y contraseña.

## Paso 2 · Subir archivos
1. cPanel → **File Manager** → carpeta HOME (`/home/USUARIO/`), *no* `public_html`.
2. Subir `sitio.zip` y extraer. Queda `/home/USUARIO/sitio/`.
3. Crear el archivo `sitio/app/config.local.php` con las credenciales reales:

```php
<?php
return [
    'db' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'name' => 'usuario_experientia',
        'user' => 'usuario_experientia',
        'pass' => 'CONTRASEÑA',
    ],
    'secret' => 'PEGAR-AQUI-UNA-CLAVE-LARGA-ALEATORIA',
];
```

> La clave `secret` firma los enlaces de descarga. Generar una cadena
> aleatoria larga (p. ej. en https://www.random.org/strings/ o con
> `php -r "echo bin2hex(random_bytes(32));"`).

## Paso 3 · Apuntar el dominio a `sitio/public`
cPanel → **Domains** → experientia.pro → **Document Root** → `sitio/public`.
(El `.htaccess` incluido enruta todo y fuerza HTTPS.)

## Paso 4 · Inicializar (una sola vez)
En cPanel → **Terminal**:

```bash
cd ~/sitio
php instalar.php "Su Nombre" hello@experientia.pro "una-contraseña-fuerte"
rm instalar.php        # importante: borrar el instalador
```

Esto crea las tablas, siembra el contenido inicial (soluciones, productos,
casos, FAQs, recursos y horarios de ejemplo) y su usuario del portal.

## Paso 5 · Verificar
- `https://experientia.pro` → sitio en español (EN/PT en el selector).
- `https://experientia.pro/admin` → portal (login del paso 4).
- Enviar el formulario de contacto → aparece en **Leads** y llega correo a hello@.
- `https://experientia.pro/sitemap.xml` → sitemap multilenguaje.

## Operación diaria
- **Leads**: portal → Leads (estado, notas, WhatsApp directo, historial).
- **Agenda**: portal → Disponibilidad (franjas por día). Duración/zona horaria en `app/config.php` → `booking`.
- **Contenido**: portal → Soluciones/Productos/Casos/FAQs/Recursos (pestañas ES/EN/PT; solo ES es obligatorio).
- **Recursos descargables**: subir el PDF desde el propio formulario del recurso.
- **Correos**: salen con `mail()` de cPanel desde hello@experientia.pro. Crear esa casilla en cPanel → Email Accounts y agregar registro SPF/DKIM (cPanel → Email Deliverability) para buena entrega.
- **Copia de seguridad**: base de datos + carpeta `sitio/datos/recursos/`.

## Actualizaciones
Subir y extraer el nuevo zip encima (no toca `config.local.php` ni `datos/`).
Si una actualización trae tablas nuevas, se crean solas en la primera visita.
