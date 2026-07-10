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
    // Categorías (orden de las pestañas del panel de Conectores)
    'connector_groups' => [
        'ia' => 'Inteligencia artificial (AlexIA)',
        'pagos' => 'Pasarelas de pago',
        'correo' => 'Correo',
        'bots' => 'Mensajería y bots',
        'agenda' => 'Agenda',
    ],
    // t: secret | text | select. transient: no se persiste (solo para acciones).
    'connectors' => [
        'openai' => [
            'nombre' => 'OpenAI', 'grupo' => 'ia',
            'desc' => 'El cerebro que redacta, analiza y responde. Imágenes y audio para recursos.',
            'campos' => [
                ['n' => 'api_key', 'l' => 'API key', 't' => 'secret', 'ph' => 'sk-...'],
                ['n' => 'model', 'l' => 'Modelo de texto', 't' => 'text', 'ph' => 'gpt-4o-mini',
                    'help' => 'Chat/análisis. Válidos hoy: gpt-4o, gpt-4o-mini, gpt-4.1, gpt-4.1-mini, gpt-4.1-nano, o3, o4-mini.'],
                ['n' => 'image_model', 'l' => 'Modelo de imagen (portadas)', 't' => 'select', 'op' => ['gpt-image-1', 'dall-e-3']],
                ['n' => 'audio_model', 'l' => 'Modelo de audio', 't' => 'select', 'op' => ['gpt-4o-mini-tts', 'tts-1', 'tts-1-hd']],
                ['n' => 'voice', 'l' => 'Voz del audio', 't' => 'select', 'op' => ['alloy', 'ash', 'coral', 'echo', 'fable', 'onyx', 'nova', 'sage', 'shimmer']],
            ],
            'acciones' => [['k' => 'test', 'l' => 'Probar']],
        ],
        'anthropic' => [
            'nombre' => 'Anthropic (Claude)', 'grupo' => 'ia',
            'desc' => 'Cerebro alternativo de AlexIA para redacción y análisis estratégico.',
            'campos' => [
                ['n' => 'api_key', 'l' => 'API key', 't' => 'secret', 'ph' => 'sk-ant-api03-...'],
                ['n' => 'model', 'l' => 'Modelo de texto', 't' => 'text', 'ph' => 'claude-sonnet-4-5',
                    'help' => 'Válidos hoy: claude-opus-4-1, claude-sonnet-4-5, claude-haiku-4-5.'],
            ],
            'acciones' => [['k' => 'test', 'l' => 'Probar']],
        ],
        'sendgrid' => [
            'nombre' => 'SendGrid', 'grupo' => 'correo',
            'desc' => 'Envía confirmaciones y recordatorios de forma confiable.',
            'campos' => [
                ['n' => 'api_key', 'l' => 'API key', 't' => 'secret'],
                ['n' => 'from_email', 'l' => 'Correo remitente', 't' => 'text', 'ph' => 'hello@experientia.pro'],
                ['n' => 'from_name', 'l' => 'Nombre remitente', 't' => 'text', 'ph' => 'ExperientIA'],
                ['n' => 'test_to', 'l' => 'Enviar prueba a (opcional)', 't' => 'text', 'ph' => 'tucorreo@ejemplo.com', 'transient' => true],
            ],
            'acciones' => [['k' => 'send_test', 'l' => 'Enviar correo de prueba']],
        ],
        'telegram' => [
            'nombre' => 'Telegram', 'grupo' => 'bots',
            'desc' => 'Agente comercial (capta leads) y bot interno de AlexIA.',
            'campos' => [
                ['n' => 'commercial_token', 'l' => 'Bot comercial (leads)', 't' => 'secret'],
                ['n' => 'internal_token', 'l' => 'Bot AlexIA (interno)', 't' => 'secret'],
                ['n' => 'authorized_chats', 'l' => 'Chats autorizados (AlexIA)', 't' => 'text', 'ph' => '12345678,87654321'],
                ['n' => 'webhook_secret', 'l' => 'Secreto de webhook', 't' => 'secret'],
            ],
            'acciones' => [['k' => 'register_webhooks', 'l' => 'Registrar webhooks'], ['k' => 'test', 'l' => 'Probar']],
        ],
        'whatsapp' => [
            'nombre' => 'WhatsApp Business', 'grupo' => 'bots',
            'desc' => 'AlexIA comercial en WhatsApp (Cloud API).',
            'campos' => [
                ['n' => 'token', 'l' => 'Access token', 't' => 'secret', 'ph' => 'EAAG...'],
                ['n' => 'phone_id', 'l' => 'Phone number ID', 't' => 'text'],
                ['n' => 'verify_token', 'l' => 'Verify token', 't' => 'text', 'ph' => 'mi-verify-token'],
                ['n' => 'waba_id', 'l' => 'WABA ID', 't' => 'text'],
            ],
            'acciones' => [['k' => 'webhook_url', 'l' => 'Ver URL de webhook'], ['k' => 'test', 'l' => 'Probar']],
        ],
        'wompi' => [
            'nombre' => 'Wompi (Bancolombia)', 'grupo' => 'pagos', 'desc' => 'Cobra consultas y servicios. Solo una activa a la vez.',
            'campos' => [
                ['n' => 'public_key', 'l' => 'Llave pública', 't' => 'text', 'ph' => 'pub_prod_...'],
                ['n' => 'private_key', 'l' => 'Llave privada', 't' => 'secret'],
                ['n' => 'integrity_secret', 'l' => 'Secreto de integridad', 't' => 'secret'],
                ['n' => 'events_secret', 'l' => 'Secreto de eventos', 't' => 'secret'],
            ],
            'acciones' => [['k' => 'test', 'l' => 'Probar']],
        ],
        'epayco' => [
            'nombre' => 'ePayco (Davivienda)', 'grupo' => 'pagos', 'desc' => 'Cobra consultas y servicios. Solo una activa a la vez.',
            'campos' => [
                ['n' => 'public_key', 'l' => 'Public key', 't' => 'text'],
                ['n' => 'p_cust_id', 'l' => 'P_CUST_ID', 't' => 'text'],
                ['n' => 'private_key', 'l' => 'P_KEY', 't' => 'secret'],
                ['n' => 'test_mode', 'l' => 'Modo prueba', 't' => 'select', 'op' => ['true', 'false']],
            ],
            'acciones' => [['k' => 'test', 'l' => 'Probar']],
        ],
        'stripe' => [
            'nombre' => 'Stripe', 'grupo' => 'pagos', 'desc' => 'Cobra consultas y servicios. Solo una activa a la vez.',
            'campos' => [
                ['n' => 'secret_key', 'l' => 'Secret key', 't' => 'secret', 'ph' => 'sk_live_...'],
                ['n' => 'webhook_secret', 'l' => 'Webhook secret', 't' => 'secret', 'ph' => 'whsec_...'],
            ],
            'acciones' => [['k' => 'test', 'l' => 'Probar']],
        ],
        'paypal' => [
            'nombre' => 'PayPal', 'grupo' => 'pagos', 'desc' => 'Cobra consultas y servicios. Solo una activa a la vez.',
            'campos' => [
                ['n' => 'client_id', 'l' => 'Client ID', 't' => 'text'],
                ['n' => 'secret', 'l' => 'Secret', 't' => 'secret'],
                ['n' => 'mode', 'l' => 'Entorno', 't' => 'select', 'op' => ['live', 'sandbox']],
            ],
            'acciones' => [['k' => 'test', 'l' => 'Probar']],
        ],
        'google_calendar' => [
            'nombre' => 'Google Calendar', 'grupo' => 'agenda',
            'desc' => 'Sincroniza reuniones y disponibilidad con Google Calendar.',
            'campos' => [
                ['n' => 'client_id', 'l' => 'Client ID', 't' => 'text', 'ph' => '...apps.googleusercontent.com'],
                ['n' => 'client_secret', 'l' => 'Client secret', 't' => 'secret'],
                ['n' => 'refresh_token', 'l' => 'Refresh token', 't' => 'secret'],
                ['n' => 'calendar_id', 'l' => 'Calendar ID', 't' => 'text', 'ph' => 'primary'],
            ],
            'acciones' => [['k' => 'create_test_event', 'l' => 'Crear evento de prueba'], ['k' => 'test', 'l' => 'Probar']],
        ],
    ],
    'booking' => ['slot_minutes' => 45, 'days_ahead' => 21, 'min_hours_ahead' => 12, 'timezone' => 'America/Bogota'],
];
