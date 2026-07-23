<?php
/**
 * GrowthBoard · Configuración del método "El Tablero de Crecimiento" (Tonny Dager).
 * La plataforma es el instrumento: mide, ordena y visualiza. La interpretación
 * y el acompañamiento son del consultor. Este archivo es la semilla; las zonas
 * viven en BD (gb_zones) y se editan desde el admin.
 */

$t = fn (string $es, string $en, string $pt) => ['es' => $es, 'en' => $en, 'pt' => $pt];

return [

    // ── Líneas del tablero (lectura por líneas del framework) ────────────────
    'lineas' => [
        'direccion' => [
            'nombre' => $t('Dirección estratégica', 'Strategic direction', 'Direção estratégica'),
            'zonas' => ['vision', 'direccion'], 'max' => 10,
            'sintoma' => $t('Muchas ideas, poca prioridad.', 'Many ideas, little priority.', 'Muitas ideias, pouca prioridade.'),
            'jugada' => $t('Definir meta trimestral y responsables.', 'Define the quarterly goal and owners.', 'Definir meta trimestral e responsáveis.'),
        ],
        'defensa' => [
            'nombre' => $t('Defensa empresarial', 'Business defense', 'Defesa empresarial'),
            'zonas' => ['finanzas', 'operacion', 'cultura'], 'max' => 15,
            'sintoma' => $t('Se vende, pero la empresa se desgasta.', 'You sell, but the company wears down.', 'Vende-se, mas a empresa se desgasta.'),
            'jugada' => $t('Ordenar finanzas, operación y cultura.', 'Put finance, operations and culture in order.', 'Ordenar finanças, operação e cultura.'),
        ],
        'mediocampo' => [
            'nombre' => $t('Mediocampo de crecimiento', 'Growth midfield', 'Meio-campo de crescimento'),
            'zonas' => ['datos', 'procesos', 'automatizacion'], 'max' => 15,
            'sintoma' => $t('Los datos, procesos y herramientas no se conectan.', 'Data, processes and tools don’t connect.', 'Dados, processos e ferramentas não se conectam.'),
            'jugada' => $t('Crear flujo mínimo y tablero de indicadores.', 'Create a minimum flow and an indicator board.', 'Criar fluxo mínimo e painel de indicadores.'),
        ],
        'ataque' => [
            'nombre' => $t('Ataque comercial', 'Commercial attack', 'Ataque comercial'),
            'zonas' => ['marketing', 'ventas', 'experiencia'], 'max' => 15,
            'sintoma' => $t('Hay mercado, pero no se convierte ni se retiene.', 'There’s a market, but it isn’t converted or retained.', 'Há mercado, mas não se converte nem se retém.'),
            'jugada' => $t('Revisar mensaje, pipeline y seguimiento.', 'Review the message, pipeline and follow-up.', 'Revisar mensagem, pipeline e acompanhamento.'),
        ],
    ],

    // ── Bandas de madurez (lectura del resultado, 11–55) ─────────────────────
    'bandas' => [
        ['min' => 11, 'max' => 25, 'key' => 'reaccion',
            'titulo' => $t('Empresa en modo reacción', 'Company in reaction mode', 'Empresa em modo reação'),
            'lectura' => $t('La empresa depende de esfuerzo, memoria y urgencias.', 'The company runs on effort, memory and urgencies.', 'A empresa depende de esforço, memória e urgências.'),
            'prioridad' => $t('Ordenar visión, procesos y datos básicos.', 'Order vision, processes and basic data.', 'Ordenar visão, processos e dados básicos.'),
            'oferta' => $t('Diagnóstico Tablero de Crecimiento', 'Growth Board Diagnostic', 'Diagnóstico Painel de Crescimento')],
        ['min' => 26, 'max' => 40, 'key' => 'fugas',
            'titulo' => $t('Empresa con fugas', 'Company with leaks', 'Empresa com fugas'),
            'lectura' => $t('La empresa vende, pero pierde oportunidades, tiempo y margen.', 'The company sells, but loses opportunities, time and margin.', 'A empresa vende, mas perde oportunidades, tempo e margem.'),
            'prioridad' => $t('Cerrar fugas comerciales y operativas.', 'Close commercial and operational leaks.', 'Fechar fugas comerciais e operacionais.'),
            'oferta' => $t('Sprint Fuga Cero', 'Zero-Leak Sprint', 'Sprint Fuga Zero')],
        ['min' => 41, 'max' => 50, 'key' => 'escalar',
            'titulo' => $t('Empresa lista para escalar', 'Company ready to scale', 'Empresa pronta para escalar'),
            'lectura' => $t('La empresa tiene base, pero necesita automatización y cultura de ejecución.', 'The company has a base, but needs automation and an execution culture.', 'A empresa tem base, mas precisa de automação e cultura de execução.'),
            'prioridad' => $t('Conectar datos, procesos, IA y seguimiento.', 'Connect data, processes, AI and follow-up.', 'Conectar dados, processos, IA e acompanhamento.'),
            'oferta' => $t('Implementación Tablero de Crecimiento', 'Growth Board Implementation', 'Implementação Painel de Crescimento')],
        ['min' => 51, 'max' => 55, 'key' => 'optimizable',
            'titulo' => $t('Empresa optimizable', 'Optimizable company', 'Empresa otimizável'),
            'lectura' => $t('La empresa tiene sistema, pero puede mejorar precisión, velocidad y rentabilidad.', 'The company has a system, but can improve precision, speed and profitability.', 'A empresa tem sistema, mas pode melhorar precisão, velocidade e rentabilidade.'),
            'prioridad' => $t('Optimización avanzada, dashboards e IA aplicada.', 'Advanced optimization, dashboards and applied AI.', 'Otimização avançada, dashboards e IA aplicada.'),
            'oferta' => $t('Acompañamiento estratégico mensual', 'Monthly strategic advisory', 'Acompanhamento estratégico mensal')],
    ],

    // ── Rangos de facturación mensual (formulario) ───────────────────────────
    'facturacion' => [
        'lt10k' => $t('Menos de USD 10 mil', 'Under USD 10k', 'Menos de USD 10 mil'),
        '10k50k' => $t('USD 10 mil – 50 mil', 'USD 10k – 50k', 'USD 10 mil – 50 mil'),
        '50k200k' => $t('USD 50 mil – 200 mil', 'USD 50k – 200k', 'USD 50 mil – 200 mil'),
        '200k1m' => $t('USD 200 mil – 1 millón', 'USD 200k – 1M', 'USD 200 mil – 1 milhão'),
        'gt1m' => $t('Más de USD 1 millón', 'Over USD 1M', 'Mais de USD 1 milhão'),
        'nd' => $t('Prefiero no decirlo', 'Prefer not to say', 'Prefiro não dizer'),
    ],

    // ── Preguntas de contexto (previas al diagnóstico) ───────────────────────
    'contexto' => [
        ['k' => 'reto', 'multi' => true, 'q' => $t('¿Cuáles son hoy los mayores retos de crecimiento de tu empresa? (elige uno o varios)', 'What are your company’s biggest growth challenges today? (choose one or several)', 'Quais são hoje os maiores desafios de crescimento da sua empresa? (escolha um ou vários)'),
            'op' => [
                $t('Conseguir más clientes', 'Get more clients', 'Conseguir mais clientes'),
                $t('Convertir mejor los leads', 'Convert leads better', 'Converter melhor os leads'),
                $t('Ordenar la operación', 'Order the operation', 'Ordenar a operação'),
                $t('Medir mejor los números', 'Measure the numbers better', 'Medir melhor os números'),
                $t('Automatizar procesos', 'Automate processes', 'Automatizar processos'),
                $t('Alinear al equipo', 'Align the team', 'Alinhar a equipe'),
                $t('Mejorar rentabilidad', 'Improve profitability', 'Melhorar rentabilidade'),
                $t('Escalar sin depender del dueño', 'Scale without depending on the owner', 'Escalar sem depender do dono'),
            ]],
        ['k' => 'frase', 'q' => $t('¿Qué frase describe mejor tu empresa hoy?', 'Which phrase best describes your company today?', 'Qual frase descreve melhor sua empresa hoje?'),
            'op' => [
                $t('Tenemos movimiento, pero poca claridad', 'We have movement, but little clarity', 'Temos movimento, mas pouca clareza'),
                $t('Vendemos, pero no sabemos por qué no crecemos más', 'We sell, but don’t know why we don’t grow more', 'Vendemos, mas não sabemos por que não crescemos mais'),
                $t('Tenemos herramientas, pero falta sistema', 'We have tools, but lack a system', 'Temos ferramentas, mas falta sistema'),
                $t('Todo depende demasiado de pocas personas', 'Everything depends too much on a few people', 'Tudo depende demais de poucas pessoas'),
                $t('Hay datos, pero no decisiones', 'There’s data, but no decisions', 'Há dados, mas não decisões'),
                $t('Estamos listos para escalar, pero necesitamos orden', 'We’re ready to scale, but need order', 'Estamos prontos para escalar, mas precisamos de ordem'),
            ]],
        ['k' => 'urgencia', 'q' => $t('¿Qué tan urgente es destrabar este crecimiento?', 'How urgent is it to unlock this growth?', 'Quão urgente é destravar esse crescimento?'),
            'op' => [
                $t('Alta. Necesitamos actuar ya', 'High. We need to act now', 'Alta. Precisamos agir já'),
                $t('Media. Queremos ordenar antes de escalar', 'Medium. We want order before scaling', 'Média. Queremos ordenar antes de escalar'),
                $t('Baja. Estamos explorando opciones', 'Low. We’re exploring options', 'Baixa. Estamos explorando opções'),
            ]],
    ],

    // ── Las 11 zonas del tablero (contenido del método, editable en BD) ──────
    'zonas' => [
        'vision' => ['linea' => 'direccion', 'icon' => 'bulb',
            'nombre' => $t('Visión y Estrategia', 'Vision & Strategy', 'Visão e Estratégia'),
            'pregunta' => $t('¿La empresa sabe exactamente hacia dónde va este trimestre?', 'Does the company know exactly where it’s going this quarter?', 'A empresa sabe exatamente para onde vai neste trimestre?'),
            'afirmaciones' => [
                $t('Tenemos una meta trimestral clara.', 'We have a clear quarterly goal.', 'Temos uma meta trimestral clara.'),
                $t('Sabemos qué cliente queremos atraer.', 'We know which client we want to attract.', 'Sabemos qual cliente queremos atrair.'),
                $t('Tenemos prioridades definidas.', 'We have defined priorities.', 'Temos prioridades definidas.'),
                $t('El equipo entiende el rumbo.', 'The team understands the direction.', 'A equipe entende o rumo.'),
                $t('Las decisiones diarias responden a una estrategia.', 'Daily decisions respond to a strategy.', 'As decisões diárias respondem a uma estratégia.'),
            ],
            'senales' => [
                $t('Todo parece urgente.', 'Everything seems urgent.', 'Tudo parece urgente.'),
                $t('No hay foco trimestral.', 'There is no quarterly focus.', 'Não há foco trimestral.'),
                $t('Cada área trabaja con objetivos distintos.', 'Each area works toward different goals.', 'Cada área trabalha com objetivos diferentes.'),
                $t('El dueño decide todo sobre la marcha.', 'The owner decides everything on the fly.', 'O dono decide tudo na hora.'),
            ],
            'jugada' => $t('Definir una meta trimestral única y 3 prioridades estratégicas.', 'Define one quarterly goal and 3 strategic priorities.', 'Definir uma meta trimestral única e 3 prioridades estratégicas.')],

        'direccion' => ['linea' => 'direccion', 'icon' => 'target',
            'nombre' => $t('Dirección', 'Leadership', 'Direção'),
            'pregunta' => $t('¿La empresa decide rápido y protege el foco?', 'Does the company decide fast and protect focus?', 'A empresa decide rápido e protege o foco?'),
            'afirmaciones' => [
                $t('Las decisiones importantes tienen responsable.', 'Important decisions have an owner.', 'As decisões importantes têm responsável.'),
                $t('El gerente no bloquea todo el avance.', 'The manager doesn’t block all progress.', 'O gerente não bloqueia todo o avanço.'),
                $t('Las reuniones terminan con acciones.', 'Meetings end with actions.', 'As reuniões terminam com ações.'),
                $t('El equipo sabe qué puede decidir.', 'The team knows what it can decide.', 'A equipe sabe o que pode decidir.'),
                $t('Las prioridades no cambian cada semana.', 'Priorities don’t change every week.', 'As prioridades não mudam toda semana.'),
            ],
            'senales' => [
                $t('Todo pasa por el gerente.', 'Everything goes through the manager.', 'Tudo passa pelo gerente.'),
                $t('Nadie decide sin pedir permiso.', 'No one decides without asking permission.', 'Ninguém decide sem pedir permissão.'),
                $t('Se habla mucho y se ejecuta poco.', 'Lots of talk, little execution.', 'Fala-se muito e executa-se pouco.'),
                $t('Las prioridades cambian cada semana.', 'Priorities change every week.', 'As prioridades mudam toda semana.'),
            ],
            'jugada' => $t('Crear un comité semanal de decisiones con responsable, acción y fecha.', 'Create a weekly decision committee with owner, action and date.', 'Criar um comitê semanal de decisões com responsável, ação e data.')],

        'finanzas' => ['linea' => 'defensa', 'icon' => 'analitica',
            'nombre' => $t('Finanzas', 'Finance', 'Finanças'),
            'pregunta' => $t('¿La empresa sabe qué vende, qué gana y qué le deja margen?', 'Does the company know what it sells, earns and what leaves margin?', 'A empresa sabe o que vende, o que ganha e o que deixa margem?'),
            'afirmaciones' => [
                $t('Conocemos margen por producto o servicio.', 'We know margin per product or service.', 'Conhecemos a margem por produto ou serviço.'),
                $t('Medimos flujo de caja.', 'We measure cash flow.', 'Medimos o fluxo de caixa.'),
                $t('Sabemos qué clientes son rentables.', 'We know which clients are profitable.', 'Sabemos quais clientes são rentáveis.'),
                $t('Invertimos con criterio de retorno.', 'We invest with return criteria.', 'Investimos com critério de retorno.'),
                $t('Revisamos números antes de decidir.', 'We review numbers before deciding.', 'Revisamos números antes de decidir.'),
            ],
            'senales' => [
                $t('Se vende, pero no queda caja.', 'Sales happen, but no cash remains.', 'Vende-se, mas não sobra caixa.'),
                $t('No se conoce el margen por servicio.', 'Margin per service is unknown.', 'Não se conhece a margem por serviço.'),
                $t('Se invierte sin medir retorno.', 'Investment without measuring return.', 'Investe-se sem medir retorno.'),
                $t('El flujo de caja siempre sorprende.', 'Cash flow always surprises.', 'O fluxo de caixa sempre surpreende.'),
            ],
            'jugada' => $t('Crear tablero financiero simple: ingresos, costos, margen, caja y rentabilidad por línea.', 'Create a simple finance board: revenue, costs, margin, cash and profitability per line.', 'Criar painel financeiro simples: receita, custos, margem, caixa e rentabilidade por linha.')],

        'operacion' => ['linea' => 'defensa', 'icon' => 'gear',
            'nombre' => $t('Operación', 'Operations', 'Operação'),
            'pregunta' => $t('¿La empresa entrega con orden o vive apagando incendios?', 'Does the company deliver with order or live putting out fires?', 'A empresa entrega com ordem ou vive apagando incêndios?'),
            'afirmaciones' => [
                $t('Tenemos procesos claros.', 'We have clear processes.', 'Temos processos claros.'),
                $t('Sabemos dónde se atasca la operación.', 'We know where the operation gets stuck.', 'Sabemos onde a operação trava.'),
                $t('Las tareas tienen responsables.', 'Tasks have owners.', 'As tarefas têm responsáveis.'),
                $t('Los errores repetidos se corrigen.', 'Repeated errors get fixed.', 'Os erros repetidos são corrigidos.'),
                $t('La entrega no depende de héroes.', 'Delivery doesn’t depend on heroes.', 'A entrega não depende de heróis.'),
            ],
            'senales' => [
                $t('El equipo vive apagando incendios.', 'The team lives putting out fires.', 'A equipe vive apagando incêndios.'),
                $t('Nadie sabe el estado real de cada tarea.', 'No one knows each task’s real status.', 'Ninguém sabe o estado real de cada tarefa.'),
                $t('La entrega depende de héroes.', 'Delivery depends on heroes.', 'A entrega depende de heróis.'),
                $t('El cliente recibe respuestas inconsistentes.', 'Clients get inconsistent answers.', 'O cliente recebe respostas inconsistentes.'),
            ],
            'jugada' => $t('Mapear el proceso crítico y eliminar 3 fricciones operativas.', 'Map the critical process and remove 3 operational frictions.', 'Mapear o processo crítico e eliminar 3 atritos operacionais.')],

        'cultura' => ['linea' => 'defensa', 'icon' => 'people',
            'nombre' => $t('Cultura', 'Culture', 'Cultura'),
            'pregunta' => $t('¿El equipo adopta los cambios o solo escucha instrucciones?', 'Does the team adopt changes or just hear instructions?', 'A equipe adota as mudanças ou só escuta instruções?'),
            'afirmaciones' => [
                $t('El equipo entiende la estrategia.', 'The team understands the strategy.', 'A equipe entende a estratégia.'),
                $t('Las herramientas se usan correctamente.', 'Tools are used correctly.', 'As ferramentas são usadas corretamente.'),
                $t('Hay disciplina de seguimiento.', 'There is follow-up discipline.', 'Há disciplina de acompanhamento.'),
                $t('La formación cambia comportamientos.', 'Training changes behaviors.', 'A formação muda comportamentos.'),
                $t('Las personas trabajan alineadas.', 'People work aligned.', 'As pessoas trabalham alinhadas.'),
            ],
            'senales' => [
                $t('El equipo vuelve al Excel viejo.', 'The team goes back to the old spreadsheet.', 'A equipe volta à planilha antiga.'),
                $t('Cada persona trabaja a su manera.', 'Each person works their own way.', 'Cada pessoa trabalha do seu jeito.'),
                $t('La IA genera miedo o resistencia.', 'AI causes fear or resistance.', 'A IA gera medo ou resistência.'),
                $t('La formación no cambia comportamientos.', 'Training doesn’t change behaviors.', 'A formação não muda comportamentos.'),
            ],
            'jugada' => $t('Crear rituales de adopción: capacitación corta, práctica guiada y seguimiento semanal.', 'Create adoption rituals: short training, guided practice and weekly follow-up.', 'Criar rituais de adoção: capacitação curta, prática guiada e acompanhamento semanal.')],

        'datos' => ['linea' => 'mediocampo', 'icon' => 'analitica',
            'nombre' => $t('Datos', 'Data', 'Dados'),
            'pregunta' => $t('¿La empresa decide con datos o con intuición?', 'Does the company decide with data or intuition?', 'A empresa decide com dados ou com intuição?'),
            'afirmaciones' => [
                $t('Tenemos indicadores clave definidos.', 'We have key indicators defined.', 'Temos indicadores-chave definidos.'),
                $t('Los datos están centralizados.', 'Data is centralized.', 'Os dados estão centralizados.'),
                $t('Los reportes llegan a tiempo.', 'Reports arrive on time.', 'Os relatórios chegam a tempo.'),
                $t('El equipo confía en los números.', 'The team trusts the numbers.', 'A equipe confia nos números.'),
                $t('Revisamos datos cada semana.', 'We review data every week.', 'Revisamos dados toda semana.'),
            ],
            'senales' => [
                $t('Cada área tiene su propia verdad.', 'Each area has its own truth.', 'Cada área tem a sua própria verdade.'),
                $t('Los reportes llegan tarde.', 'Reports arrive late.', 'Os relatórios chegam atrasados.'),
                $t('Se decide por intuición.', 'Decisions are made by intuition.', 'Decide-se por intuição.'),
                $t('Nadie confía en los números.', 'No one trusts the numbers.', 'Ninguém confia nos números.'),
            ],
            'jugada' => $t('Definir 7 indicadores vitales y una fuente única de información.', 'Define 7 vital indicators and a single source of truth.', 'Definir 7 indicadores vitais e uma fonte única de informação.')],

        'procesos' => ['linea' => 'mediocampo', 'icon' => 'cube',
            'nombre' => $t('Procesos', 'Processes', 'Processos'),
            'pregunta' => $t('¿La empresa tiene método o cada persona trabaja a su manera?', 'Does the company have a method, or does each person work their own way?', 'A empresa tem método ou cada pessoa trabalha do seu jeito?'),
            'afirmaciones' => [
                $t('El flujo comercial está documentado.', 'The sales flow is documented.', 'O fluxo comercial está documentado.'),
                $t('El flujo operativo está documentado.', 'The operations flow is documented.', 'O fluxo operacional está documentado.'),
                $t('Cada etapa tiene responsable.', 'Each stage has an owner.', 'Cada etapa tem responsável.'),
                $t('Sabemos qué pasa después de cada acción.', 'We know what happens after each action.', 'Sabemos o que acontece depois de cada ação.'),
                $t('El proceso se puede repetir sin improvisar.', 'The process repeats without improvising.', 'O processo pode se repetir sem improvisar.'),
            ],
            'senales' => [
                $t('Los clientes reciben experiencias distintas.', 'Clients get different experiences.', 'Os clientes recebem experiências diferentes.'),
                $t('El seguimiento depende de memoria.', 'Follow-up depends on memory.', 'O acompanhamento depende de memória.'),
                $t('Nadie sabe qué sigue.', 'No one knows what’s next.', 'Ninguém sabe o que vem depois.'),
                $t('El proceso cambia según la persona.', 'The process changes with the person.', 'O processo muda conforme a pessoa.'),
            ],
            'jugada' => $t('Diseñar el flujo mínimo: entrada, responsable, acción, tiempo y resultado esperado.', 'Design the minimum flow: input, owner, action, time and expected result.', 'Desenhar o fluxo mínimo: entrada, responsável, ação, tempo e resultado esperado.')],

        'automatizacion' => ['linea' => 'mediocampo', 'icon' => 'gear',
            'nombre' => $t('Automatización', 'Automation', 'Automação'),
            'pregunta' => $t('¿La empresa libera tiempo o sigue haciendo todo manual?', 'Does the company free up time or still do everything manually?', 'A empresa libera tempo ou continua fazendo tudo manual?'),
            'afirmaciones' => [
                $t('Automatizamos tareas repetitivas.', 'We automate repetitive tasks.', 'Automatizamos tarefas repetitivas.'),
                $t('Tenemos alertas para no perder oportunidades.', 'We have alerts to avoid losing opportunities.', 'Temos alertas para não perder oportunidades.'),
                $t('Evitamos doble digitación.', 'We avoid double data entry.', 'Evitamos dupla digitação.'),
                $t('Usamos IA donde genera valor real.', 'We use AI where it creates real value.', 'Usamos IA onde gera valor real.'),
                $t('Las herramientas están conectadas.', 'Tools are connected.', 'As ferramentas estão conectadas.'),
            ],
            'senales' => [
                $t('El equipo copia y pega todo.', 'The team copies and pastes everything.', 'A equipe copia e cola tudo.'),
                $t('Los leads se enfrían sin alerta.', 'Leads go cold without an alert.', 'Os leads esfriam sem alerta.'),
                $t('Hay doble digitación.', 'There is double data entry.', 'Há dupla digitação.'),
                $t('Se compran herramientas sin proceso.', 'Tools are bought without a process.', 'Compram-se ferramentas sem processo.'),
            ],
            'jugada' => $t('Automatizar una tarea de alto volumen y alto impacto comercial.', 'Automate one high-volume, high-impact commercial task.', 'Automatizar uma tarefa de alto volume e alto impacto comercial.')],

        'marketing' => ['linea' => 'ataque', 'icon' => 'growth',
            'nombre' => $t('Marketing', 'Marketing', 'Marketing'),
            'pregunta' => $t('¿La empresa atrae oportunidades correctas o solo publica?', 'Does the company attract the right opportunities or just publish?', 'A empresa atrai oportunidades corretas ou só publica?'),
            'afirmaciones' => [
                $t('Tenemos una promesa clara.', 'We have a clear promise.', 'Temos uma promessa clara.'),
                $t('Sabemos qué dolor queremos instalar.', 'We know which pain we want to install.', 'Sabemos qual dor queremos instalar.'),
                $t('Publicamos con intención comercial.', 'We publish with commercial intent.', 'Publicamos com intenção comercial.'),
                $t('Medimos conversaciones, no solo likes.', 'We measure conversations, not just likes.', 'Medimos conversas, não só likes.'),
                $t('El contenido conecta con ventas.', 'Content connects with sales.', 'O conteúdo conecta com vendas.'),
            ],
            'senales' => [
                $t('Se publica sin estrategia.', 'Publishing without strategy.', 'Publica-se sem estratégia.'),
                $t('Se mide solo por likes.', 'Measured only by likes.', 'Mede-se só por likes.'),
                $t('El mensaje no diferencia.', 'The message doesn’t differentiate.', 'A mensagem não diferencia.'),
                $t('La audiencia no sabe qué vendes.', 'The audience doesn’t know what you sell.', 'A audiência não sabe o que você vende.'),
            ],
            'jugada' => $t('Crear una narrativa central y 3 series de contenido conectadas a venta.', 'Create a central narrative and 3 content series connected to sales.', 'Criar uma narrativa central e 3 séries de conteúdo conectadas a vendas.')],

        'ventas' => ['linea' => 'ataque', 'icon' => 'target',
            'nombre' => $t('Ventas', 'Sales', 'Vendas'),
            'pregunta' => $t('¿La empresa convierte interés en ingresos con sistema?', 'Does the company convert interest into revenue with a system?', 'A empresa converte interesse em receita com sistema?'),
            'afirmaciones' => [
                $t('Tenemos pipeline visible.', 'We have a visible pipeline.', 'Temos pipeline visível.'),
                $t('Medimos tasa de conversión.', 'We measure conversion rate.', 'Medimos taxa de conversão.'),
                $t('Hacemos seguimiento con tiempos definidos.', 'We follow up with defined timing.', 'Fazemos acompanhamento com prazos definidos.'),
                $t('Sabemos por qué se pierden oportunidades.', 'We know why opportunities are lost.', 'Sabemos por que as oportunidades se perdem.'),
                $t('Las propuestas se siguen hasta cierre.', 'Proposals are followed to close.', 'As propostas são acompanhadas até o fechamento.'),
            ],
            'senales' => [
                $t('No hay pipeline visible.', 'No visible pipeline.', 'Não há pipeline visível.'),
                $t('Los leads mueren en WhatsApp.', 'Leads die in WhatsApp.', 'Os leads morrem no WhatsApp.'),
                $t('Nadie sabe la tasa de cierre.', 'No one knows the close rate.', 'Ninguém sabe a taxa de fechamento.'),
                $t('La propuesta se envía y no se sigue.', 'Proposals are sent and not followed.', 'A proposta é enviada e não é acompanhada.'),
            ],
            'jugada' => $t('Implementar seguimiento comercial con estados, tiempos, alertas y responsables.', 'Implement sales follow-up with states, timing, alerts and owners.', 'Implementar acompanhamento comercial com estados, prazos, alertas e responsáveis.')],

        'experiencia' => ['linea' => 'ataque', 'icon' => 'people',
            'nombre' => $t('Experiencia', 'Experience', 'Experiência'),
            'pregunta' => $t('¿La empresa retiene, fideliza y multiplica clientes?', 'Does the company retain, build loyalty and multiply clients?', 'A empresa retém, fideliza e multiplica clientes?'),
            'afirmaciones' => [
                $t('Tenemos proceso de postventa.', 'We have a post-sale process.', 'Temos processo de pós-venda.'),
                $t('Medimos satisfacción.', 'We measure satisfaction.', 'Medimos satisfação.'),
                $t('Activamos recompras.', 'We activate repeat purchases.', 'Ativamos recompras.'),
                $t('Pedimos referidos.', 'We ask for referrals.', 'Pedimos indicações.'),
                $t('El cliente siente seguimiento después de comprar.', 'Clients feel follow-up after buying.', 'O cliente sente acompanhamento depois de comprar.'),
            ],
            'senales' => [
                $t('Se vende y luego se desaparece.', 'Sell and then disappear.', 'Vende-se e depois se desaparece.'),
                $t('No hay postventa.', 'No post-sale.', 'Não há pós-venda.'),
                $t('No se mide retención.', 'Retention isn’t measured.', 'Não se mede retenção.'),
                $t('El cliente satisfecho no se activa.', 'Happy clients aren’t activated.', 'O cliente satisfeito não é ativado.'),
            ],
            'jugada' => $t('Crear flujo de postventa: bienvenida, seguimiento, medición, recompra y referido.', 'Create a post-sale flow: welcome, follow-up, measurement, repurchase and referral.', 'Criar fluxo de pós-venda: boas-vindas, acompanhamento, medição, recompra e indicação.')],
    ],

    // ── Datos de ejemplo de la demo (por industria; escala 1–5 por zona) ─────
    // La demo muestra el instrumento con datos ficticios y lo declara.
    'demo' => [
        'default' => ['scores' => ['vision' => 3.2, 'direccion' => 2.8, 'finanzas' => 3.4, 'operacion' => 2.6, 'cultura' => 3.0, 'datos' => 2.2, 'procesos' => 2.8, 'automatizacion' => 1.8, 'marketing' => 3.6, 'ventas' => 2.4, 'experiencia' => 3.0],
            'marcador' => ['pipeline' => 47, 'conversion' => 18, 'rentabilidad' => 26, 'crecimiento' => 9]],
        'tecnologia' => ['scores' => ['vision' => 3.8, 'direccion' => 3.2, 'finanzas' => 3.0, 'operacion' => 3.4, 'cultura' => 3.6, 'datos' => 3.8, 'procesos' => 3.0, 'automatizacion' => 3.4, 'marketing' => 2.6, 'ventas' => 2.2, 'experiencia' => 2.8],
            'marcador' => ['pipeline' => 63, 'conversion' => 14, 'rentabilidad' => 31, 'crecimiento' => 17]],
        'retail' => ['scores' => ['vision' => 3.0, 'direccion' => 2.6, 'finanzas' => 3.6, 'operacion' => 3.2, 'cultura' => 2.8, 'datos' => 2.4, 'procesos' => 3.0, 'automatizacion' => 2.0, 'marketing' => 3.8, 'ventas' => 3.4, 'experiencia' => 2.2],
            'marcador' => ['pipeline' => 120, 'conversion' => 24, 'rentabilidad' => 18, 'crecimiento' => 7]],
        'financiero' => ['scores' => ['vision' => 3.6, 'direccion' => 3.4, 'finanzas' => 4.2, 'operacion' => 3.0, 'cultura' => 2.6, 'datos' => 3.4, 'procesos' => 3.6, 'automatizacion' => 2.8, 'marketing' => 2.4, 'ventas' => 2.8, 'experiencia' => 3.2],
            'marcador' => ['pipeline' => 38, 'conversion' => 21, 'rentabilidad' => 35, 'crecimiento' => 11]],
        'salud' => ['scores' => ['vision' => 2.8, 'direccion' => 2.4, 'finanzas' => 3.0, 'operacion' => 3.8, 'cultura' => 3.4, 'datos' => 2.0, 'procesos' => 3.2, 'automatizacion' => 1.6, 'marketing' => 2.6, 'ventas' => 2.6, 'experiencia' => 3.8],
            'marcador' => ['pipeline' => 52, 'conversion' => 28, 'rentabilidad' => 22, 'crecimiento' => 8]],
        'manufactura' => ['scores' => ['vision' => 3.0, 'direccion' => 3.2, 'finanzas' => 3.4, 'operacion' => 4.0, 'cultura' => 2.8, 'datos' => 2.6, 'procesos' => 3.8, 'automatizacion' => 2.4, 'marketing' => 2.0, 'ventas' => 2.4, 'experiencia' => 2.8],
            'marcador' => ['pipeline' => 29, 'conversion' => 32, 'rentabilidad' => 24, 'crecimiento' => 6]],
        'profesionales' => ['scores' => ['vision' => 3.4, 'direccion' => 2.8, 'finanzas' => 2.8, 'operacion' => 3.0, 'cultura' => 3.2, 'datos' => 2.4, 'procesos' => 2.6, 'automatizacion' => 2.0, 'marketing' => 3.2, 'ventas' => 3.0, 'experiencia' => 3.4],
            'marcador' => ['pipeline' => 34, 'conversion' => 26, 'rentabilidad' => 38, 'crecimiento' => 12]],
    ],

    // ── Correo postdiagnóstico (copy del framework, por idioma) ──────────────
    'email' => [
        'es' => ['asunto' => 'Tu Tablero de Crecimiento ya empezó a hablar',
            'cuerpo' => '<p>Hola, {nombre}.</p><p>Gracias por completar el Diagnóstico Tablero de Crecimiento.</p><p>Este diagnóstico nos ayuda a leer dónde tu empresa puede estar perdiendo fuerza: estrategia, operación, datos, automatización, ventas o experiencia.</p><p>El siguiente paso es revisar tu resultado y detectar tu primera jugada.</p><p><a href="{enlace}">Agenda aquí tu lectura estratégica</a>.</p><p>Si tu empresa está vendiendo, pero no está creciendo como debería, probablemente no le falta movimiento.</p><p>Le falta tablero.</p><p>Tonny Dager<br>ExperientIA</p>'],
        'en' => ['asunto' => 'Your Growth Board has started to speak',
            'cuerpo' => '<p>Hi, {nombre}.</p><p>Thanks for completing the Growth Board Diagnostic.</p><p>It helps us read where your company may be losing strength: strategy, operations, data, automation, sales or experience.</p><p>The next step is to review your result and spot your first play.</p><p><a href="{enlace}">Book your strategic reading here</a>.</p><p>If your company is selling but not growing as it should, it probably doesn’t lack movement.</p><p>It lacks a board.</p><p>Tonny Dager<br>ExperientIA</p>'],
        'pt' => ['asunto' => 'Seu Painel de Crescimento já começou a falar',
            'cuerpo' => '<p>Olá, {nombre}.</p><p>Obrigado por completar o Diagnóstico Painel de Crescimento.</p><p>Ele nos ajuda a ler onde sua empresa pode estar perdendo força: estratégia, operação, dados, automação, vendas ou experiência.</p><p>O próximo passo é revisar seu resultado e detectar sua primeira jogada.</p><p><a href="{enlace}">Agende aqui sua leitura estratégica</a>.</p><p>Se sua empresa está vendendo, mas não está crescendo como deveria, provavelmente não falta movimento.</p><p>Falta painel.</p><p>Tonny Dager<br>ExperientIA</p>'],
    ],
];
