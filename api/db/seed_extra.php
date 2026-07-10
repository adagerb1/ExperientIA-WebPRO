<?php
/** Semillas adicionales: conectores y plantillas de email. */
return [
    'email_templates' => [
        [
            'tkey' => 'lead_notify',
            'subject' => ['es' => '[ExperientIA] Nueva interacción: {{titulo}}', 'en' => '[ExperientIA] New interaction: {{titulo}}', 'pt' => '[ExperientIA] Nova interação: {{titulo}}'],
            'body' => ['es' => '<h2>{{titulo}}</h2><p><b>{{name}}</b> · {{company}}</p><ul><li>Correo: {{email}}</li><li>WhatsApp: {{phone}}</li><li>País: {{country}} · Industria: {{industry}}</li></ul><p>{{detalle}}</p>', 'en' => '', 'pt' => ''],
        ],
        [
            'tkey' => 'booking_confirm',
            'subject' => ['es' => 'Su sesión 1:1 con ExperientIA está confirmada', 'en' => 'Your 1:1 session with ExperientIA is confirmed', 'pt' => 'Sua sessão 1:1 com a ExperientIA está confirmada'],
            'body' => ['es' => '<h2>Su sesión está confirmada</h2><p>Hola {{name}}, su sesión quedó agendada para <b>{{fecha}}</b> ({{tz}}).</p><p>Le contactaremos con el enlace de la reunión.</p>', 'en' => '<h2>Your session is confirmed</h2><p>Hi {{name}}, your session is booked for <b>{{fecha}}</b> ({{tz}}).</p>', 'pt' => '<h2>Sua sessão está confirmada</h2><p>Olá {{name}}, sua sessão foi agendada para <b>{{fecha}}</b> ({{tz}}).</p>'],
        ],
        [
            'tkey' => 'resource_delivery',
            'subject' => ['es' => 'Su recurso de ExperientIA: {{titulo}}', 'en' => 'Your ExperientIA resource: {{titulo}}', 'pt' => 'Seu recurso da ExperientIA: {{titulo}}'],
            'body' => ['es' => '<h2>Su recurso está listo</h2><p><b>{{titulo}}</b></p><p><a href="{{url}}">Descargar</a> (enlace válido 7 días)</p>', 'en' => '<h2>Your resource is ready</h2><p><a href="{{url}}">Download</a></p>', 'pt' => '<h2>Seu recurso está pronto</h2><p><a href="{{url}}">Baixar</a></p>'],
        ],
        [
            'tkey' => 'newsletter_welcome',
            'subject' => ['es' => 'Bienvenido a la inteligencia ejecutiva de ExperientIA', 'en' => 'Welcome to ExperientIA executive intelligence', 'pt' => 'Bem-vindo à inteligência executiva da ExperientIA'],
            'body' => ['es' => '<h2>Gracias por suscribirse</h2><p>Recibirá una síntesis mensual sobre IA, automatización y crecimiento.</p>', 'en' => '<h2>Thanks for subscribing</h2>', 'pt' => '<h2>Obrigado por assinar</h2>'],
        ],
    ],
    'connectors' => ['openai', 'anthropic', 'sendgrid', 'telegram', 'whatsapp', 'wompi', 'epayco', 'stripe', 'paypal', 'google_calendar'],
];
