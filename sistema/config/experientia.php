<?php

return [
    // Industrias disponibles en formularios y CRM (clave => etiqueta base ES;
    // las etiquetas públicas por idioma viven en lang/*/site.php).
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

    // Duración de la sesión 1:1 en minutos y horizonte de agendamiento en días.
    'booking' => [
        'slot_minutes' => 45,
        'days_ahead' => 21,
        'min_hours_ahead' => 12,
        'timezone' => 'America/Bogota',
    ],

    'contact_email' => env('EXPERIENTIA_CONTACT_EMAIL', 'hello@experientia.pro'),
];
