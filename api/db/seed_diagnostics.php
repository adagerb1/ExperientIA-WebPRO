<?php
/**
 * Semilla de diagnósticos dinámicos. Cada diagnóstico define sus preguntas y sus
 * resultados; un resultado puede enlazar una solución (solucion_skey) para heredar
 * su copy, o traer el suyo propio. Usado por install.php y migrate.php.
 */
$madurez = require __DIR__ . '/../config/diagnostico.php';

return [
    [
        'dkey' => 'madurez-ia',
        'icon' => 'target',
        'nombre' => ['es' => 'Diagnóstico de Madurez en IA', 'en' => 'AI Maturity Diagnostic', 'pt' => 'Diagnóstico de Maturidade em IA'],
        'intro' => ['es' => 'Responde y descubre tu mayor oportunidad con IA.', 'en' => 'Answer and discover your biggest AI opportunity.', 'pt' => 'Responda e descubra sua maior oportunidade com IA.'],
        'preguntas' => $madurez['preguntas'],
        // Resultados enlazados a soluciones: heredan título/pilar/qué cambia de la solución.
        'resultados' => [
            ['clave' => 'estrategia', 'solucion_skey' => 'estrategia'],
            ['clave' => 'automatizacion', 'solucion_skey' => 'automatizacion'],
            ['clave' => 'datos', 'solucion_skey' => 'datos'],
            ['clave' => 'growth', 'solucion_skey' => 'growth'],
        ],
        'default_result' => 'estrategia',
        'sort' => 1,
        'active' => 1,
    ],
    [
        'dkey' => 'preparacion-automatizacion',
        'icon' => 'gear',
        'nombre' => ['es' => '¿Está tu operación lista para automatizar?', 'en' => 'Is your operation ready to automate?', 'pt' => 'Sua operação está pronta para automatizar?'],
        'intro' => ['es' => '5 preguntas para saber por dónde empezar con la automatización.', 'en' => '5 questions to know where to start with automation.', 'pt' => '5 perguntas para saber por onde começar com a automação.'],
        'preguntas' => [
            [
                'id' => 'volumen',
                'texto' => ['es' => '¿Tus procesos repetitivos tienen un volumen alto y constante?', 'en' => 'Do your repetitive processes have high, constant volume?', 'pt' => 'Seus processos repetitivos têm volume alto e constante?'],
                'opciones' => [
                    ['texto' => ['es' => 'Sí, todos los días y en gran cantidad', 'en' => 'Yes, every day and in large amounts', 'pt' => 'Sim, todos os dias e em grande quantidade'], 'scores' => ['listo' => 3]],
                    ['texto' => ['es' => 'Volumen medio, algunos picos', 'en' => 'Medium volume, some peaks', 'pt' => 'Volume médio, alguns picos'], 'scores' => ['listo' => 1]],
                    ['texto' => ['es' => 'Bajo o muy variable', 'en' => 'Low or very variable', 'pt' => 'Baixo ou muito variável'], 'scores' => ['estrategia-primero' => 1]],
                ],
            ],
            [
                'id' => 'datos_orden',
                'texto' => ['es' => '¿La información que alimenta esos procesos está ordenada y accesible?', 'en' => 'Is the data feeding those processes organized and accessible?', 'pt' => 'A informação que alimenta esses processos está organizada e acessível?'],
                'opciones' => [
                    ['texto' => ['es' => 'Sí, en sistemas conectados', 'en' => 'Yes, in connected systems', 'pt' => 'Sim, em sistemas conectados'], 'scores' => ['listo' => 2]],
                    ['texto' => ['es' => 'Parcialmente, algo en Excel', 'en' => 'Partially, some in Excel', 'pt' => 'Parcialmente, algo em Excel'], 'scores' => ['ordenar-datos' => 2]],
                    ['texto' => ['es' => 'Está dispersa y desordenada', 'en' => 'It is scattered and messy', 'pt' => 'Está dispersa e desorganizada'], 'scores' => ['ordenar-datos' => 3]],
                ],
            ],
            [
                'id' => 'reglas',
                'texto' => ['es' => '¿Los procesos siguen reglas claras o dependen del criterio de cada persona?', 'en' => 'Do processes follow clear rules or depend on each person’s judgment?', 'pt' => 'Os processos seguem regras claras ou dependem do critério de cada pessoa?'],
                'opciones' => [
                    ['texto' => ['es' => 'Reglas claras y documentadas', 'en' => 'Clear, documented rules', 'pt' => 'Regras claras e documentadas'], 'scores' => ['listo' => 2]],
                    ['texto' => ['es' => 'Reglas informales, en la cabeza del equipo', 'en' => 'Informal rules, in the team’s heads', 'pt' => 'Regras informais, na cabeça da equipe'], 'scores' => ['listo' => 1]],
                    ['texto' => ['es' => 'Cada quien lo hace a su manera', 'en' => 'Everyone does it their own way', 'pt' => 'Cada um faz do seu jeito'], 'scores' => ['estrategia-primero' => 2]],
                ],
            ],
            [
                'id' => 'costo',
                'texto' => ['es' => '¿Esas tareas consumen tiempo de tu talento más caro?', 'en' => 'Do those tasks consume your most expensive talent’s time?', 'pt' => 'Essas tarefas consomem o tempo do seu talento mais caro?'],
                'opciones' => [
                    ['texto' => ['es' => 'Sí, gente senior atrapada en lo operativo', 'en' => 'Yes, senior people stuck in operations', 'pt' => 'Sim, gente sênior presa no operacional'], 'scores' => ['listo' => 3]],
                    ['texto' => ['es' => 'A veces', 'en' => 'Sometimes', 'pt' => 'Às vezes'], 'scores' => ['listo' => 1]],
                    ['texto' => ['es' => 'No realmente', 'en' => 'Not really', 'pt' => 'Nem tanto'], 'scores' => []],
                ],
            ],
            [
                'id' => 'meta',
                'texto' => ['es' => '¿Qué buscas principalmente al automatizar?', 'en' => 'What are you mainly after when automating?', 'pt' => 'O que você busca principalmente ao automatizar?'],
                'opciones' => [
                    ['texto' => ['es' => 'Escalar sin contratar más', 'en' => 'Scale without hiring more', 'pt' => 'Escalar sem contratar mais'], 'scores' => ['listo' => 2]],
                    ['texto' => ['es' => 'Tener datos confiables para decidir', 'en' => 'Have reliable data to decide', 'pt' => 'Ter dados confiáveis para decidir'], 'scores' => ['ordenar-datos' => 2]],
                    ['texto' => ['es' => 'Aún no tengo claridad del objetivo', 'en' => 'I’m not clear on the goal yet', 'pt' => 'Ainda não tenho clareza do objetivo'], 'scores' => ['estrategia-primero' => 3]],
                ],
            ],
        ],
        'resultados' => [
            [
                'clave' => 'listo', 'solucion_skey' => 'automatizacion', 'icon' => 'gear',
                'pilar' => ['es' => 'Listo para automatizar', 'en' => 'Ready to automate', 'pt' => 'Pronto para automatizar'],
                'titulo' => ['es' => 'Tu operación está lista para automatizar', 'en' => 'Your operation is ready to automate', 'pt' => 'Sua operação está pronta para automatizar'],
                'descripcion' => ['es' => 'Tienes volumen, reglas y datos suficientes: el retorno de automatizar es alto y rápido. Empecemos por los procesos de mayor impacto.', 'en' => 'You have enough volume, rules and data: the return on automation is high and fast. Let’s start with the highest-impact processes.', 'pt' => 'Você tem volume, regras e dados suficientes: o retorno de automatizar é alto e rápido. Vamos começar pelos processos de maior impacto.'],
            ],
            [
                'clave' => 'ordenar-datos', 'solucion_skey' => 'datos', 'icon' => 'analitica',
                'pilar' => ['es' => 'Primero, ordenar los datos', 'en' => 'First, organize the data', 'pt' => 'Primeiro, organizar os dados'],
                'titulo' => ['es' => 'Ordena tus datos y la automatización rendirá el doble', 'en' => 'Organize your data and automation will pay off twice as much', 'pt' => 'Organize seus dados e a automação renderá o dobro'],
                'descripcion' => ['es' => 'Automatizar sobre datos dispersos multiplica errores. Consolidamos primero tu información y luego automatizamos sobre una base confiable.', 'en' => 'Automating on scattered data multiplies errors. We consolidate your information first, then automate on a reliable base.', 'pt' => 'Automatizar sobre dados dispersos multiplica erros. Consolidamos sua informação primeiro e depois automatizamos sobre uma base confiável.'],
            ],
            [
                'clave' => 'estrategia-primero', 'solucion_skey' => 'estrategia', 'icon' => 'target',
                'pilar' => ['es' => 'Primero, claridad estratégica', 'en' => 'First, strategic clarity', 'pt' => 'Primeiro, clareza estratégica'],
                'titulo' => ['es' => 'Define el objetivo antes de automatizar', 'en' => 'Define the goal before automating', 'pt' => 'Defina o objetivo antes de automatizar'],
                'descripcion' => ['es' => 'Sin reglas ni objetivo claro, automatizar consolida el desorden. Empecemos por un roadmap que priorice dónde automatizar de verdad mueve el negocio.', 'en' => 'Without clear rules or goal, automating cements the chaos. Let’s start with a roadmap that prioritizes where automation truly moves the business.', 'pt' => 'Sem regras nem objetivo claro, automatizar consolida a desordem. Vamos começar por um roadmap que prioriza onde automatizar realmente move o negócio.'],
            ],
        ],
        'default_result' => 'listo',
        'sort' => 2,
        'active' => 1,
    ],
];
