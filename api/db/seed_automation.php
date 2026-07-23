<?php
/**
 * Semilla de automatización: plantillas de campaña + una secuencia de bienvenida
 * (creada PAUSADA para no enviar nada hasta que la actives). Usada por install.php
 * y migrate.php sólo si las tablas están vacías.
 */
return [
    'templates' => [
        [
            'nombre' => 'Bienvenida',
            'canal' => 'email',
            'asunto' => ['es' => 'Hola {nombre}, gracias por escribir a ExperientIA', 'en' => 'Hi {nombre}, thanks for reaching ExperientIA', 'pt' => 'Olá {nombre}, obrigado por falar com a ExperientIA'],
            'cuerpo' => [
                'es' => "Hola {nombre},\n\nGracias por tu interés en ExperientIA. Ayudamos a la dirección a convertir la IA, los datos y la automatización en crecimiento medible.\n\n¿Te gustaría una sesión de diagnóstico ejecutivo de 30 minutos? Responde a este correo y la agendamos.\n\n— El equipo de ExperientIA",
                'en' => "Hi {nombre},\n\nThanks for your interest in ExperientIA. We help leadership turn AI, data and automation into measurable growth.\n\nWould you like a 30-minute executive diagnostic session? Reply to this email and we'll schedule it.\n\n— The ExperientIA team",
                'pt' => "Olá {nombre},\n\nObrigado pelo seu interesse na ExperientIA. Ajudamos a diretoria a transformar IA, dados e automação em crescimento mensurável.\n\nQuer uma sessão de diagnóstico executivo de 30 minutos? Responda a este e-mail e agendamos.\n\n— A equipe da ExperientIA",
            ],
        ],
        [
            'nombre' => 'Seguimiento 48h',
            'canal' => 'email',
            'asunto' => ['es' => '{nombre}, una idea rápida para {empresa}', 'en' => '{nombre}, a quick idea for {empresa}', 'pt' => '{nombre}, uma ideia rápida para {empresa}'],
            'cuerpo' => [
                'es' => "Hola {nombre},\n\nTe escribo para saber si viste nuestro mensaje anterior. En 30 minutos podemos mostrarte, con tu contexto, dónde la IA rinde primero en tu operación.\n\n¿Reservamos un espacio esta semana?\n\n— ExperientIA",
                'en' => "Hi {nombre},\n\nJust checking whether you saw our previous note. In 30 minutes we can show you, with your context, where AI pays off first in your operation.\n\nShall we book a slot this week?\n\n— ExperientIA",
                'pt' => "Olá {nombre},\n\nSó para saber se viu nossa mensagem anterior. Em 30 minutos podemos mostrar, com o seu contexto, onde a IA rende primeiro na sua operação.\n\nVamos reservar um horário esta semana?\n\n— ExperientIA",
            ],
        ],
    ],
    // Secuencia de ejemplo PAUSADA (active=0): actívala cuando quieras que envíe.
    'sequence' => [
        'nombre' => 'Bienvenida a lead nuevo',
        'trigger_status' => 'nuevo',
        'active' => 0,
        'steps' => [
            ['delay_hours' => 0, 'template_idx' => 0],
            ['delay_hours' => 48, 'template_idx' => 1],
        ],
    ],
];
