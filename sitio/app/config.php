<?php
/**
 * Configuración de la plataforma ExperientIA.
 * En producción, crear config.local.php (no versionado) para sobrescribir
 * credenciales de base de datos y correo.
 */

$config = [
    'app' => [
        'url' => 'https://experientia.pro',
        'env' => 'production',           // production | dev
        'locales' => ['es', 'en', 'pt'],
        'default_locale' => 'es',
    ],

    'db' => [
        'driver' => 'mysql',             // mysql | sqlite (solo desarrollo)
        'host' => '127.0.0.1',
        'name' => 'CAMBIAR_base',
        'user' => 'CAMBIAR_usuario',
        'pass' => 'CAMBIAR_contraseña',
        'sqlite_path' => __DIR__ . '/../datos/dev.sqlite',
    ],

    'mail' => [
        'from' => 'hello@experientia.pro',
        'from_name' => 'ExperientIA',
        'notify_to' => 'hello@experientia.pro', // recibe avisos de leads
    ],

    // Clave secreta para firmar enlaces de descarga y tokens (cambiar en producción).
    'secret' => 'CAMBIAR-clave-larga-y-aleatoria',

    'booking' => [
        'slot_minutes' => 45,
        'days_ahead' => 21,
        'min_hours_ahead' => 12,
        'timezone' => 'America/Bogota',
    ],

    'industries' => [
        'tecnologia' => 'Tecnología y software',
        'retail' => 'Retail y comercio',
        'financiero' => 'Servicios financieros',
        'salud' => 'Salud',
        'manufactura' => 'Manufactura',
        'educacion' => 'Educación',
        'logistica' => 'Logística y transporte',
        'agroindustria' => 'Agroindustria',
        'turismo' => 'Turismo y hospitalidad',
        'profesionales' => 'Servicios profesionales',
        'construccion' => 'Construcción e inmobiliario',
        'energia' => 'Energía',
        'gobierno' => 'Gobierno y ONG',
        'medios' => 'Medios y marketing',
        'otro' => 'Otra industria',
    ],

    'company_sizes' => [
        'micro' => '1–10 (Micro)',
        'pequena' => '11–50 (Pequeña)',
        'mediana' => '51–200 (Mediana)',
        'grande' => '201–1000 (Grande)',
        'corporativa' => '1000+ (Corporativa)',
    ],

    'lead_statuses' => [
        'nuevo' => 'Nuevo',
        'contactado' => 'Contactado',
        'calificado' => 'Calificado',
        'propuesta' => 'Propuesta',
        'cliente' => 'Cliente',
        'descartado' => 'Descartado',
    ],

    'social' => [
        ['name' => 'Instagram', 'icon' => 'instagram', 'url' => 'https://www.instagram.com/experientia.sas/'],
        ['name' => 'LinkedIn', 'icon' => 'linkedin', 'url' => 'https://www.linkedin.com/in/tonny-dager/'],
        ['name' => 'Facebook', 'icon' => 'facebook', 'url' => 'https://www.facebook.com/Experientia.SAS'],
    ],
];

if (is_file(__DIR__ . '/config.local.php')) {
    $local = require __DIR__ . '/config.local.php';
    $config = array_replace_recursive($config, $local);
}

return $config;
