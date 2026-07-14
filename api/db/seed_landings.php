<?php
/**
 * Semilla de landings de conversión (trilingües) por solución (skey) y producto
 * (slug del nombre ES). Es el punto de partida editable desde el admin: cada
 * ítem recibe una landing rica que luego se ajusta sin tocar código.
 *
 * Estructura de cada landing:
 *   promesa/antes/despues/cta  → {es,en,pt}
 *   beneficios  → [{icon, t:{...}, x:{...}}]
 *   pasos       → [{t:{...}, x:{...}}]
 *   entregables → {es:[...], en:[...], pt:[...]}
 *   metricas    → [{valor, suf?, label:{...}}]
 *   faqs        → [{q:{...}, a:{...}}]
 *   oferta      → {on, badge:{...}, titulo:{...}, texto:{...}, cta:{...}} (opcional)
 *   testimonio  → {on, quote:{...}, autor, cargo:{...}} (opcional)
 *   proof_casos → bool (mostrar franja de casos como prueba social)
 */

$solutions = [
    'estrategia' => [
        'promesa' => ['es' => 'De “deberíamos usar IA” a un plan de inversión con retornos definidos.', 'en' => 'From “we should use AI” to an investment plan with defined returns.', 'pt' => 'De “deveríamos usar IA” a um plano de investimento com retornos definidos.'],
        'antes' => ['es' => 'Comités que discuten IA en abstracto, pilotos sueltos que no escalan y presupuesto que se diluye sin retorno claro.', 'en' => 'Committees debating AI in the abstract, isolated pilots that don’t scale and budget that dilutes with no clear return.', 'pt' => 'Comitês que discutem IA no abstrato, pilotos soltos que não escalam e orçamento que se dilui sem retorno claro.'],
        'despues' => ['es' => 'Una hoja de ruta priorizada por impacto en el P&L, con dueños, hitos y métricas que la dirección defiende ante la junta.', 'en' => 'A roadmap prioritized by P&L impact, with owners, milestones and metrics leadership can defend to the board.', 'pt' => 'Um roadmap priorizado por impacto no resultado, com donos, marcos e métricas que a diretoria defende ao conselho.'],
        'beneficios' => [
            ['icon' => 'target', 't' => ['es' => 'Prioridad por impacto', 'en' => 'Priority by impact', 'pt' => 'Prioridade por impacto'], 'x' => ['es' => 'Cada iniciativa se ordena por retorno real y viabilidad, no por moda.', 'en' => 'Every initiative is ranked by real return and feasibility, not hype.', 'pt' => 'Cada iniciativa é ordenada por retorno real e viabilidade, não por moda.']],
            ['icon' => 'shield', 't' => ['es' => 'Decisiones defendibles', 'en' => 'Defensible decisions', 'pt' => 'Decisões defensáveis'], 'x' => ['es' => 'Un caso de inversión que resiste las preguntas difíciles del comité y la junta.', 'en' => 'An investment case that withstands the tough questions from committee and board.', 'pt' => 'Um caso de investimento que resiste às perguntas difíceis do comitê e do conselho.']],
            ['icon' => 'growth', 't' => ['es' => 'Del plan a la ejecución', 'en' => 'From plan to execution', 'pt' => 'Do plano à execução'], 'x' => ['es' => 'Roadmap con dueños y métricas: la estrategia se ejecuta, no se archiva.', 'en' => 'A roadmap with owners and metrics: strategy gets executed, not filed away.', 'pt' => 'Roadmap com donos e métricas: a estratégia se executa, não se arquiva.']],
        ],
        'pasos' => [
            ['t' => ['es' => 'Diagnóstico de madurez', 'en' => 'Maturity diagnostic', 'pt' => 'Diagnóstico de maturidade'], 'x' => ['es' => 'Datos, procesos y talento evaluados en semanas, no meses.', 'en' => 'Data, processes and talent assessed in weeks, not months.', 'pt' => 'Dados, processos e talento avaliados em semanas, não meses.']],
            ['t' => ['es' => 'Priorización por P&L', 'en' => 'P&L prioritization', 'pt' => 'Priorização por resultado'], 'x' => ['es' => 'Iniciativas ordenadas por impacto financiero y viabilidad real.', 'en' => 'Initiatives ranked by financial impact and real feasibility.', 'pt' => 'Iniciativas ordenadas por impacto financeiro e viabilidade real.']],
            ['t' => ['es' => 'Roadmap ejecutivo', 'en' => 'Executive roadmap', 'pt' => 'Roadmap executivo'], 'x' => ['es' => 'Hitos, dueños y métricas listos para presentar a la junta.', 'en' => 'Milestones, owners and metrics ready to present to the board.', 'pt' => 'Marcos, donos e métricas prontos para apresentar ao conselho.']],
        ],
        'entregables' => [
            'es' => ['Diagnóstico de madurez en IA (datos, procesos, talento)', 'Mapa de oportunidades priorizado por impacto en P&L', 'Roadmap ejecutivo a 12 meses con hitos y dueños', 'Caso de inversión y métricas de seguimiento', 'Sesión de alineación con el comité directivo'],
            'en' => ['AI maturity diagnostic (data, processes, talent)', 'Opportunity map prioritized by P&L impact', '12-month executive roadmap with milestones and owners', 'Investment case and tracking metrics', 'Alignment session with the leadership committee'],
            'pt' => ['Diagnóstico de maturidade em IA (dados, processos, talento)', 'Mapa de oportunidades priorizado por impacto no resultado', 'Roadmap executivo de 12 meses com marcos e donos', 'Caso de investimento e métricas de acompanhamento', 'Sessão de alinhamento com o comitê diretivo'],
        ],
        'metricas' => [
            ['valor' => '12', 'suf' => ' meses', 'label' => ['es' => 'de roadmap accionable', 'en' => 'of actionable roadmap', 'pt' => 'de roadmap acionável']],
            ['valor' => '100', 'suf' => '%', 'label' => ['es' => 'iniciativas con dueño y métrica', 'en' => 'initiatives with owner and metric', 'pt' => 'iniciativas com dono e métrica']],
            ['valor' => '1', 'label' => ['es' => 'fuente de verdad para decidir', 'en' => 'source of truth to decide', 'pt' => 'fonte de verdade para decidir']],
        ],
        'faqs' => [
            ['q' => ['es' => '¿Necesitamos los datos ordenados antes de empezar?', 'en' => 'Do we need our data in order before starting?', 'pt' => 'Precisamos dos dados organizados antes de começar?'], 'a' => ['es' => 'No. El diagnóstico parte de donde estás hoy; ordenar los datos es parte del roadmap, priorizado por lo que de verdad mueve el negocio.', 'en' => 'No. The diagnostic starts where you are today; organizing the data is part of the roadmap, prioritized by what truly moves the business.', 'pt' => 'Não. O diagnóstico parte de onde você está hoje; organizar os dados é parte do roadmap, priorizado pelo que realmente move o negócio.']],
            ['q' => ['es' => '¿Es una consultoría que entrega un PDF y se va?', 'en' => 'Is this a consultancy that delivers a PDF and leaves?', 'pt' => 'É uma consultoria que entrega um PDF e vai embora?'], 'a' => ['es' => 'No. Entregamos un plan con dueños y métricas y acompañamos la ejecución. El objetivo es retorno, no un documento.', 'en' => 'No. We deliver a plan with owners and metrics and support execution. The goal is return, not a document.', 'pt' => 'Não. Entregamos um plano com donos e métricas e acompanhamos a execução. O objetivo é retorno, não um documento.']],
        ],
        'cta' => ['es' => 'Quiero mi roadmap de IA', 'en' => 'I want my AI roadmap', 'pt' => 'Quero meu roadmap de IA'],
        'proof_casos' => true,
    ],
    'automatizacion' => [
        'promesa' => ['es' => 'Recupera horas senior y escala la operación sin escalar la nómina.', 'en' => 'Recover senior hours and scale operations without scaling headcount.', 'pt' => 'Recupere horas sênior e escale a operação sem escalar a folha.'],
        'antes' => ['es' => 'Procesos manuales que consumen a tu mejor gente, generan errores y frenan el crecimiento.', 'en' => 'Manual processes that consume your best people, cause errors and slow growth.', 'pt' => 'Processos manuais que consomem sua melhor gente, geram erros e freiam o crescimento.'],
        'despues' => ['es' => 'Flujos automatizados y monitoreados que liberan al equipo para lo estratégico, con indicadores de eficiencia a la vista.', 'en' => 'Automated, monitored flows that free the team for strategy, with efficiency indicators in plain sight.', 'pt' => 'Fluxos automatizados e monitorados que liberam a equipe para o estratégico, com indicadores de eficiência à vista.'],
        'beneficios' => [
            ['icon' => 'gear', 't' => ['es' => 'Horas senior liberadas', 'en' => 'Senior hours freed', 'pt' => 'Horas sênior liberadas'], 'x' => ['es' => 'Tu talento deja de operar planillas y vuelve a la estrategia.', 'en' => 'Your talent stops running spreadsheets and returns to strategy.', 'pt' => 'Seu talento deixa de operar planilhas e volta à estratégia.']],
            ['icon' => 'shield', 't' => ['es' => 'Menos errores, más control', 'en' => 'Fewer errors, more control', 'pt' => 'Menos erros, mais controle'], 'x' => ['es' => 'Procesos consistentes, auditables y monitoreados en vivo.', 'en' => 'Consistent, auditable, live-monitored processes.', 'pt' => 'Processos consistentes, auditáveis e monitorados ao vivo.']],
            ['icon' => 'growth', 't' => ['es' => 'Escala sin más nómina', 'en' => 'Scale without more headcount', 'pt' => 'Escale sem mais folha'], 'x' => ['es' => 'La operación crece con la demanda, no con el headcount.', 'en' => 'Operations grow with demand, not with headcount.', 'pt' => 'A operação cresce com a demanda, não com o headcount.']],
        ],
        'pasos' => [
            ['t' => ['es' => 'Mapeo de procesos', 'en' => 'Process mapping', 'pt' => 'Mapeamento de processos'], 'x' => ['es' => 'Identificamos cuellos de botella y el retorno de automatizar cada uno.', 'en' => 'We identify bottlenecks and the return of automating each one.', 'pt' => 'Identificamos gargalos e o retorno de automatizar cada um.']],
            ['t' => ['es' => 'Automatización con IA', 'en' => 'AI automation', 'pt' => 'Automação com IA'], 'x' => ['es' => 'Implementamos donde el retorno lo justifica, con datos reales.', 'en' => 'We implement where the return justifies it, with real data.', 'pt' => 'Implementamos onde o retorno justifica, com dados reais.']],
            ['t' => ['es' => 'Operación monitoreada', 'en' => 'Monitored operation', 'pt' => 'Operação monitorada'], 'x' => ['es' => 'Indicadores de eficiencia y mejora continua desde el día uno.', 'en' => 'Efficiency indicators and continuous improvement from day one.', 'pt' => 'Indicadores de eficiência e melhoria contínua desde o dia um.']],
        ],
        'entregables' => [
            'es' => ['Mapa de procesos críticos con potencial de automatización', 'Automatizaciones implementadas y documentadas', 'Tablero de eficiencia operativa', 'Protocolo de monitoreo y mejora continua', 'Transferencia y capacitación al equipo'],
            'en' => ['Map of critical processes with automation potential', 'Automations implemented and documented', 'Operational efficiency dashboard', 'Monitoring and continuous-improvement protocol', 'Handover and team training'],
            'pt' => ['Mapa de processos críticos com potencial de automação', 'Automações implementadas e documentadas', 'Painel de eficiência operacional', 'Protocolo de monitoramento e melhoria contínua', 'Transferência e capacitação da equipe'],
        ],
        'metricas' => [
            ['valor' => '24/7', 'label' => ['es' => 'operación monitoreada', 'en' => 'monitored operation', 'pt' => 'operação monitorada']],
            ['valor' => '100', 'suf' => '%', 'label' => ['es' => 'procesos auditables', 'en' => 'auditable processes', 'pt' => 'processos auditáveis']],
            ['valor' => '1', 'label' => ['es' => 'tablero de eficiencia', 'en' => 'efficiency dashboard', 'pt' => 'painel de eficiência']],
        ],
        'faqs' => [
            ['q' => ['es' => '¿Tendremos que cambiar todos nuestros sistemas?', 'en' => 'Will we have to change all our systems?', 'pt' => 'Teremos que trocar todos os nossos sistemas?'], 'a' => ['es' => 'No. Automatizamos sobre lo que ya usas e integramos; reemplazar es la excepción, no la regla.', 'en' => 'No. We automate on top of what you already use and integrate; replacing is the exception, not the rule.', 'pt' => 'Não. Automatizamos sobre o que você já usa e integramos; substituir é a exceção, não a regra.']],
            ['q' => ['es' => '¿La automatización reemplaza a mi equipo?', 'en' => 'Does automation replace my team?', 'pt' => 'A automação substitui minha equipe?'], 'a' => ['es' => 'No. Libera a tu equipo de lo repetitivo para que se enfoque en lo que genera valor y criterio.', 'en' => 'No. It frees your team from the repetitive so they can focus on what creates value and judgment.', 'pt' => 'Não. Libera sua equipe do repetitivo para focar no que gera valor e critério.']],
        ],
        'cta' => ['es' => 'Quiero automatizar mi operación', 'en' => 'I want to automate my operation', 'pt' => 'Quero automatizar minha operação'],
        'proof_casos' => true,
    ],
    'datos' => [
        'promesa' => ['es' => 'Una sola fuente de verdad para decidir en horas, no en semanas.', 'en' => 'A single source of truth to decide in hours, not weeks.', 'pt' => 'Uma única fonte de verdade para decidir em horas, não em semanas.'],
        'antes' => ['es' => 'Datos dispersos en sistemas que no conversan y reportes manuales que llegan tarde a las decisiones.', 'en' => 'Data scattered across systems that don’t talk, and manual reports that arrive too late for decisions.', 'pt' => 'Dados dispersos em sistemas que não conversam e relatórios manuais que chegam tarde às decisões.'],
        'despues' => ['es' => 'Tableros ejecutivos con indicadores accionables y modelos que anticipan, sobre una arquitectura simple.', 'en' => 'Executive dashboards with actionable indicators and models that anticipate, on a simple architecture.', 'pt' => 'Painéis executivos com indicadores acionáveis e modelos que antecipam, sobre uma arquitetura simples.'],
        'beneficios' => [
            ['icon' => 'analitica', 't' => ['es' => 'Una fuente de verdad', 'en' => 'One source of truth', 'pt' => 'Uma fonte de verdade'], 'x' => ['es' => 'Todos deciden sobre los mismos números, sin discutir de dónde salieron.', 'en' => 'Everyone decides on the same numbers, no arguing where they came from.', 'pt' => 'Todos decidem sobre os mesmos números, sem discutir de onde saíram.']],
            ['icon' => 'ia', 't' => ['es' => 'Anticipación con IA', 'en' => 'Anticipation with AI', 'pt' => 'Antecipação com IA'], 'x' => ['es' => 'Modelos predictivos donde adelantarse vale dinero.', 'en' => 'Predictive models where getting ahead is worth money.', 'pt' => 'Modelos preditivos onde antecipar vale dinheiro.']],
            ['icon' => 'growth', 't' => ['es' => 'Decisiones en horas', 'en' => 'Decisions in hours', 'pt' => 'Decisões em horas'], 'x' => ['es' => 'De reportes mensuales a indicadores vivos.', 'en' => 'From monthly reports to live indicators.', 'pt' => 'De relatórios mensais a indicadores vivos.']],
        ],
        'pasos' => [
            ['t' => ['es' => 'Consolidación de fuentes', 'en' => 'Source consolidation', 'pt' => 'Consolidação de fontes'], 'x' => ['es' => 'Unificamos tus datos en una arquitectura simple y confiable.', 'en' => 'We unify your data into a simple, reliable architecture.', 'pt' => 'Unificamos seus dados em uma arquitetura simples e confiável.']],
            ['t' => ['es' => 'Tableros accionables', 'en' => 'Actionable dashboards', 'pt' => 'Painéis acionáveis'], 'x' => ['es' => 'Indicadores que dicen qué hacer, no solo qué pasó.', 'en' => 'Indicators that say what to do, not just what happened.', 'pt' => 'Indicadores que dizem o que fazer, não só o que aconteceu.']],
            ['t' => ['es' => 'Modelos predictivos', 'en' => 'Predictive models', 'pt' => 'Modelos preditivos'], 'x' => ['es' => 'Donde anticipar el resultado tiene impacto financiero.', 'en' => 'Where anticipating the outcome has financial impact.', 'pt' => 'Onde antecipar o resultado tem impacto financeiro.']],
        ],
        'entregables' => [
            'es' => ['Arquitectura de datos consolidada', 'Tableros ejecutivos por área', 'Indicadores accionables con dueño', 'Modelos predictivos priorizados', 'Gobierno de métricas y ritual de revisión'],
            'en' => ['Consolidated data architecture', 'Executive dashboards by area', 'Actionable indicators with owners', 'Prioritized predictive models', 'Metric governance and review ritual'],
            'pt' => ['Arquitetura de dados consolidada', 'Painéis executivos por área', 'Indicadores acionáveis com dono', 'Modelos preditivos priorizados', 'Governança de métricas e ritual de revisão'],
        ],
        'metricas' => [
            ['valor' => '1', 'label' => ['es' => 'fuente de verdad', 'en' => 'source of truth', 'pt' => 'fonte de verdade']],
            ['valor' => '360', 'suf' => '°', 'label' => ['es' => 'visibilidad del negocio', 'en' => 'business visibility', 'pt' => 'visibilidade do negócio']],
            ['valor' => '0', 'label' => ['es' => 'reportes manuales tardíos', 'en' => 'late manual reports', 'pt' => 'relatórios manuais atrasados']],
        ],
        'faqs' => [
            ['q' => ['es' => '¿Sirve si tenemos datos en Excel y varios sistemas?', 'en' => 'Does it work if our data is in Excel and several systems?', 'pt' => 'Funciona se tivermos dados em Excel e vários sistemas?'], 'a' => ['es' => 'Sí. Justamente ahí empezamos: consolidamos lo disperso en una arquitectura simple antes de sofisticar.', 'en' => 'Yes. That’s exactly where we start: we consolidate the scattered into a simple architecture before sophisticating.', 'pt' => 'Sim. É exatamente aí que começamos: consolidamos o disperso em uma arquitetura simples antes de sofisticar.']],
            ['q' => ['es' => '¿Cuánto tarda en verse el primer tablero?', 'en' => 'How long until the first dashboard is live?', 'pt' => 'Quanto tempo até o primeiro painel?'], 'a' => ['es' => 'Priorizamos un tablero de alto impacto primero, para que la dirección vea valor temprano y luego escalamos.', 'en' => 'We prioritize one high-impact dashboard first, so leadership sees value early, then we scale.', 'pt' => 'Priorizamos um painel de alto impacto primeiro, para a diretoria ver valor cedo, e depois escalamos.']],
        ],
        'cta' => ['es' => 'Quiero ver mis datos claros', 'en' => 'I want my data clear', 'pt' => 'Quero ver meus dados claros'],
        'proof_casos' => true,
    ],
    'growth' => [
        'promesa' => ['es' => 'Convierte el crecimiento en un sistema que compone, no en campañas sueltas.', 'en' => 'Turn growth into a compounding system, not isolated campaigns.', 'pt' => 'Transforme o crescimento em um sistema que compõe, não em campanhas soltas.'],
        'antes' => ['es' => 'Adquisición cara, retención frágil y una experiencia que no aprende de sus propios datos.', 'en' => 'Expensive acquisition, fragile retention and an experience that doesn’t learn from its own data.', 'pt' => 'Aquisição cara, retenção frágil e uma experiência que não aprende com os próprios dados.'],
        'despues' => ['es' => 'Funnels instrumentados de punta a punta, personalización con IA y experimentación continua con gobierno de métricas.', 'en' => 'End-to-end instrumented funnels, AI personalization and continuous experimentation with metric governance.', 'pt' => 'Funis instrumentados de ponta a ponta, personalização com IA e experimentação contínua com governança de métricas.'],
        'beneficios' => [
            ['icon' => 'growth', 't' => ['es' => 'Crecimiento que compone', 'en' => 'Compounding growth', 'pt' => 'Crescimento que compõe'], 'x' => ['es' => 'Cada experimento deja aprendizaje y activo, no solo gasto.', 'en' => 'Every experiment leaves learning and an asset, not just spend.', 'pt' => 'Cada experimento deixa aprendizado e ativo, não só gasto.']],
            ['icon' => 'ia', 't' => ['es' => 'Personalización con IA', 'en' => 'AI personalization', 'pt' => 'Personalização com IA'], 'x' => ['es' => 'Scoring y mensajes relevantes para cada segmento.', 'en' => 'Scoring and relevant messages for each segment.', 'pt' => 'Scoring e mensagens relevantes para cada segmento.']],
            ['icon' => 'analitica', 't' => ['es' => 'Funnel medido punta a punta', 'en' => 'Funnel measured end to end', 'pt' => 'Funil medido ponta a ponta'], 'x' => ['es' => 'Sabes dónde ganas y dónde pierdes clientes.', 'en' => 'You know where you win and where you lose customers.', 'pt' => 'Você sabe onde ganha e onde perde clientes.']],
        ],
        'pasos' => [
            ['t' => ['es' => 'Instrumentación del funnel', 'en' => 'Funnel instrumentation', 'pt' => 'Instrumentação do funil'], 'x' => ['es' => 'Medimos cada etapa de adquisición a retención.', 'en' => 'We measure every stage from acquisition to retention.', 'pt' => 'Medimos cada etapa da aquisição à retenção.']],
            ['t' => ['es' => 'Personalización y scoring', 'en' => 'Personalization and scoring', 'pt' => 'Personalização e scoring'], 'x' => ['es' => 'IA aplicada a segmentar y priorizar lo que convierte.', 'en' => 'AI applied to segment and prioritize what converts.', 'pt' => 'IA aplicada a segmentar e priorizar o que converte.']],
            ['t' => ['es' => 'Experimentación continua', 'en' => 'Continuous experimentation', 'pt' => 'Experimentação contínua'], 'x' => ['es' => 'Ciclos de mejora con gobierno de métricas.', 'en' => 'Improvement cycles with metric governance.', 'pt' => 'Ciclos de melhoria com governança de métricas.']],
        ],
        'entregables' => [
            'es' => ['Funnel instrumentado de adquisición a retención', 'Modelo de scoring y segmentación', 'Motor de experimentación y roadmap de tests', 'Tablero de métricas de crecimiento', 'Ritual de revisión y priorización'],
            'en' => ['Instrumented funnel from acquisition to retention', 'Scoring and segmentation model', 'Experimentation engine and test roadmap', 'Growth metrics dashboard', 'Review and prioritization ritual'],
            'pt' => ['Funil instrumentado da aquisição à retenção', 'Modelo de scoring e segmentação', 'Motor de experimentação e roadmap de testes', 'Painel de métricas de crescimento', 'Ritual de revisão e priorização'],
        ],
        'metricas' => [
            ['valor' => '100', 'suf' => '%', 'label' => ['es' => 'del funnel instrumentado', 'en' => 'of the funnel instrumented', 'pt' => 'do funil instrumentado']],
            ['valor' => '1:1', 'label' => ['es' => 'personalización por segmento', 'en' => 'personalization per segment', 'pt' => 'personalização por segmento']],
            ['valor' => '∞', 'label' => ['es' => 'ciclos de experimentación', 'en' => 'experimentation cycles', 'pt' => 'ciclos de experimentação']],
        ],
        'faqs' => [
            ['q' => ['es' => '¿Esto es una agencia de marketing?', 'en' => 'Is this a marketing agency?', 'pt' => 'Isto é uma agência de marketing?'], 'a' => ['es' => 'No. Construimos el sistema de crecimiento —datos, funnel y experimentación— para que tus campañas rindan más, las hagas con quien las hagas.', 'en' => 'No. We build the growth system —data, funnel and experimentation— so your campaigns perform better, whoever runs them.', 'pt' => 'Não. Construímos o sistema de crescimento —dados, funil e experimentação— para suas campanhas renderem mais, com quem quer que as faça.']],
            ['q' => ['es' => '¿Funciona en B2B con ciclos largos?', 'en' => 'Does it work in B2B with long cycles?', 'pt' => 'Funciona em B2B com ciclos longos?'], 'a' => ['es' => 'Sí. Instrumentamos el funnel completo, incluido el ciclo comercial largo, y priorizamos las palancas de mayor impacto.', 'en' => 'Yes. We instrument the full funnel, including the long sales cycle, and prioritize the highest-impact levers.', 'pt' => 'Sim. Instrumentamos o funil completo, incluindo o ciclo comercial longo, e priorizamos as alavancas de maior impacto.']],
        ],
        'cta' => ['es' => 'Quiero un sistema de crecimiento', 'en' => 'I want a growth system', 'pt' => 'Quero um sistema de crescimento'],
        'proof_casos' => true,
    ],
];

$products = [
    'diagnostico-ejecutivo' => [
        'promesa' => ['es' => 'En una sesión, claridad sobre dónde la IA mueve tu P&L primero.', 'en' => 'In one session, clarity on where AI moves your P&L first.', 'pt' => 'Em uma sessão, clareza sobre onde a IA move seu resultado primeiro.'],
        'antes' => ['es' => 'Muchas ideas de IA, cero certeza sobre por dónde empezar sin quemar presupuesto.', 'en' => 'Many AI ideas, zero certainty on where to start without burning budget.', 'pt' => 'Muitas ideias de IA, zero certeza sobre por onde começar sem queimar orçamento.'],
        'despues' => ['es' => 'Un mapa claro de las 3 oportunidades de mayor retorno y el primer paso concreto.', 'en' => 'A clear map of the top 3 highest-return opportunities and the concrete first step.', 'pt' => 'Um mapa claro das 3 oportunidades de maior retorno e o primeiro passo concreto.'],
        'beneficios' => [
            ['icon' => 'target', 't' => ['es' => 'Foco inmediato', 'en' => 'Immediate focus', 'pt' => 'Foco imediato'], 'x' => ['es' => 'Sales de la sesión sabiendo exactamente por dónde empezar.', 'en' => 'You leave the session knowing exactly where to start.', 'pt' => 'Você sai da sessão sabendo exatamente por onde começar.']],
            ['icon' => 'analitica', 't' => ['es' => 'Priorizado por retorno', 'en' => 'Prioritized by return', 'pt' => 'Priorizado por retorno'], 'x' => ['es' => 'Oportunidades ordenadas por impacto y facilidad de ejecución.', 'en' => 'Opportunities ranked by impact and ease of execution.', 'pt' => 'Oportunidades ordenadas por impacto e facilidade de execução.']],
            ['icon' => 'shield', 't' => ['es' => 'Sin compromiso', 'en' => 'No commitment', 'pt' => 'Sem compromisso'], 'x' => ['es' => 'Un diagnóstico honesto: si no hay caso, te lo decimos.', 'en' => 'An honest diagnostic: if there’s no case, we tell you.', 'pt' => 'Um diagnóstico honesto: se não há caso, nós dizemos.']],
        ],
        'pasos' => [
            ['t' => ['es' => 'Conversación estructurada', 'en' => 'Structured conversation', 'pt' => 'Conversa estruturada'], 'x' => ['es' => 'Entendemos tu negocio, metas y frenos actuales.', 'en' => 'We understand your business, goals and current blockers.', 'pt' => 'Entendemos seu negócio, metas e freios atuais.']],
            ['t' => ['es' => 'Mapa de oportunidades', 'en' => 'Opportunity map', 'pt' => 'Mapa de oportunidades'], 'x' => ['es' => 'Detectamos dónde la IA y la automatización rinden primero.', 'en' => 'We spot where AI and automation pay off first.', 'pt' => 'Detectamos onde a IA e a automação rendem primeiro.']],
            ['t' => ['es' => 'Primer paso concreto', 'en' => 'Concrete first step', 'pt' => 'Primeiro passo concreto'], 'x' => ['es' => 'Te vas con una recomendación accionable, no con teoría.', 'en' => 'You leave with an actionable recommendation, not theory.', 'pt' => 'Você sai com uma recomendação acionável, não com teoria.']],
        ],
        'entregables' => [
            'es' => ['Sesión ejecutiva de diagnóstico', 'Mapa de las 3 oportunidades de mayor retorno', 'Recomendación del primer paso concreto', 'Estimación de impacto y esfuerzo'],
            'en' => ['Executive diagnostic session', 'Map of the top 3 highest-return opportunities', 'Recommendation of the concrete first step', 'Impact and effort estimate'],
            'pt' => ['Sessão executiva de diagnóstico', 'Mapa das 3 oportunidades de maior retorno', 'Recomendação do primeiro passo concreto', 'Estimativa de impacto e esforço'],
        ],
        'metricas' => [
            ['valor' => '1', 'label' => ['es' => 'sesión para tener claridad', 'en' => 'session to gain clarity', 'pt' => 'sessão para ter clareza']],
            ['valor' => '3', 'label' => ['es' => 'oportunidades priorizadas', 'en' => 'prioritized opportunities', 'pt' => 'oportunidades priorizadas']],
            ['valor' => '360', 'suf' => '°', 'label' => ['es' => 'de tu negocio en revisión', 'en' => 'of your business reviewed', 'pt' => 'do seu negócio em revisão']],
        ],
        'faqs' => [
            ['q' => ['es' => '¿Es una llamada de ventas disfrazada?', 'en' => 'Is this a disguised sales call?', 'pt' => 'É uma ligação de vendas disfarçada?'], 'a' => ['es' => 'No. Es un diagnóstico real con valor propio. Si no vemos un caso claro para trabajar juntos, te lo diremos.', 'en' => 'No. It’s a real diagnostic with value of its own. If we don’t see a clear case to work together, we’ll tell you.', 'pt' => 'Não. É um diagnóstico real com valor próprio. Se não virmos um caso claro para trabalhar juntos, diremos.']],
        ],
        'cta' => ['es' => 'Quiero mi diagnóstico', 'en' => 'I want my diagnostic', 'pt' => 'Quero meu diagnóstico'],
        'proof_casos' => true,
    ],
    'programas-de-automatizacion' => [
        'promesa' => ['es' => 'Automatización implementada, no diapositivas: procesos que ya corren solos.', 'en' => 'Automation implemented, not slides: processes that already run themselves.', 'pt' => 'Automação implementada, não slides: processos que já rodam sozinhos.'],
        'antes' => ['es' => 'Equipos atados a tareas repetitivas y procesos que no escalan con la demanda.', 'en' => 'Teams tied to repetitive tasks and processes that don’t scale with demand.', 'pt' => 'Equipes presas a tarefas repetitivas e processos que não escalam com a demanda.'],
        'despues' => ['es' => 'Flujos en producción, monitoreados y documentados, con el equipo capacitado para operarlos.', 'en' => 'Flows in production, monitored and documented, with the team trained to operate them.', 'pt' => 'Fluxos em produção, monitorados e documentados, com a equipe capacitada para operá-los.'],
        'beneficios' => [
            ['icon' => 'gear', 't' => ['es' => 'Ejecución real', 'en' => 'Real execution', 'pt' => 'Execução real'], 'x' => ['es' => 'Salimos con procesos funcionando, no con un plan en el cajón.', 'en' => 'We leave with working processes, not a plan in a drawer.', 'pt' => 'Saímos com processos funcionando, não com um plano na gaveta.']],
            ['icon' => 'growth', 't' => ['es' => 'Retorno medible', 'en' => 'Measurable return', 'pt' => 'Retorno mensurável'], 'x' => ['es' => 'Cada automatización se elige por su impacto en eficiencia.', 'en' => 'Each automation is chosen by its efficiency impact.', 'pt' => 'Cada automação é escolhida pelo seu impacto em eficiência.']],
            ['icon' => 'people', 't' => ['es' => 'Equipo autónomo', 'en' => 'Autonomous team', 'pt' => 'Equipe autônoma'], 'x' => ['es' => 'Transferimos el conocimiento para que no dependas de nadie.', 'en' => 'We transfer the knowledge so you depend on no one.', 'pt' => 'Transferimos o conhecimento para você não depender de ninguém.']],
        ],
        'pasos' => [
            ['t' => ['es' => 'Priorización de procesos', 'en' => 'Process prioritization', 'pt' => 'Priorização de processos'], 'x' => ['es' => 'Elegimos los flujos con mayor retorno de automatización.', 'en' => 'We pick the flows with the highest automation return.', 'pt' => 'Escolhemos os fluxos com maior retorno de automação.']],
            ['t' => ['es' => 'Construcción e integración', 'en' => 'Build and integrate', 'pt' => 'Construção e integração'], 'x' => ['es' => 'Implementamos sobre tus herramientas actuales.', 'en' => 'We implement on top of your current tools.', 'pt' => 'Implementamos sobre suas ferramentas atuais.']],
            ['t' => ['es' => 'Puesta en producción', 'en' => 'Go live', 'pt' => 'Colocação em produção'], 'x' => ['es' => 'Monitoreo, documentación y capacitación al equipo.', 'en' => 'Monitoring, documentation and team training.', 'pt' => 'Monitoramento, documentação e capacitação da equipe.']],
        ],
        'entregables' => [
            'es' => ['Procesos automatizados en producción', 'Integraciones con tus herramientas actuales', 'Tablero de eficiencia y monitoreo', 'Documentación y capacitación del equipo'],
            'en' => ['Automated processes in production', 'Integrations with your current tools', 'Efficiency and monitoring dashboard', 'Documentation and team training'],
            'pt' => ['Processos automatizados em produção', 'Integrações com suas ferramentas atuais', 'Painel de eficiência e monitoramento', 'Documentação e capacitação da equipe'],
        ],
        'metricas' => [
            ['valor' => '24/7', 'label' => ['es' => 'procesos operando', 'en' => 'processes running', 'pt' => 'processos operando']],
            ['valor' => '100', 'suf' => '%', 'label' => ['es' => 'documentado y transferido', 'en' => 'documented and transferred', 'pt' => 'documentado e transferido']],
            ['valor' => '1', 'label' => ['es' => 'tablero de eficiencia', 'en' => 'efficiency dashboard', 'pt' => 'painel de eficiência']],
        ],
        'faqs' => [
            ['q' => ['es' => '¿Con qué herramientas trabajan?', 'en' => 'Which tools do you work with?', 'pt' => 'Com quais ferramentas vocês trabalham?'], 'a' => ['es' => 'Nos adaptamos a tu stack. Integramos sobre lo que ya usas y solo proponemos cambios cuando el retorno lo justifica.', 'en' => 'We adapt to your stack. We integrate on what you already use and only propose changes when the return justifies it.', 'pt' => 'Nos adaptamos ao seu stack. Integramos sobre o que você já usa e só propomos mudanças quando o retorno justifica.']],
        ],
        'cta' => ['es' => 'Quiero automatizar procesos', 'en' => 'I want to automate processes', 'pt' => 'Quero automatizar processos'],
        'proof_casos' => true,
    ],
    'academia-ejecutiva-de-ia' => [
        'promesa' => ['es' => 'Que tu equipo directivo lidere la IA, en vez de temerla.', 'en' => 'Have your leadership team lead AI, instead of fearing it.', 'pt' => 'Faça sua equipe diretiva liderar a IA, em vez de temê-la.'],
        'antes' => ['es' => 'Adopción desigual, miedo al cambio y decisiones de IA delegadas a quien menos las entiende.', 'en' => 'Uneven adoption, fear of change and AI decisions delegated to those who understand it least.', 'pt' => 'Adoção desigual, medo da mudança e decisões de IA delegadas a quem menos as entende.'],
        'despues' => ['es' => 'Líderes que entienden, deciden y gobiernan la IA con criterio, y una cultura que la adopta.', 'en' => 'Leaders who understand, decide and govern AI with judgment, and a culture that adopts it.', 'pt' => 'Líderes que entendem, decidem e governam a IA com critério, e uma cultura que a adota.'],
        'beneficios' => [
            ['icon' => 'people', 't' => ['es' => 'Criterio ejecutivo', 'en' => 'Executive judgment', 'pt' => 'Critério executivo'], 'x' => ['es' => 'Tus líderes deciden sobre IA con fundamento, no por moda.', 'en' => 'Your leaders decide on AI with grounding, not hype.', 'pt' => 'Seus líderes decidem sobre IA com fundamento, não por moda.']],
            ['icon' => 'bulb', 't' => ['es' => 'Adopción real', 'en' => 'Real adoption', 'pt' => 'Adoção real'], 'x' => ['es' => 'De la resistencia al uso cotidiano en los equipos.', 'en' => 'From resistance to everyday use across teams.', 'pt' => 'Da resistência ao uso cotidiano nas equipes.']],
            ['icon' => 'shield', 't' => ['es' => 'Gobierno responsable', 'en' => 'Responsible governance', 'pt' => 'Governança responsável'], 'x' => ['es' => 'Uso de IA con ética, seguridad y control.', 'en' => 'AI use with ethics, security and control.', 'pt' => 'Uso de IA com ética, segurança e controle.']],
        ],
        'pasos' => [
            ['t' => ['es' => 'Nivelación por rol', 'en' => 'Role-based leveling', 'pt' => 'Nivelamento por papel'], 'x' => ['es' => 'Contenido y casos ajustados a cada nivel de la organización.', 'en' => 'Content and cases tailored to each level of the organization.', 'pt' => 'Conteúdo e casos ajustados a cada nível da organização.']],
            ['t' => ['es' => 'Práctica con tu contexto', 'en' => 'Practice with your context', 'pt' => 'Prática com seu contexto'], 'x' => ['es' => 'Aplicación a casos reales del negocio, no ejemplos genéricos.', 'en' => 'Application to real business cases, not generic examples.', 'pt' => 'Aplicação a casos reais do negócio, não exemplos genéricos.']],
            ['t' => ['es' => 'Cultura y gobierno', 'en' => 'Culture and governance', 'pt' => 'Cultura e governança'], 'x' => ['es' => 'Instalamos rituales y lineamientos de uso responsable.', 'en' => 'We install rituals and responsible-use guidelines.', 'pt' => 'Instalamos rituais e diretrizes de uso responsável.']],
        ],
        'entregables' => [
            'es' => ['Programa formativo por rol y nivel', 'Casos prácticos con datos de tu negocio', 'Lineamientos de uso responsable de IA', 'Plan de adopción y seguimiento cultural'],
            'en' => ['Training program by role and level', 'Practical cases with your business data', 'Responsible AI-use guidelines', 'Adoption plan and cultural follow-up'],
            'pt' => ['Programa formativo por papel e nível', 'Casos práticos com dados do seu negócio', 'Diretrizes de uso responsável de IA', 'Plano de adoção e acompanhamento cultural'],
        ],
        'metricas' => [
            ['valor' => '360', 'suf' => '°', 'label' => ['es' => 'adopción por toda la organización', 'en' => 'adoption across the organization', 'pt' => 'adoção por toda a organização']],
            ['valor' => '1:1', 'label' => ['es' => 'casos con tu contexto real', 'en' => 'cases with your real context', 'pt' => 'casos com seu contexto real']],
            ['valor' => '100', 'suf' => '%', 'label' => ['es' => 'uso responsable y gobernado', 'en' => 'responsible, governed use', 'pt' => 'uso responsável e governado']],
        ],
        'faqs' => [
            ['q' => ['es' => '¿Necesitan conocimientos técnicos previos?', 'en' => 'Do they need prior technical knowledge?', 'pt' => 'Precisam de conhecimento técnico prévio?'], 'a' => ['es' => 'No. El programa se nivela por rol; los líderes aprenden a decidir y gobernar, no a programar.', 'en' => 'No. The program levels by role; leaders learn to decide and govern, not to code.', 'pt' => 'Não. O programa se nivela por papel; os líderes aprendem a decidir e governar, não a programar.']],
        ],
        'cta' => ['es' => 'Quiero formar a mi equipo', 'en' => 'I want to train my team', 'pt' => 'Quero formar minha equipe'],
        'proof_casos' => true,
    ],
];

return ['solutions' => $solutions, 'products' => $products];
