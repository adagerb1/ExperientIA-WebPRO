<?php
return [
    'locales' => ['es', 'en', 'pt'],
    'industries' => [
        'tecnologia' => 'Tecnología y software', 'retail' => 'Retail y comercio',
        'financiero' => 'Servicios financieros', 'salud' => 'Salud', 'manufactura' => 'Manufactura',
        'educacion' => 'Educación', 'logistica' => 'Logística y transporte', 'agroindustria' => 'Agroindustria',
        'turismo' => 'Turismo y hospitalidad', 'profesionales' => 'Servicios profesionales',
        'construccion' => 'Construcción e inmobiliario', 'energia' => 'Energía',
        'gobierno' => 'Gobierno y ONG', 'medios' => 'Medios y marketing', 'otro' => 'Otra industria',
    ],
    'company_sizes' => [
        'micro' => '1–10 (Micro)', 'pequena' => '11–50 (Pequeña)', 'mediana' => '51–200 (Mediana)',
        'grande' => '201–1000 (Grande)', 'corporativa' => '1000+ (Corporativa)',
    ],
    'lead_statuses' => [
        'nuevo' => 'Nuevo', 'contactado' => 'Contactado', 'calificado' => 'Calificado',
        'propuesta' => 'Propuesta', 'cliente' => 'Cliente', 'descartado' => 'Descartado',
    ],
    'connectors' => [
        'openai' => ['nombre' => 'OpenAI · AlexIA', 'campos' => ['api_key', 'model'], 'grupo' => 'IA'],
        'sendgrid' => ['nombre' => 'SendGrid', 'campos' => ['api_key', 'from_email', 'from_name'], 'grupo' => 'Comunicaciones'],
        'telegram' => ['nombre' => 'Telegram (2 bots)', 'campos' => ['commercial_token', 'internal_token', 'webhook_secret'], 'grupo' => 'Comunicaciones'],
        'whatsapp' => ['nombre' => 'WhatsApp Business', 'campos' => ['token', 'phone_id', 'verify_token'], 'grupo' => 'Comunicaciones'],
        'wompi' => ['nombre' => 'Wompi', 'campos' => ['public_key', 'private_key'], 'grupo' => 'Pagos'],
        'epayco' => ['nombre' => 'ePayco', 'campos' => ['public_key', 'private_key'], 'grupo' => 'Pagos'],
        'stripe' => ['nombre' => 'Stripe', 'campos' => ['secret_key', 'webhook_secret'], 'grupo' => 'Pagos'],
        'paypal' => ['nombre' => 'PayPal', 'campos' => ['client_id', 'secret'], 'grupo' => 'Pagos'],
        'google_calendar' => ['nombre' => 'Google Calendar', 'campos' => ['client_id', 'client_secret', 'refresh_token', 'calendar_id'], 'grupo' => 'Agenda'],
    ],
    'booking' => ['slot_minutes' => 45, 'days_ahead' => 21, 'min_hours_ahead' => 12, 'timezone' => 'America/Bogota'],
];
