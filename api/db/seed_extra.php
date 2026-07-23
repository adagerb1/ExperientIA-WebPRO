<?php
/** Semillas adicionales: conectores y plantillas de email. */
return [
    'email_templates' => [
        [
            'tkey' => 'gb_diagnostico',
            'subject' => ['es' => 'Tu Tablero de Crecimiento ya empezó a hablar', 'en' => 'Your Growth Board has started to speak', 'pt' => 'Seu Painel de Crescimento já começou a falar'],
            'body' => [
                'es' => '<p>Hola, {{nombre}}.</p><p>Gracias por completar el Diagnóstico Tablero de Crecimiento.</p><p>Este diagnóstico nos ayuda a leer dónde tu empresa puede estar perdiendo fuerza: estrategia, operación, datos, automatización, ventas o experiencia.</p><p>El siguiente paso es revisar tu resultado y detectar tu primera jugada.</p><p>Si tu empresa está vendiendo, pero no está creciendo como debería, probablemente no le falta movimiento.</p><p><b>Le falta tablero.</b></p><p>Tonny Dager<br>ExperientIA</p>',
                'en' => '<p>Hi, {{nombre}}.</p><p>Thanks for completing the Growth Board Diagnostic.</p><p>It helps us read where your company may be losing strength: strategy, operations, data, automation, sales or experience.</p><p>The next step is to review your result and spot your first play.</p><p>If your company is selling but not growing as it should, it probably does not lack movement.</p><p><b>It lacks a board.</b></p><p>Tonny Dager<br>ExperientIA</p>',
                'pt' => '<p>Olá, {{nombre}}.</p><p>Obrigado por completar o Diagnóstico Painel de Crescimento.</p><p>Ele nos ajuda a ler onde sua empresa pode estar perdendo força: estratégia, operação, dados, automação, vendas ou experiência.</p><p>O próximo passo é revisar seu resultado e detectar sua primeira jogada.</p><p>Se sua empresa está vendendo, mas não está crescendo como deveria, provavelmente não falta movimento.</p><p><b>Falta painel.</b></p><p>Tonny Dager<br>ExperientIA</p>',
            ],
        ],
        [
            'tkey' => 'gb_acceso',
            'subject' => ['es' => 'Tu acceso a Mi GrowthBoard', 'en' => 'Your access to My GrowthBoard', 'pt' => 'Seu acesso ao Meu GrowthBoard'],
            'body' => [
                'es' => '<p>Hola, {{nombre}}.</p><p>Este es tu acceso personal a tu tablero de crecimiento en vivo: tu cancha, tus jugadas y tu marcador semanal.</p><p>El enlace es personal y vence en 30 días.</p>',
                'en' => '<p>Hi, {{nombre}}.</p><p>This is your personal access to your live growth board: your field, your plays and your weekly scoreboard.</p><p>The link is personal and expires in 30 days.</p>',
                'pt' => '<p>Olá, {{nombre}}.</p><p>Este é seu acesso pessoal ao seu painel de crescimento ao vivo: seu campo, suas jogadas e seu placar semanal.</p><p>O link é pessoal e expira em 30 dias.</p>',
            ],
        ],
        [
            'tkey' => 'lead_notify',
            'subject' => ['es' => '[ExperientIA] Nueva interacción: {{titulo}}', 'en' => '[ExperientIA] New interaction: {{titulo}}', 'pt' => '[ExperientIA] Nova interação: {{titulo}}'],
            'body' => ['es' => '<h2>{{titulo}}</h2><p><b>{{name}}</b> · {{company}}</p><ul><li>Correo: {{email}}</li><li>WhatsApp: {{phone}}</li><li>País: {{country}} · Industria: {{industry}}</li></ul><p>{{detalle}}</p>', 'en' => '', 'pt' => ''],
        ],
        [
            'tkey' => 'testimonial_invite',
            'subject' => ['es' => '{{nombre}}, tu experiencia con ExperientIA vale oro', 'en' => '{{nombre}}, your experience with ExperientIA is gold', 'pt' => '{{nombre}}, sua experiência com a ExperientIA vale ouro'],
            'body' => [
                'es' => '<p>Hola, {{nombre}}.</p><p>Trabajar contigo ha sido un privilegio, y tu opinión puede ayudar a otras empresas a dar el paso.</p><p>¿Nos regalas un testimonio de tu experiencia? Es un formulario de <b>2 minutos</b>: puedes contar qué cambió en tu negocio y, si quieres, incluir tu foto y el logo de tu empresa.</p>',
                'en' => '<p>Hi, {{nombre}}.</p><p>Working with you has been a privilege, and your opinion can help other companies take the leap.</p><p>Would you share a testimonial about your experience? It is a <b>2-minute</b> form: tell us what changed in your business and, if you like, include your photo and your company logo.</p>',
                'pt' => '<p>Olá, {{nombre}}.</p><p>Trabalhar com você foi um privilégio, e sua opinião pode ajudar outras empresas a dar o passo.</p><p>Você nos daria um depoimento sobre sua experiência? É um formulário de <b>2 minutos</b>: conte o que mudou no seu negócio e, se quiser, inclua sua foto e o logo da sua empresa.</p>',
            ],
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
