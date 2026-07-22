// Landings de venta por solución y producto — orientadas a conversión, mobile-first.
// Estructura: promesa · contraste antes/después · beneficios · cómo funciona ·
// entregables · prueba (métricas) · objeciones (FAQ) · cierre + CTA sticky + modal.
import { t, tr, trLines, pageUrl, api, store, setMeta } from '../lib/core.js';
import { Icon, CountUp, ScrollProgress, inView } from '../lib/ui.js';
import { PageHero } from '../lib/layout.js';
import { LeadModal } from '../lib/forms.js';

async function fc(s){ const r=await api.get('/content/'+s); return r.ok?r.data:[]; }
export const slugify = (s)=> (s||'').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,80);
const tx = (key,fb)=>{ const v=t(key); return (v && v!==key)?v:fb; };
const L = (o)=> o ? (o[store.locale] || o.es || '') : '';

// ————————————————————— Copy de conversión (trilingüe) —————————————————————
// Por solución (skey) y por producto (slug). Se combina con el contenido del CMS;
// el CMS aporta problema/cómo/qué cambia y aquí vive el marco de venta.
const SOL = {
  estrategia: {
    promesa:{es:'De “deberíamos usar IA” a un plan de inversión con retornos definidos.',en:'From “we should use AI” to an investment plan with defined returns.',pt:'De “deveríamos usar IA” a um plano de investimento com retornos definidos.'},
    antes:{es:'Comités que discuten IA en abstracto, pilotos sueltos que no escalan y presupuesto que se diluye sin retorno claro.',en:'Committees debating AI in the abstract, isolated pilots that don’t scale and budget that dilutes with no clear return.',pt:'Comitês que discutem IA no abstrato, pilotos soltos que não escalam e orçamento que se dilui sem retorno claro.'},
    despues:{es:'Una hoja de ruta priorizada por impacto en el P&L, con dueños, hitos y métricas que la dirección defiende ante la junta.',en:'A roadmap prioritized by P&L impact, with owners, milestones and metrics leadership can defend to the board.',pt:'Um roadmap priorizado por impacto no resultado, com donos, marcos e métricas que a diretoria defende ao conselho.'},
    beneficios:[
      {icon:'target',t:{es:'Prioridad por impacto',en:'Priority by impact',pt:'Prioridade por impacto'},x:{es:'Cada iniciativa se ordena por retorno real y viabilidad, no por moda.',en:'Every initiative is ranked by real return and feasibility, not hype.',pt:'Cada iniciativa é ordenada por retorno real e viabilidade, não por moda.'}},
      {icon:'shield',t:{es:'Decisiones defendibles',en:'Defensible decisions',pt:'Decisões defensáveis'},x:{es:'Un caso de inversión que resiste las preguntas difíciles del comité y la junta.',en:'An investment case that withstands the tough questions from committee and board.',pt:'Um caso de investimento que resiste às perguntas difíceis do comitê e do conselho.'}},
      {icon:'growth',t:{es:'Del plan a la ejecución',en:'From plan to execution',pt:'Do plano à execução'},x:{es:'Roadmap con dueños y métricas: la estrategia se ejecuta, no se archiva.',en:'A roadmap with owners and metrics: strategy gets executed, not filed away.',pt:'Roadmap com donos e métricas: a estratégia se executa, não se arquiva.'}},
    ],
    pasos:[
      {t:{es:'Diagnóstico de madurez',en:'Maturity diagnostic',pt:'Diagnóstico de maturidade'},x:{es:'Datos, procesos y talento evaluados en semanas, no meses.',en:'Data, processes and talent assessed in weeks, not months.',pt:'Dados, processos e talento avaliados em semanas, não meses.'}},
      {t:{es:'Priorización por P&L',en:'P&L prioritization',pt:'Priorização por resultado'},x:{es:'Iniciativas ordenadas por impacto financiero y viabilidad real.',en:'Initiatives ranked by financial impact and real feasibility.',pt:'Iniciativas ordenadas por impacto financeiro e viabilidade real.'}},
      {t:{es:'Roadmap ejecutivo',en:'Executive roadmap',pt:'Roadmap executivo'},x:{es:'Hitos, dueños y métricas listos para presentar a la junta.',en:'Milestones, owners and metrics ready to present to the board.',pt:'Marcos, donos e métricas prontos para apresentar ao conselho.'}},
    ],
    entregables:{es:['Diagnóstico de madurez en IA (datos, procesos, talento)','Mapa de oportunidades priorizado por impacto en P&L','Roadmap ejecutivo a 12 meses con hitos y dueños','Caso de inversión y métricas de seguimiento','Sesión de alineación con el comité directivo'],
      en:['AI maturity diagnostic (data, processes, talent)','Opportunity map prioritized by P&L impact','12-month executive roadmap with milestones and owners','Investment case and tracking metrics','Alignment session with the leadership committee'],
      pt:['Diagnóstico de maturidade em IA (dados, processos, talento)','Mapa de oportunidades priorizado por impacto no resultado','Roadmap executivo de 12 meses com marcos e donos','Caso de investimento e métricas de acompanhamento','Sessão de alinhamento com o comitê diretivo']},
    metricas:[{valor:'12',suf:' meses',label:{es:'de roadmap accionable',en:'of actionable roadmap',pt:'de roadmap acionável'}},{valor:'100',suf:'%',label:{es:'iniciativas con dueño y métrica',en:'initiatives with owner and metric',pt:'iniciativas com dono e métrica'}},{valor:'1',label:{es:'fuente de verdad para decidir',en:'source of truth to decide',pt:'fonte de verdade para decidir'}}],
    faqs:[
      {q:{es:'¿Necesitamos los datos ordenados antes de empezar?',en:'Do we need our data in order before starting?',pt:'Precisamos dos dados organizados antes de começar?'},a:{es:'No. El diagnóstico parte de donde estás hoy; ordenar los datos es parte del roadmap, priorizado por lo que de verdad mueve el negocio.',en:'No. The diagnostic starts where you are today; organizing the data is part of the roadmap, prioritized by what truly moves the business.',pt:'Não. O diagnóstico parte de onde você está hoje; organizar os dados é parte do roadmap, priorizado pelo que realmente move o negócio.'}},
      {q:{es:'¿Es una consultoría que entrega un PDF y se va?',en:'Is this a consultancy that delivers a PDF and leaves?',pt:'É uma consultoria que entrega um PDF e vai embora?'},a:{es:'No. Entregamos un plan con dueños y métricas y acompañamos la ejecución. El objetivo es retorno, no un documento.',en:'No. We deliver a plan with owners and metrics and support execution. The goal is return, not a document.',pt:'Não. Entregamos um plano com donos e métricas e acompanhamos a execução. O objetivo é retorno, não um documento.'}},
    ],
    cta:{es:'Quiero mi roadmap de IA',en:'I want my AI roadmap',pt:'Quero meu roadmap de IA'},
  },
  automatizacion:{
    promesa:{es:'Recupera horas senior y escala la operación sin escalar la nómina.',en:'Recover senior hours and scale operations without scaling headcount.',pt:'Recupere horas sênior e escale a operação sem escalar a folha.'},
    antes:{es:'Procesos manuales que consumen a tu mejor gente, generan errores y frenan el crecimiento.',en:'Manual processes that consume your best people, cause errors and slow growth.',pt:'Processos manuais que consomem sua melhor gente, geram erros e freiam o crescimento.'},
    despues:{es:'Flujos automatizados y monitoreados que liberan al equipo para lo estratégico, con indicadores de eficiencia a la vista.',en:'Automated, monitored flows that free the team for strategy, with efficiency indicators in plain sight.',pt:'Fluxos automatizados e monitorados que liberam a equipe para o estratégico, com indicadores de eficiência à vista.'},
    beneficios:[
      {icon:'gear',t:{es:'Horas senior liberadas',en:'Senior hours freed',pt:'Horas sênior liberadas'},x:{es:'Tu talento deja de operar planillas y vuelve a la estrategia.',en:'Your talent stops running spreadsheets and returns to strategy.',pt:'Seu talento deixa de operar planilhas e volta à estratégia.'}},
      {icon:'shield',t:{es:'Menos errores, más control',en:'Fewer errors, more control',pt:'Menos erros, mais controle'},x:{es:'Procesos consistentes, auditables y monitoreados en vivo.',en:'Consistent, auditable, live-monitored processes.',pt:'Processos consistentes, auditáveis e monitorados ao vivo.'}},
      {icon:'growth',t:{es:'Escala sin más nómina',en:'Scale without more headcount',pt:'Escale sem mais folha'},x:{es:'La operación crece con la demanda, no con el headcount.',en:'Operations grow with demand, not with headcount.',pt:'A operação cresce com a demanda, não com o headcount.'}},
    ],
    pasos:[
      {t:{es:'Mapeo de procesos',en:'Process mapping',pt:'Mapeamento de processos'},x:{es:'Identificamos cuellos de botella y el retorno de automatizar cada uno.',en:'We identify bottlenecks and the return of automating each one.',pt:'Identificamos gargalos e o retorno de automatizar cada um.'}},
      {t:{es:'Automatización con IA',en:'AI automation',pt:'Automação com IA'},x:{es:'Implementamos donde el retorno lo justifica, con datos reales.',en:'We implement where the return justifies it, with real data.',pt:'Implementamos onde o retorno justifica, com dados reais.'}},
      {t:{es:'Operación monitoreada',en:'Monitored operation',pt:'Operação monitorada'},x:{es:'Indicadores de eficiencia y mejora continua desde el día uno.',en:'Efficiency indicators and continuous improvement from day one.',pt:'Indicadores de eficiência e melhoria contínua desde o dia um.'}},
    ],
    entregables:{es:['Mapa de procesos críticos con potencial de automatización','Automatizaciones implementadas y documentadas','Tablero de eficiencia operativa','Protocolo de monitoreo y mejora continua','Transferencia y capacitación al equipo'],
      en:['Map of critical processes with automation potential','Automations implemented and documented','Operational efficiency dashboard','Monitoring and continuous-improvement protocol','Handover and team training'],
      pt:['Mapa de processos críticos com potencial de automação','Automações implementadas e documentadas','Painel de eficiência operacional','Protocolo de monitoramento e melhoria contínua','Transferência e capacitação da equipe']},
    metricas:[{valor:'24/7',label:{es:'operación monitoreada',en:'monitored operation',pt:'operação monitorada'}},{valor:'100',suf:'%',label:{es:'procesos auditables',en:'auditable processes',pt:'processos auditáveis'}},{valor:'1',label:{es:'tablero de eficiencia',en:'efficiency dashboard',pt:'painel de eficiência'}}],
    faqs:[
      {q:{es:'¿Tendremos que cambiar todos nuestros sistemas?',en:'Will we have to change all our systems?',pt:'Teremos que trocar todos os nossos sistemas?'},a:{es:'No. Automatizamos sobre lo que ya usas e integramos; reemplazar es la excepción, no la regla.',en:'No. We automate on top of what you already use and integrate; replacing is the exception, not the rule.',pt:'Não. Automatizamos sobre o que você já usa e integramos; substituir é a exceção, não a regra.'}},
      {q:{es:'¿La automatización reemplaza a mi equipo?',en:'Does automation replace my team?',pt:'A automação substitui minha equipe?'},a:{es:'No. Libera a tu equipo de lo repetitivo para que se enfoque en lo que genera valor y criterio.',en:'No. It frees your team from the repetitive so they can focus on what creates value and judgment.',pt:'Não. Libera sua equipe do repetitivo para focar no que gera valor e critério.'}},
    ],
    cta:{es:'Quiero automatizar mi operación',en:'I want to automate my operation',pt:'Quero automatizar minha operação'},
  },
  datos:{
    promesa:{es:'Una sola fuente de verdad para decidir en horas, no en semanas.',en:'A single source of truth to decide in hours, not weeks.',pt:'Uma única fonte de verdade para decidir em horas, não em semanas.'},
    antes:{es:'Datos dispersos en sistemas que no conversan y reportes manuales que llegan tarde a las decisiones.',en:'Data scattered across systems that don’t talk, and manual reports that arrive too late for decisions.',pt:'Dados dispersos em sistemas que não conversam e relatórios manuais que chegam tarde às decisões.'},
    despues:{es:'Tableros ejecutivos con indicadores accionables y modelos que anticipan, sobre una arquitectura simple.',en:'Executive dashboards with actionable indicators and models that anticipate, on a simple architecture.',pt:'Painéis executivos com indicadores acionáveis e modelos que antecipam, sobre uma arquitetura simples.'},
    beneficios:[
      {icon:'analitica',t:{es:'Una fuente de verdad',en:'One source of truth',pt:'Uma fonte de verdade'},x:{es:'Todos deciden sobre los mismos números, sin discutir de dónde salieron.',en:'Everyone decides on the same numbers, no arguing where they came from.',pt:'Todos decidem sobre os mesmos números, sem discutir de onde saíram.'}},
      {icon:'ia',t:{es:'Anticipación con IA',en:'Anticipation with AI',pt:'Antecipação com IA'},x:{es:'Modelos predictivos donde adelantarse vale dinero.',en:'Predictive models where getting ahead is worth money.',pt:'Modelos preditivos onde antecipar vale dinheiro.'}},
      {icon:'growth',t:{es:'Decisiones en horas',en:'Decisions in hours',pt:'Decisões em horas'},x:{es:'De reportes mensuales a indicadores vivos.',en:'From monthly reports to live indicators.',pt:'De relatórios mensais a indicadores vivos.'}},
    ],
    pasos:[
      {t:{es:'Consolidación de fuentes',en:'Source consolidation',pt:'Consolidação de fontes'},x:{es:'Unificamos tus datos en una arquitectura simple y confiable.',en:'We unify your data into a simple, reliable architecture.',pt:'Unificamos seus dados em uma arquitetura simples e confiável.'}},
      {t:{es:'Tableros accionables',en:'Actionable dashboards',pt:'Painéis acionáveis'},x:{es:'Indicadores que dicen qué hacer, no solo qué pasó.',en:'Indicators that say what to do, not just what happened.',pt:'Indicadores que dizem o que fazer, não só o que aconteceu.'}},
      {t:{es:'Modelos predictivos',en:'Predictive models',pt:'Modelos preditivos'},x:{es:'Donde anticipar el resultado tiene impacto financiero.',en:'Where anticipating the outcome has financial impact.',pt:'Onde antecipar o resultado tem impacto financeiro.'}},
    ],
    entregables:{es:['Arquitectura de datos consolidada','Tableros ejecutivos por área','Indicadores accionables con dueño','Modelos predictivos priorizados','Gobierno de métricas y ritual de revisión'],
      en:['Consolidated data architecture','Executive dashboards by area','Actionable indicators with owners','Prioritized predictive models','Metric governance and review ritual'],
      pt:['Arquitetura de dados consolidada','Painéis executivos por área','Indicadores acionáveis com dono','Modelos preditivos priorizados','Governança de métricas e ritual de revisão']},
    metricas:[{valor:'1',label:{es:'fuente de verdad',en:'source of truth',pt:'fonte de verdade'}},{valor:'360',suf:'°',label:{es:'visibilidad del negocio',en:'business visibility',pt:'visibilidade do negócio'}},{valor:'0',label:{es:'reportes manuales tardíos',en:'late manual reports',pt:'relatórios manuais atrasados'}}],
    faqs:[
      {q:{es:'¿Sirve si tenemos datos en Excel y varios sistemas?',en:'Does it work if our data is in Excel and several systems?',pt:'Funciona se tivermos dados em Excel e vários sistemas?'},a:{es:'Sí. Justamente ahí empezamos: consolidamos lo disperso en una arquitectura simple antes de sofisticar.',en:'Yes. That’s exactly where we start: we consolidate the scattered into a simple architecture before sophisticating.',pt:'Sim. É exatamente aí que começamos: consolidamos o disperso em uma arquitetura simples antes de sofisticar.'}},
      {q:{es:'¿Cuánto tarda en verse el primer tablero?',en:'How long until the first dashboard is live?',pt:'Quanto tempo até o primeiro painel?'},a:{es:'Priorizamos un tablero de alto impacto primero, para que la dirección vea valor temprano y luego escalamos.',en:'We prioritize one high-impact dashboard first, so leadership sees value early, then we scale.',pt:'Priorizamos um painel de alto impacto primeiro, para a diretoria ver valor cedo, e depois escalamos.'}},
    ],
    cta:{es:'Quiero ver mis datos claros',en:'I want my data clear',pt:'Quero ver meus dados claros'},
  },
  growth:{
    promesa:{es:'Convierte el crecimiento en un sistema que compone, no en campañas sueltas.',en:'Turn growth into a compounding system, not isolated campaigns.',pt:'Transforme o crescimento em um sistema que compõe, não em campanhas soltas.'},
    antes:{es:'Adquisición cara, retención frágil y una experiencia que no aprende de sus propios datos.',en:'Expensive acquisition, fragile retention and an experience that doesn’t learn from its own data.',pt:'Aquisição cara, retenção frágil e uma experiência que não aprende com os próprios dados.'},
    despues:{es:'Funnels instrumentados de punta a punta, personalización con IA y experimentación continua con gobierno de métricas.',en:'End-to-end instrumented funnels, AI personalization and continuous experimentation with metric governance.',pt:'Funis instrumentados de ponta a ponta, personalização com IA e experimentação contínua com governança de métricas.'},
    beneficios:[
      {icon:'growth',t:{es:'Crecimiento que compone',en:'Compounding growth',pt:'Crescimento que compõe'},x:{es:'Cada experimento deja aprendizaje y activo, no solo gasto.',en:'Every experiment leaves learning and an asset, not just spend.',pt:'Cada experimento deixa aprendizado e ativo, não só gasto.'}},
      {icon:'ia',t:{es:'Personalización con IA',en:'AI personalization',pt:'Personalização com IA'},x:{es:'Scoring y mensajes relevantes para cada segmento.',en:'Scoring and relevant messages for each segment.',pt:'Scoring e mensagens relevantes para cada segmento.'}},
      {icon:'analitica',t:{es:'Funnel medido punta a punta',en:'Funnel measured end to end',pt:'Funil medido ponta a ponta'},x:{es:'Sabes dónde ganas y dónde pierdes clientes.',en:'You know where you win and where you lose customers.',pt:'Você sabe onde ganha e onde perde clientes.'}},
    ],
    pasos:[
      {t:{es:'Instrumentación del funnel',en:'Funnel instrumentation',pt:'Instrumentação do funil'},x:{es:'Medimos cada etapa de adquisición a retención.',en:'We measure every stage from acquisition to retention.',pt:'Medimos cada etapa da aquisição à retenção.'}},
      {t:{es:'Personalización y scoring',en:'Personalization and scoring',pt:'Personalização e scoring'},x:{es:'IA aplicada a segmentar y priorizar lo que convierte.',en:'AI applied to segment and prioritize what converts.',pt:'IA aplicada a segmentar e priorizar o que converte.'}},
      {t:{es:'Experimentación continua',en:'Continuous experimentation',pt:'Experimentação contínua'},x:{es:'Ciclos de mejora con gobierno de métricas.',en:'Improvement cycles with metric governance.',pt:'Ciclos de melhoria com governança de métricas.'}},
    ],
    entregables:{es:['Funnel instrumentado de adquisición a retención','Modelo de scoring y segmentación','Motor de experimentación y roadmap de tests','Tablero de métricas de crecimiento','Ritual de revisión y priorización'],
      en:['Instrumented funnel from acquisition to retention','Scoring and segmentation model','Experimentation engine and test roadmap','Growth metrics dashboard','Review and prioritization ritual'],
      pt:['Funil instrumentado da aquisição à retenção','Modelo de scoring e segmentação','Motor de experimentação e roadmap de testes','Painel de métricas de crescimento','Ritual de revisão e priorização']},
    metricas:[{valor:'100',suf:'%',label:{es:'del funnel instrumentado',en:'of the funnel instrumented',pt:'do funil instrumentado'}},{valor:'1:1',label:{es:'personalización por segmento',en:'personalization per segment',pt:'personalização por segmento'}},{valor:'∞',label:{es:'ciclos de experimentación',en:'experimentation cycles',pt:'ciclos de experimentação'}}],
    faqs:[
      {q:{es:'¿Esto es una agencia de marketing?',en:'Is this a marketing agency?',pt:'Isto é uma agência de marketing?'},a:{es:'No. Construimos el sistema de crecimiento —datos, funnel y experimentación— para que tus campañas rindan más, las hagas con quien las hagas.',en:'No. We build the growth system —data, funnel and experimentation— so your campaigns perform better, whoever runs them.',pt:'Não. Construímos o sistema de crescimento —dados, funil e experimentação— para suas campanhas renderem mais, com quem quer que as faça.'}},
      {q:{es:'¿Funciona en B2B con ciclos largos?',en:'Does it work in B2B with long cycles?',pt:'Funciona em B2B com ciclos longos?'},a:{es:'Sí. Instrumentamos el funnel completo, incluido el ciclo comercial largo, y priorizamos las palancas de mayor impacto.',en:'Yes. We instrument the full funnel, including the long sales cycle, and prioritize the highest-impact levers.',pt:'Sim. Instrumentamos o funil completo, incluindo o ciclo comercial longo, e priorizamos as alavancas de maior impacto.'}},
    ],
    cta:{es:'Quiero un sistema de crecimiento',en:'I want a growth system',pt:'Quero um sistema de crescimento'},
  },
};

const PROD = {
  'diagnostico-ejecutivo':{
    promesa:{es:'En una sesión, claridad sobre dónde la IA mueve tu P&L primero.',en:'In one session, clarity on where AI moves your P&L first.',pt:'Em uma sessão, clareza sobre onde a IA move seu resultado primeiro.'},
    antes:{es:'Muchas ideas de IA, cero certeza sobre por dónde empezar sin quemar presupuesto.',en:'Many AI ideas, zero certainty on where to start without burning budget.',pt:'Muitas ideias de IA, zero certeza sobre por onde começar sem queimar orçamento.'},
    despues:{es:'Un mapa claro de las 3 oportunidades de mayor retorno y el primer paso concreto.',en:'A clear map of the top 3 highest-return opportunities and the concrete first step.',pt:'Um mapa claro das 3 oportunidades de maior retorno e o primeiro passo concreto.'},
    beneficios:[
      {icon:'target',t:{es:'Foco inmediato',en:'Immediate focus',pt:'Foco imediato'},x:{es:'Sales de la sesión sabiendo exactamente por dónde empezar.',en:'You leave the session knowing exactly where to start.',pt:'Você sai da sessão sabendo exatamente por onde começar.'}},
      {icon:'analitica',t:{es:'Priorizado por retorno',en:'Prioritized by return',pt:'Priorizado por retorno'},x:{es:'Oportunidades ordenadas por impacto y facilidad de ejecución.',en:'Opportunities ranked by impact and ease of execution.',pt:'Oportunidades ordenadas por impacto e facilidade de execução.'}},
      {icon:'shield',t:{es:'Sin compromiso',en:'No commitment',pt:'Sem compromisso'},x:{es:'Un diagnóstico honesto: si no hay caso, te lo decimos.',en:'An honest diagnostic: if there’s no case, we tell you.',pt:'Um diagnóstico honesto: se não há caso, nós dizemos.'}},
    ],
    pasos:[
      {t:{es:'Conversación estructurada',en:'Structured conversation',pt:'Conversa estruturada'},x:{es:'Entendemos tu negocio, metas y frenos actuales.',en:'We understand your business, goals and current blockers.',pt:'Entendemos seu negócio, metas e freios atuais.'}},
      {t:{es:'Mapa de oportunidades',en:'Opportunity map',pt:'Mapa de oportunidades'},x:{es:'Detectamos dónde la IA y la automatización rinden primero.',en:'We spot where AI and automation pay off first.',pt:'Detectamos onde a IA e a automação rendem primeiro.'}},
      {t:{es:'Primer paso concreto',en:'Concrete first step',pt:'Primeiro passo concreto'},x:{es:'Te vas con una recomendación accionable, no con teoría.',en:'You leave with an actionable recommendation, not theory.',pt:'Você sai com uma recomendação acionável, não com teoria.'}},
    ],
    entregables:{es:['Sesión ejecutiva de diagnóstico','Mapa de las 3 oportunidades de mayor retorno','Recomendación del primer paso concreto','Estimación de impacto y esfuerzo'],
      en:['Executive diagnostic session','Map of the top 3 highest-return opportunities','Recommendation of the concrete first step','Impact and effort estimate'],
      pt:['Sessão executiva de diagnóstico','Mapa das 3 oportunidades de maior retorno','Recomendação do primeiro passo concreto','Estimativa de impacto e esforço']},
    metricas:[{valor:'1',label:{es:'sesión para tener claridad',en:'session to gain clarity',pt:'sessão para ter clareza'}},{valor:'3',label:{es:'oportunidades priorizadas',en:'prioritized opportunities',pt:'oportunidades priorizadas'}},{valor:'360',suf:'°',label:{es:'de tu negocio en revisión',en:'of your business reviewed',pt:'do seu negócio em revisão'}}],
    faqs:[
      {q:{es:'¿Es una llamada de ventas disfrazada?',en:'Is this a disguised sales call?',pt:'É uma ligação de vendas disfarçada?'},a:{es:'No. Es un diagnóstico real con valor propio. Si no vemos un caso claro para trabajar juntos, te lo diremos.',en:'No. It’s a real diagnostic with value of its own. If we don’t see a clear case to work together, we’ll tell you.',pt:'Não. É um diagnóstico real com valor próprio. Se não virmos um caso claro para trabalhar juntos, diremos.'}},
    ],
    cta:{es:'Quiero mi diagnóstico',en:'I want my diagnostic',pt:'Quero meu diagnóstico'},
  },
  'programas-de-automatizacion':{
    promesa:{es:'Automatización implementada, no diapositivas: procesos que ya corren solos.',en:'Automation implemented, not slides: processes that already run themselves.',pt:'Automação implementada, não slides: processos que já rodam sozinhos.'},
    antes:{es:'Equipos atados a tareas repetitivas y procesos que no escalan con la demanda.',en:'Teams tied to repetitive tasks and processes that don’t scale with demand.',pt:'Equipes presas a tarefas repetitivas e processos que não escalam com a demanda.'},
    despues:{es:'Flujos en producción, monitoreados y documentados, con el equipo capacitado para operarlos.',en:'Flows in production, monitored and documented, with the team trained to operate them.',pt:'Fluxos em produção, monitorados e documentados, com a equipe capacitada para operá-los.'},
    beneficios:[
      {icon:'gear',t:{es:'Ejecución real',en:'Real execution',pt:'Execução real'},x:{es:'Salimos con procesos funcionando, no con un plan en el cajón.',en:'We leave with working processes, not a plan in a drawer.',pt:'Saímos com processos funcionando, não com um plano na gaveta.'}},
      {icon:'growth',t:{es:'Retorno medible',en:'Measurable return',pt:'Retorno mensurável'},x:{es:'Cada automatización se elige por su impacto en eficiencia.',en:'Each automation is chosen by its efficiency impact.',pt:'Cada automação é escolhida pelo seu impacto em eficiência.'}},
      {icon:'people',t:{es:'Equipo autónomo',en:'Autonomous team',pt:'Equipe autônoma'},x:{es:'Transferimos el conocimiento para que no dependas de nadie.',en:'We transfer the knowledge so you depend on no one.',pt:'Transferimos o conhecimento para você não depender de ninguém.'}},
    ],
    pasos:[
      {t:{es:'Priorización de procesos',en:'Process prioritization',pt:'Priorização de processos'},x:{es:'Elegimos los flujos con mayor retorno de automatización.',en:'We pick the flows with the highest automation return.',pt:'Escolhemos os fluxos com maior retorno de automação.'}},
      {t:{es:'Construcción e integración',en:'Build and integrate',pt:'Construção e integração'},x:{es:'Implementamos sobre tus herramientas actuales.',en:'We implement on top of your current tools.',pt:'Implementamos sobre suas ferramentas atuais.'}},
      {t:{es:'Puesta en producción',en:'Go live',pt:'Colocação em produção'},x:{es:'Monitoreo, documentación y capacitación al equipo.',en:'Monitoring, documentation and team training.',pt:'Monitoramento, documentação e capacitação da equipe.'}},
    ],
    entregables:{es:['Procesos automatizados en producción','Integraciones con tus herramientas actuales','Tablero de eficiencia y monitoreo','Documentación y capacitación del equipo'],
      en:['Automated processes in production','Integrations with your current tools','Efficiency and monitoring dashboard','Documentation and team training'],
      pt:['Processos automatizados em produção','Integrações com suas ferramentas atuais','Painel de eficiência e monitoramento','Documentação e capacitação da equipe']},
    metricas:[{valor:'24/7',label:{es:'procesos operando',en:'processes running',pt:'processos operando'}},{valor:'100',suf:'%',label:{es:'documentado y transferido',en:'documented and transferred',pt:'documentado e transferido'}},{valor:'1',label:{es:'tablero de eficiencia',en:'efficiency dashboard',pt:'painel de eficiência'}}],
    faqs:[
      {q:{es:'¿Con qué herramientas trabajan?',en:'Which tools do you work with?',pt:'Com quais ferramentas vocês trabalham?'},a:{es:'Nos adaptamos a tu stack. Integramos sobre lo que ya usas y solo proponemos cambios cuando el retorno lo justifica.',en:'We adapt to your stack. We integrate on what you already use and only propose changes when the return justifies it.',pt:'Nos adaptamos ao seu stack. Integramos sobre o que você já usa e só propomos mudanças quando o retorno justifica.'}},
    ],
    cta:{es:'Quiero automatizar procesos',en:'I want to automate processes',pt:'Quero automatizar processos'},
  },
  'academia-ejecutiva-de-ia':{
    promesa:{es:'Que tu equipo directivo lidere la IA, en vez de temerla.',en:'Have your leadership team lead AI, instead of fearing it.',pt:'Faça sua equipe diretiva liderar a IA, em vez de temê-la.'},
    antes:{es:'Adopción desigual, miedo al cambio y decisiones de IA delegadas a quien menos las entiende.',en:'Uneven adoption, fear of change and AI decisions delegated to those who understand it least.',pt:'Adoção desigual, medo da mudança e decisões de IA delegadas a quem menos as entende.'},
    despues:{es:'Líderes que entienden, deciden y gobiernan la IA con criterio, y una cultura que la adopta.',en:'Leaders who understand, decide and govern AI with judgment, and a culture that adopts it.',pt:'Líderes que entendem, decidem e governam a IA com critério, e uma cultura que a adota.'},
    beneficios:[
      {icon:'people',t:{es:'Criterio ejecutivo',en:'Executive judgment',pt:'Critério executivo'},x:{es:'Tus líderes deciden sobre IA con fundamento, no por moda.',en:'Your leaders decide on AI with grounding, not hype.',pt:'Seus líderes decidem sobre IA com fundamento, não por moda.'}},
      {icon:'bulb',t:{es:'Adopción real',en:'Real adoption',pt:'Adoção real'},x:{es:'De la resistencia al uso cotidiano en los equipos.',en:'From resistance to everyday use across teams.',pt:'Da resistência ao uso cotidiano nas equipes.'}},
      {icon:'shield',t:{es:'Gobierno responsable',en:'Responsible governance',pt:'Governança responsável'},x:{es:'Uso de IA con ética, seguridad y control.',en:'AI use with ethics, security and control.',pt:'Uso de IA com ética, segurança e controle.'}},
    ],
    pasos:[
      {t:{es:'Nivelación por rol',en:'Role-based leveling',pt:'Nivelamento por papel'},x:{es:'Contenido y casos ajustados a cada nivel de la organización.',en:'Content and cases tailored to each level of the organization.',pt:'Conteúdo e casos ajustados a cada nível da organização.'}},
      {t:{es:'Práctica con tu contexto',en:'Practice with your context',pt:'Prática com seu contexto'},x:{es:'Aplicación a casos reales del negocio, no ejemplos genéricos.',en:'Application to real business cases, not generic examples.',pt:'Aplicação a casos reais do negócio, não exemplos genéricos.'}},
      {t:{es:'Cultura y gobierno',en:'Culture and governance',pt:'Cultura e governança'},x:{es:'Instalamos rituales y lineamientos de uso responsable.',en:'We install rituals and responsible-use guidelines.',pt:'Instalamos rituais e diretrizes de uso responsável.'}},
    ],
    entregables:{es:['Programa formativo por rol y nivel','Casos prácticos con datos de tu negocio','Lineamientos de uso responsable de IA','Plan de adopción y seguimiento cultural'],
      en:['Training program by role and level','Practical cases with your business data','Responsible AI-use guidelines','Adoption plan and cultural follow-up'],
      pt:['Programa formativo por papel e nível','Casos práticos com dados do seu negócio','Diretrizes de uso responsável de IA','Plano de adoção e acompanhamento cultural']},
    metricas:[{valor:'360',suf:'°',label:{es:'adopción por toda la organización',en:'adoption across the organization',pt:'adoção por toda a organização'}},{valor:'1:1',label:{es:'casos con tu contexto real',en:'cases with your real context',pt:'casos com seu contexto real'}},{valor:'100',suf:'%',label:{es:'uso responsable y gobernado',en:'responsible, governed use',pt:'uso responsável e governado'}}],
    faqs:[
      {q:{es:'¿Necesitan conocimientos técnicos previos?',en:'Do they need prior technical knowledge?',pt:'Precisam de conhecimento técnico prévio?'},a:{es:'No. El programa se nivela por rol; los líderes aprenden a decidir y gobernar, no a programar.',en:'No. The program levels by role; leaders learn to decide and govern, not to code.',pt:'Não. O programa se nivela por papel; os líderes aprendem a decidir e governar, não a programar.'}},
    ],
    cta:{es:'Quiero formar a mi equipo',en:'I want to train my team',pt:'Quero formar minha equipe'},
  },
};

// ————————————————————— Componente de landing (compartido) —————————————————————
const Landing = {
  components:{ Icon, CountUp, ScrollProgress, PageHero, LeadModal },
  props:{ m:Object },
  data(){ return { modal:false }; },
  computed:{ t:()=>t, pageUrl:()=>pageUrl },
  methods:{ tx },
  template:`<div>
    <ScrollProgress/>
    <PageHero :eyebrow="m.eyebrow" :titulo="m.titulo" :sub="m.promesa">
      <div class="land-cta-row" v-reveal :style="{'--d':'.22s'}">
        <button class="btn btn-grad" @click="modal=true">{{ m.cta }}</button>
        <router-link :to="pageUrl(m.diag?'diagnostico':'agenda')" class="btn btn-ghost">{{ m.diag?tx('common.hacer_diagnostico','Hacer diagnóstico'):tx('landing.agendar','Agendar 1:1') }}</router-link></div>
      <p class="hero-trust small" v-reveal :style="{'--d':'.3s'}"><Icon name="check" :size="14"/> {{ tx('landing.trust','Respuesta en menos de 24 h · Sin compromiso') }}</p>
    </PageHero>

    <section class="section land-oferta-sec" style="padding-top:0"><div class="container"><div class="glass glass-lit card land-oferta" v-reveal>
      <div class="land-oferta-body"><span class="chip chip--cyan">{{ (m.oferta&&m.oferta.badge) || tx('landing.oferta_badge','Sin costo') }}</span>
        <h2 class="h3">{{ (m.oferta&&m.oferta.titulo) || tx('landing.oferta_t','Diagnóstico ejecutivo gratuito') }}</h2>
        <p>{{ (m.oferta&&m.oferta.texto) || tx('landing.oferta_s','En 30 minutos te entregamos un mapa de las 3 oportunidades de mayor retorno para tu empresa. Sin costo y sin compromiso.') }}</p></div>
      <div class="land-oferta-cta">
        <router-link :to="pageUrl('diagnostico')" class="btn btn-grad">{{ (m.oferta&&m.oferta.cta) || tx('landing.oferta_cta','Quiero mi diagnóstico gratis') }}</router-link>
        <p class="small garantia"><Icon name="shield" :size="13"/> {{ tx('landing.garantia','Si no vemos un caso claro, te lo decimos. Sin letra pequeña.') }}</p></div>
    </div></div></section>

    <section class="section land-contrast-sec"><div class="container land-contrast">
      <div class="glass card contrast-col contrast-antes" v-reveal><span class="contrast-tag">{{ tx('landing.antes','Hoy') }}</span><p>{{ m.antes }}</p></div>
      <div class="contrast-arrow" v-reveal :style="{'--d':'.1s'}"><Icon name="arrow" :size="26"/></div>
      <div class="glass glass-lit card contrast-col contrast-despues" v-reveal :style="{'--d':'.16s'}"><span class="contrast-tag contrast-tag--win">{{ tx('landing.despues','Con ExperientIA') }}</span><p>{{ m.despues }}</p></div>
    </div></section>

    <section class="section"><div class="container">
      <div class="section-head"><h2 class="h2" v-reveal>{{ tx('landing.benes_t','Lo que cambia para ti') }}</h2></div>
      <div class="grid grid-3 land-benes">
        <article v-for="(b,i) in m.beneficios" :key="i" class="glass card bene-card" v-reveal :style="{'--d':i*.09+'s'}">
          <span class="icon-chip"><Icon :name="b.icon"/></span><h3 class="h3">{{ b.t }}</h3><p>{{ b.x }}</p></article></div></div></section>

    <section class="section land-steps-sec"><div class="bg-atmos"><div class="halo halo-violet" style="width:460px;height:460px;top:-120px;left:-220px;opacity:.24"></div></div>
      <div class="container">
      <div class="section-head"><p class="eyebrow" v-reveal>{{ tx('landing.como_t','Cómo funciona') }}</p><h2 class="h2" v-reveal :style="{'--d':'.06s'}">{{ tx('landing.como_s','Un método claro, sin humo') }}</h2></div>
      <div class="land-steps">
        <div v-for="(s,i) in m.pasos" :key="i" class="glass card step" v-reveal :style="{'--d':i*.1+'s'}">
          <span class="step-num">{{ i+1 }}</span><div><h3 class="h3">{{ s.t }}</h3><p>{{ s.x }}</p></div></div></div></div></section>

    <section class="section"><div class="container land-grid">
      <div class="land-body">
        <div class="glass card" v-reveal><h2 class="lbl" style="margin-bottom:1rem">{{ tx('landing.entregables_t','Qué recibes') }}</h2>
          <ul class="check-list check-list--lg"><li v-for="(e,i) in m.entregables" :key="i" v-reveal :style="{'--d':i*.05+'s'}"><span class="check-chip"><Icon name="check" :size="13"/></span>{{ e }}</li></ul></div>
        <div class="glass glass-lit card land-metrics" v-reveal v-if="m.metricas.length">
          <div v-for="(mt,i) in m.metricas" :key="i" class="metric"><span class="metric-val grad-text"><CountUp :value="mt.valor" :suffix="mt.suf||''"/></span><span class="metric-lbl">{{ mt.label }}</span></div></div>
      </div>
      <aside class="land-aside"><div class="glass glass-lit card land-cta" v-reveal>
        <h3 class="h3">{{ tx('landing.aside_t','Da el primer paso') }}</h3><p>{{ tx('landing.aside_s','Cuéntanos tu caso y te mostramos cómo aplicarlo a tu negocio.') }}</p>
        <button class="btn btn-grad" style="width:100%" @click="modal=true">{{ m.cta }}</button>
        <router-link :to="pageUrl('agenda')" class="btn btn-ghost" style="width:100%">{{ tx('landing.agendar','Agendar 1:1') }}</router-link>
        <p class="small garantia" style="justify-content:center"><Icon name="shield" :size="12"/> {{ tx('landing.garantia_corta','Sin costo · sin compromiso') }}</p>
        <p class="small" style="text-align:center">{{ t('form.privacidad') }}</p></div></aside></div></section>

    <section class="section land-proof-sec" v-if="m.casos && m.casos.length"><div class="bg-atmos"><div class="halo halo-cyan" style="width:440px;height:440px;top:-120px;right:-200px;opacity:.22"></div></div>
      <div class="container"><div class="section-head"><p class="eyebrow" v-reveal>{{ tx('landing.proof_e','Resultados reales') }}</p><h2 class="h2" v-reveal :style="{'--d':'.06s'}">{{ tx('landing.proof_t','Lo que logran nuestros clientes') }}</h2></div>
      <div class="grid grid-3 land-proof">
        <router-link v-for="(c,i) in m.casos" :key="i" :to="c.url" class="glass glass-lit card proof-card" v-reveal :style="{'--d':i*.08+'s'}">
          <span class="proof-sector">{{ c.sector }}</span><h3 class="h3">{{ c.titulo }}</h3>
          <div class="proof-metric"><span class="grad-text proof-val">{{ c.valor }}</span><span class="proof-lbl">{{ c.label }}</span></div>
          <span class="link-arrow">{{ tx('landing.ver_caso','Ver el caso') }} <Icon name="arrow" :size="15"/></span></router-link></div></div></section>

    <section class="section" v-if="m.testimonio"><div class="container" style="max-width:820px">
      <figure class="glass glass-lit card land-quote" v-reveal>
        <Icon name="bulb" :size="22"/>
        <blockquote>“{{ m.testimonio.quote }}”</blockquote>
        <figcaption v-if="m.testimonio.autor||m.testimonio.cargo"><b>{{ m.testimonio.autor }}</b><span v-if="m.testimonio.cargo"> · {{ m.testimonio.cargo }}</span></figcaption>
      </figure></div></section>

    <section class="section" v-if="m.faqs && m.faqs.length"><div class="container land-faqs">
      <div class="section-head"><h2 class="h2" v-reveal>{{ tx('landing.faq_t','Antes de que preguntes') }}</h2></div>
      <div class="faq-list"><details v-for="(f,i) in m.faqs" :key="i" class="glass faq-item" v-reveal :style="{'--d':i*.06+'s'}" :open="i===0"><summary>{{ f.q }}</summary><p>{{ f.a }}</p></details></div></div></section>

    <section class="section"><div class="container"><div class="glass glass-lit card land-final" v-reveal>
      <h2 class="h2">{{ tx('landing.final_t','¿Listo para aplicarlo en tu empresa?') }}</h2>
      <p class="lead">{{ tx('landing.final_s','Agenda una sesión o déjanos tus datos y te contactamos para mostrarte cómo funciona en tu caso.') }}</p>
      <div class="land-cta-row"><button class="btn btn-grad" @click="modal=true">{{ m.cta }}</button>
        <router-link :to="pageUrl('diagnostico')" class="btn btn-ghost">{{ tx('landing.oferta_cta','Quiero mi diagnóstico gratis') }}</router-link></div>
      <p class="small garantia" style="justify-content:center"><Icon name="shield" :size="13"/> {{ tx('landing.garantia','Si no vemos un caso claro, te lo decimos. Sin letra pequeña.') }}</p></div></div></section>

    <div class="land-sticky"><span>{{ m.titulo }}</span><button class="btn btn-grad btn-sm" @click="modal=true">{{ tx('landing.cta_corto','Me interesa') }}</button></div>
    <LeadModal v-if="modal" :titulo="m.titulo" :origen="m.origen" @close="modal=false"/>
  </div>`,
};

const NoEncontrado = {
  template:`<section class="page-hero" style="min-height:50vh;display:grid;align-items:center"><div class="container" style="text-align:center;display:grid;gap:1rem;justify-items:center">
    <h1 class="h2">{{ tx('landing.no_t','Contenido no encontrado') }}</h1><router-link :to="pageUrl(volver)" class="btn btn-primary">{{ tx('landing.volver','Ver todo') }}</router-link></div></section>`,
  props:{ volver:String }, computed:{ pageUrl:()=>pageUrl }, methods:{ tx },
};

// Prueba social embebida: toma los casos y arma tarjetas con su métrica estelar.
async function proofCasos(){
  const casos = await fc('casos');
  return casos.slice(0,3).map(c=>{
    const res = (c.resultados||[])[0] || {};
    return { sector: tr(c.sector), titulo: tr(c.titulo), valor: res.valor || '', label: res.label ? tr(res.label) : '',
      url: pageUrl('casos', { slug: slugify(tr(c.titulo,'es')) }) };
  }).filter(c=>c.valor);
}

export const SolucionLanding = {
  components:{ Landing, NoEncontrado },
  template:`<Landing v-if="m" :m="m"/><NoEncontrado v-else-if="cargado" volver="soluciones"/>`,
  data(){ return { m:null, cargado:false }; },
  async mounted(){
    const items=await fc('soluciones'); const s=items.find(x=>x.skey===this.$route.params.slug);
    if(s){ const c=(s.landing&&typeof s.landing==='object')?s.landing:(SOL[s.skey]||{}); const casos=await proofCasos(); this.m=build(s, c, tr(s.pilar), tr(s.titulo), 'solucion:'+s.skey, 'soluciones', trLines(s.como), true, tr(s.problema), tr(s.cambia), casos); setMeta(tr(s.titulo)+' · ExperientIA', L(c.promesa)||tr(s.cambia)); }
    this.cargado=true;
  },
};

export const ProductoLanding = {
  components:{ Landing, NoEncontrado },
  template:`<Landing v-if="m" :m="m"/><NoEncontrado v-else-if="cargado" volver="productos"/>`,
  data(){ return { m:null, cargado:false }; },
  async mounted(){
    const slug=this.$route.params.slug; const items=await fc('productos'); const p=items.find(x=>slugify(tr(x.nombre,'es'))===slug);
    if(p){ const c=(p.landing&&typeof p.landing==='object')?p.landing:(PROD[slug]||{}); const casos=await proofCasos(); this.m=build(p, c, tr(p.rol), tr(p.nombre), 'producto:'+slug, 'productos', [], false, tr(p.texto), tr(p.rol), casos); setMeta(tr(p.nombre)+' · ExperientIA', L(c.promesa)||tr(p.texto)); }
    this.cargado=true;
  },
};

// Normaliza entidad CMS + copy de landing (BD o curado) en el modelo que consume <Landing>.
function build(ent, c, eyebrow, titulo, origen, volver, comoLines, diag, antesFb, despuesFb, casos){
  const beneficios = (c.beneficios||[]).map(b=>({ icon:b.icon, t:L(b.t), x:L(b.x) }));
  const pasos = (c.pasos||[]).map(p=>({ t:L(p.t), x:L(p.x) }));
  const metricas = (c.metricas||[]).map(mt=>({ valor:mt.valor, suf:mt.suf||'', label:L(mt.label) }));
  const faqs = (c.faqs||[]).map(f=>({ q:L(f.q), a:L(f.a) }));
  let entregables = c.entregables ? (c.entregables[store.locale]||c.entregables.es||[]) : [];
  if((!entregables || !entregables.length) && comoLines && comoLines.length) entregables = comoLines;
  const proofOn = c.proof_casos !== false;
  const oferta = (c.oferta && c.oferta.on) ? { badge:L(c.oferta.badge), titulo:L(c.oferta.titulo), texto:L(c.oferta.texto), cta:L(c.oferta.cta) } : null;
  const testimonio = (c.testimonio && c.testimonio.on && L(c.testimonio.quote)) ? { quote:L(c.testimonio.quote), autor:c.testimonio.autor||'', cargo:L(c.testimonio.cargo) } : null;
  return {
    eyebrow, titulo, origen, volver, diag,
    promesa: L(c.promesa) || despuesFb || '',
    antes: L(c.antes) || antesFb || '',
    despues: L(c.despues) || despuesFb || '',
    cta: L(c.cta) || tx('landing.cta','Quiero más información'),
    beneficios, pasos, entregables, metricas, faqs, oferta, testimonio,
    casos: proofOn ? (casos||[]) : [],
  };
}

// ————————————————————— Caso como landing narrativa (story landing) —————————————————————
// Espíritu: contar el caso como una historia visual (texto + imagen/visual + motion),
// con la identidad de ExperientIA y respetando la confidencialidad del cliente.

// Visual de marca animado para capítulos sin imagen: bars (resultado),
// flow (intervención/proceso) o radar (reto/diagnóstico). Se anima al entrar en viewport.
const CaseViz = {
  components:{ Icon },
  props:{ variant:{ type:String, default:'bars' } },
  template:`<div class="caso-viz glass glass-lit" :class="'caso-viz--'+variant" ref="root" aria-hidden="true">
    <template v-if="variant==='bars'">
      <div class="vz-bars"><span v-for="(h,i) in [30,46,38,58,52,74,68,92]" :key="i" class="vz-bar" :style="{'--h':h+'%','--i':i}"></span></div>
      <span class="vz-trend"></span>
    </template>
    <template v-else-if="variant==='flow'">
      <div class="vz-flow">
        <span v-for="(ic,i) in ['analitica','ia','gear','growth']" :key="i" class="vz-node" :style="{'--i':i}"><Icon :name="ic" :size="20"/></span>
        <span class="vz-link"></span><span class="vz-pulse"></span></div>
    </template>
    <template v-else>
      <div class="vz-radar">
        <span class="vz-ring" v-for="i in 3" :key="i" :style="{'--i':i}"></span>
        <span class="vz-core"></span>
        <span v-for="(b,i) in [[22,30],[68,24],[58,70]]" :key="'b'+i" class="vz-blip" :style="{left:b[0]+'%',top:b[1]+'%','--i':i}"></span></div>
    </template>
  </div>`,
  mounted(){ this._stop = inView(this.$refs.root, ()=> this.$refs.root.classList.add('on'), { amount:.35 }); },
  unmounted(){ this._stop && this._stop(); },
};

// Copy curado trilingüe de la historia de cada caso sembrado (por slug ES).
// El JSON `landing` editado desde el panel siempre tiene prioridad sobre esto.
const CASO = {
  'del-reporte-semanal-al-pulso-diario': {
    confidencial:true,
    historia:[
      { viz:'radar', tag:{es:'El reto',en:'The challenge',pt:'O desafio'},
        t:{es:'Decidir a ciegas seis días a la semana',en:'Deciding blind six days a week',pt:'Decidir às cegas seis dias por semana'},
        x:{es:'Cada lunes llegaba un consolidado manual con la foto de la semana anterior. Precios e inventario se ajustaban tarde, tienda por tienda, y el margen se escapaba en los días que nadie estaba viendo.',en:'Every Monday a hand-built consolidation arrived with last week’s snapshot. Prices and inventory were adjusted late, store by store, and margin leaked away on the days nobody was watching.',pt:'Toda segunda-feira chegava um consolidado manual com a foto da semana anterior. Preços e estoque eram ajustados tarde, loja por loja, e a margem escapava nos dias em que ninguém estava olhando.'} },
      { viz:'flow', tag:{es:'La jugada',en:'The play',pt:'A jogada'},
        t:{es:'Un tablero conectado al pulso real del negocio',en:'A board connected to the real pulse of the business',pt:'Um painel conectado ao pulso real do negócio'},
        x:{es:'Conectamos ventas e inventario al Tablero de Crecimiento, automatizamos el pipeline de datos y entrenamos alertas de quiebre y rotación con IA. La conversación del comité pasó de «¿qué pasó?» a «¿qué hacemos hoy?».',en:'We connected sales and inventory to the Growth Board, automated the data pipeline and trained stock-out and rotation alerts with AI. The committee conversation shifted from “what happened?” to “what do we do today?”.',pt:'Conectamos vendas e estoque ao Tablero de Crecimiento, automatizamos o pipeline de dados e treinamos alertas de ruptura e giro com IA. A conversa do comitê passou de «o que aconteceu?» para «o que fazemos hoje?».'} },
      { viz:'bars', tag:{es:'El resultado',en:'The result',pt:'O resultado'},
        t:{es:'El margen apareció donde siempre estuvo escondido',en:'Margin showed up where it had always been hiding',pt:'A margem apareceu onde sempre esteve escondida'},
        x:{es:'Con el dato del día sobre la mesa, las categorías intervenidas recuperaron +31% de margen operativo y el equipo dejó de gastar la mitad de sus horas armando reportes.',en:'With the day’s data on the table, targeted categories recovered +31% operating margin and the team stopped spending half its hours building reports.',pt:'Com o dado do dia na mesa, as categorias trabalhadas recuperaram +31% de margem operacional e a equipe deixou de gastar metade de suas horas montando relatórios.'} },
    ],
    timeline:[
      { fase:{es:'Semanas 1–2',en:'Weeks 1–2',pt:'Semanas 1–2'}, t:{es:'Diagnóstico y mapa de datos',en:'Diagnostic and data map',pt:'Diagnóstico e mapa de dados'}, x:{es:'Fuentes, calidad y las decisiones críticas de precio e inventario.',en:'Sources, quality and the critical pricing and inventory decisions.',pt:'Fontes, qualidade e as decisões críticas de preço e estoque.'} },
      { fase:{es:'Semanas 3–6',en:'Weeks 3–6',pt:'Semanas 3–6'}, t:{es:'Pipeline automatizado',en:'Automated pipeline',pt:'Pipeline automatizado'}, x:{es:'Ventas e inventario fluyendo solos al tablero, sin planillas.',en:'Sales and inventory flowing into the board on their own, no spreadsheets.',pt:'Vendas e estoque fluindo sozinhos para o painel, sem planilhas.'} },
      { fase:{es:'Semanas 7–8',en:'Weeks 7–8',pt:'Semanas 7–8'}, t:{es:'Alertas con IA',en:'AI alerts',pt:'Alertas com IA'}, x:{es:'Quiebres y rotación anómala detectados antes de doler.',en:'Stock-outs and abnormal rotation detected before they hurt.',pt:'Rupturas e giro anômalo detectados antes de doer.'} },
      { fase:{es:'Desde el mes 3',en:'From month 3',pt:'A partir do mês 3'}, t:{es:'Ritmo de decisión diario',en:'Daily decision rhythm',pt:'Ritmo de decisão diário'}, x:{es:'Comité corto cada mañana, todos sobre el mismo tablero.',en:'A short committee every morning, everyone on the same board.',pt:'Comitê curto toda manhã, todos sobre o mesmo painel.'} },
    ],
    testimonio:{ on:true, quote:{es:'Por primera vez el lunes dejó de ser el día de descubrir lo que perdimos la semana pasada.',en:'For the first time, Monday stopped being the day we discover what we lost last week.',pt:'Pela primeira vez, a segunda-feira deixou de ser o dia de descobrir o que perdemos na semana passada.'}, autor:'', cargo:{es:'Dirección Comercial · cliente en confidencialidad',en:'Commercial Director · client under NDA',pt:'Direção Comercial · cliente em confidencialidade'} },
  },
  'onboarding-de-12-dias-a-48-horas': {
    confidencial:true,
    historia:[
      { viz:'radar', tag:{es:'El reto',en:'The challenge',pt:'O desafio'},
        t:{es:'Doce días para decir «bienvenido»',en:'Twelve days to say “welcome”',pt:'Doze dias para dizer «bem-vindo»'},
        x:{es:'Cada cuenta nueva pasaba por verificación documental manual y reprocesos entre áreas. El cliente esperaba doce días en promedio — y muchos no esperaban: se iban a la competencia.',en:'Every new account went through manual document verification and rework between areas. Customers waited twelve days on average — and many didn’t wait: they left for the competition.',pt:'Cada conta nova passava por verificação documental manual e retrabalhos entre áreas. O cliente esperava doze dias em média — e muitos não esperavam: iam para a concorrência.'} },
      { viz:'flow', tag:{es:'La jugada',en:'The play',pt:'A jogada'},
        t:{es:'Verificación con IA y casos límite orquestados',en:'AI verification and orchestrated edge cases',pt:'Verificação com IA e casos-limite orquestrados'},
        x:{es:'Automatizamos el flujo documental con IA, dejamos los casos límite en manos del equipo con el contexto completo, y pusimos la conversión de apertura en un tablero en tiempo real.',en:'We automated the document flow with AI, left edge cases in the team’s hands with full context, and put opening conversion on a real-time board.',pt:'Automatizamos o fluxo documental com IA, deixamos os casos-limite nas mãos da equipe com o contexto completo e colocamos a conversão de abertura em um painel em tempo real.'} },
      { viz:'bars', tag:{es:'El resultado',en:'The result',pt:'O resultado'},
        t:{es:'48 horas — y la operación escaló sin sumar personal',en:'48 hours — and operations scaled without adding headcount',pt:'48 horas — e a operação escalou sem somar pessoal'},
        x:{es:'El onboarding pasó de 12 días a 48 horas, el NPS de apertura subió 22 puntos y la capacidad de procesamiento se multiplicó por 3.4 con el mismo equipo.',en:'Onboarding went from 12 days to 48 hours, opening NPS rose 22 points and processing capacity multiplied by 3.4 with the same team.',pt:'O onboarding passou de 12 dias para 48 horas, o NPS de abertura subiu 22 pontos e a capacidade de processamento multiplicou por 3,4 com a mesma equipe.'} },
    ],
    timeline:[
      { fase:{es:'Semanas 1–2',en:'Weeks 1–2',pt:'Semanas 1–2'}, t:{es:'Mapa del flujo y sus fricciones',en:'Map of the flow and its frictions',pt:'Mapa do fluxo e suas fricções'}, x:{es:'Dónde se atascaba cada expediente y por qué.',en:'Where each file got stuck, and why.',pt:'Onde cada processo travava e por quê.'} },
      { fase:{es:'Semanas 3–8',en:'Weeks 3–8',pt:'Semanas 3–8'}, t:{es:'Automatización documental con IA',en:'AI document automation',pt:'Automação documental com IA'}, x:{es:'Verificación automática con trazabilidad completa.',en:'Automatic verification with full traceability.',pt:'Verificação automática com rastreabilidade completa.'} },
      { fase:{es:'Semanas 9–10',en:'Weeks 9–10',pt:'Semanas 9–10'}, t:{es:'Orquestación de casos límite',en:'Edge-case orchestration',pt:'Orquestração de casos-limite'}, x:{es:'Lo que exige criterio humano llega con contexto, no con carpetas.',en:'What needs human judgment arrives with context, not folders.',pt:'O que exige critério humano chega com contexto, não com pastas.'} },
      { fase:{es:'Mes 3',en:'Month 3',pt:'Mês 3'}, t:{es:'Conversión en vivo y ajuste continuo',en:'Live conversion and continuous tuning',pt:'Conversão ao vivo e ajuste contínuo'}, x:{es:'El tablero muestra dónde se gana y se pierde cada apertura.',en:'The board shows where each opening is won or lost.',pt:'O painel mostra onde cada abertura é ganha ou perdida.'} },
    ],
    testimonio:{ on:true, quote:{es:'Pasamos de pedir disculpas por la demora a sorprender al cliente con la cuenta lista.',en:'We went from apologizing for delays to surprising customers with the account ready.',pt:'Passamos de pedir desculpas pela demora a surpreender o cliente com a conta pronta.'}, autor:'', cargo:{es:'Gerencia de Operaciones · cliente en confidencialidad',en:'Operations Manager · client under NDA',pt:'Gerência de Operações · cliente em confidencialidade'} },
  },
  'anticiparse-a-la-parada-no-planificada': {
    confidencial:true,
    historia:[
      { viz:'radar', tag:{es:'El reto',en:'The challenge',pt:'O desafio'},
        t:{es:'La línea se detenía sin avisar',en:'The line stopped without warning',pt:'A linha parava sem avisar'},
        x:{es:'Cada parada no planificada erosionaba el margen y obligaba a mantener inventarios de seguridad costosos «por si acaso». El mantenimiento llegaba siempre después del daño.',en:'Every unplanned stop eroded margin and forced costly “just in case” safety stock. Maintenance always arrived after the damage.',pt:'Cada parada não planejada corroía a margem e obrigava a manter estoques de segurança caros «por via das dúvidas». A manutenção chegava sempre depois do dano.'} },
      { viz:'flow', tag:{es:'La jugada',en:'The play',pt:'A jogada'},
        t:{es:'Los sensores que ya existían empezaron a hablar',en:'The sensors that already existed started talking',pt:'Os sensores que já existiam começaram a falar'},
        x:{es:'Sin comprar hardware nuevo: analítica predictiva sobre los sensores existentes, alertas tempranas integradas al flujo de mantenimiento y un tablero de salud operativa por línea.',en:'No new hardware: predictive analytics on existing sensors, early alerts integrated into the maintenance flow and an operational-health board per line.',pt:'Sem comprar hardware novo: analítica preditiva sobre os sensores existentes, alertas antecipados integrados ao fluxo de manutenção e um painel de saúde operacional por linha.'} },
      { viz:'bars', tag:{es:'El resultado',en:'The result',pt:'O resultado'},
        t:{es:'Menos paradas, menos inventario, inversión recuperada en 6 meses',en:'Fewer stops, less inventory, investment recovered in 6 months',pt:'Menos paradas, menos estoque, investimento recuperado em 6 meses'},
        x:{es:'Las horas de parada no planificada cayeron 38%, el inventario de seguridad se redujo 17% y el programa se pagó solo en seis meses.',en:'Unplanned downtime hours fell 38%, safety stock dropped 17% and the program paid for itself in six months.',pt:'As horas de parada não planejada caíram 38%, o estoque de segurança reduziu 17% e o programa se pagou sozinho em seis meses.'} },
    ],
    timeline:[
      { fase:{es:'Semanas 1–3',en:'Weeks 1–3',pt:'Semanas 1–3'}, t:{es:'Auditoría de sensores y datos',en:'Sensor and data audit',pt:'Auditoria de sensores e dados'}, x:{es:'Qué señales ya existen y qué historia cuentan las fallas.',en:'What signals already exist and what story the failures tell.',pt:'Quais sinais já existem e que história as falhas contam.'} },
      { fase:{es:'Semanas 4–8',en:'Weeks 4–8',pt:'Semanas 4–8'}, t:{es:'Modelos predictivos por línea',en:'Predictive models per line',pt:'Modelos preditivos por linha'}, x:{es:'Entrenados con el histórico real de la planta.',en:'Trained on the plant’s real history.',pt:'Treinados com o histórico real da planta.'} },
      { fase:{es:'Semanas 9–12',en:'Weeks 9–12',pt:'Semanas 9–12'}, t:{es:'Alertas en el flujo de mantenimiento',en:'Alerts in the maintenance flow',pt:'Alertas no fluxo de manutenção'}, x:{es:'La predicción se vuelve orden de trabajo, no un correo más.',en:'Prediction becomes a work order, not another email.',pt:'A predição vira ordem de serviço, não mais um e-mail.'} },
      { fase:{es:'Mes 6',en:'Month 6',pt:'Mês 6'}, t:{es:'Inversión recuperada',en:'Investment recovered',pt:'Investimento recuperado'}, x:{es:'El programa se paga con las paradas que ya no ocurren.',en:'The program pays for itself with the stops that no longer happen.',pt:'O programa se paga com as paradas que já não acontecem.'} },
    ],
    testimonio:{ on:true, quote:{es:'Hoy el mantenimiento llega antes que la falla. Eso cambió la economía de toda la planta.',en:'Today maintenance arrives before the failure. That changed the economics of the whole plant.',pt:'Hoje a manutenção chega antes da falha. Isso mudou a economia de toda a planta.'}, autor:'', cargo:{es:'Dirección de Planta · cliente en confidencialidad',en:'Plant Director · client under NDA',pt:'Direção de Planta · cliente em confidencialidade'} },
  },
};

export const CasoDetalle = {
  components:{ Icon, CountUp, ScrollProgress, LeadModal, NoEncontrado, CaseViz },
  template:`<div v-if="c" class="caso-landing">
    <ScrollProgress/>

    <section class="caso-hero"><div class="bg-atmos">
      <div class="halo halo-cyan anim-pulse" style="width:640px;height:640px;top:-280px;right:-160px"></div>
      <div class="halo halo-violet anim-pulse" style="width:520px;height:520px;bottom:-220px;left:-200px;animation-delay:2.4s"></div>
      <span class="caso-orb" style="top:18%;left:6%;--s:10px;--d:0s"></span>
      <span class="caso-orb" style="top:64%;left:12%;--s:6px;--d:1.6s"></span>
      <span class="caso-orb" style="top:30%;right:8%;--s:8px;--d:.8s"></span></div>
      <div class="container caso-hero__in">
        <div class="caso-hero__copy">
          <div class="caso-chips" v-reveal>
            <span class="chip">{{ tr(c.sector) }}</span>
            <span v-if="ld.confidencial" class="chip chip--conf"><Icon name="shield" :size="12"/> {{ tx('caso.conf','Caso real · cliente en confidencialidad') }}</span></div>
          <h1 class="display" v-reveal :style="{'--d':'.08s'}">{{ tr(c.titulo) }}</h1>
          <p class="lead" v-reveal :style="{'--d':'.16s'}">{{ sub }}</p>
          <div class="land-cta-row" v-reveal :style="{'--d':'.24s'}">
            <button class="btn btn-grad" @click="modal=true">{{ tx('caso.cta','Quiero resultados así') }}</button>
            <router-link :to="pageUrl('diagnostico')" class="btn btn-ghost">{{ tx('landing.oferta_cta','Quiero mi diagnóstico gratis') }}</router-link></div></div>
        <div class="glass glass-lit caso-hero__star" v-reveal :style="{'--d':'.2s'}" v-if="star">
          <span class="caso-star-val grad-text"><CountUp :value="star.valor"/></span>
          <span class="caso-star-lbl">{{ tr(star.label) }}</span>
          <div class="caso-star-mini"><div v-for="(r,i) in resto" :key="i"><b>{{ r.valor }}</b><span>{{ tr(r.label) }}</span></div></div></div></div>
      <div class="scroll-cue" aria-hidden="true"><span></span></div></section>

    <section class="section caso-historia-sec"><div class="container">
      <div class="section-head"><p class="eyebrow" v-reveal>{{ tx('caso.historia_e','La historia') }}</p>
        <h2 class="h2" v-reveal :style="{'--d':'.06s'}">{{ tx('caso.historia_t','Del problema al resultado, jugada a jugada') }}</h2></div>
      <article v-for="(h,i) in historia" :key="i" class="caso-cap" :class="{'caso-cap--rev': i%2===1}">
        <div class="caso-cap__txt" v-reveal>
          <div class="caso-cap__head"><span class="caso-cap__num grad-text">{{ ('0'+(i+1)).slice(-2) }}</span><span class="caso-cap__tag">{{ h.tag }}</span></div>
          <h3 class="h2">{{ h.t }}</h3><p class="lead">{{ h.x }}</p></div>
        <div class="caso-cap__media" v-reveal :style="{'--d':'.12s'}">
          <img v-if="h.img" :src="h.img" alt="" loading="lazy" class="glass caso-cap__img">
          <CaseViz v-else :variant="h.viz||'bars'"/></div></article></div></section>

    <section class="section caso-cifras-sec"><div class="bg-atmos"><div class="halo halo-cyan" style="width:460px;height:460px;top:-160px;right:-220px;opacity:.24"></div></div>
      <div class="container"><div class="glass glass-lit card land-metrics caso-metrics" v-reveal>
        <div v-for="(r,i) in c.resultados" :key="i" class="metric"><span class="metric-val grad-text"><CountUp :value="r.valor"/></span><span class="metric-lbl">{{ tr(r.label) }}</span></div></div></div></section>

    <section class="section caso-tl-sec" v-if="timeline.length"><div class="container">
      <div class="section-head"><p class="eyebrow" v-reveal>{{ tx('caso.tl_e','El camino') }}</p>
        <h2 class="h2" v-reveal :style="{'--d':'.06s'}">{{ tx('caso.tl_t','Así se construyó, semana a semana') }}</h2></div>
      <ol class="caso-tl">
        <li v-for="(s,i) in timeline" :key="i" class="caso-tl__item" v-reveal :style="{'--d':i*.1+'s'}">
          <span class="caso-tl__dot"></span>
          <span class="caso-tl__fase">{{ s.fase }}</span>
          <h3 class="h3">{{ s.t }}</h3><p>{{ s.x }}</p></li></ol></div></section>

    <section class="section" v-if="ld.beneficios && ld.beneficios.length"><div class="container">
      <div class="section-head"><h2 class="h2" v-reveal>{{ tx('landing.benes_t','Lo que cambia para ti') }}</h2></div>
      <div class="grid grid-3 land-benes">
        <article v-for="(b,i) in ld.beneficios" :key="i" class="glass card bene-card" v-reveal :style="{'--d':i*.09+'s'}">
          <span class="icon-chip"><Icon :name="b.icon||'check'"/></span><h3 class="h3">{{ b.t }}</h3><p>{{ b.x }}</p></article></div></div></section>

    <section class="section" v-if="ld.testimonio"><div class="container" style="max-width:820px">
      <figure class="glass glass-lit card land-quote" v-reveal><Icon name="bulb" :size="22"/>
        <blockquote>“{{ ld.testimonio.quote }}”</blockquote>
        <figcaption v-if="ld.testimonio.autor||ld.testimonio.cargo"><b>{{ ld.testimonio.autor }}</b><span v-if="ld.testimonio.cargo"><template v-if="ld.testimonio.autor"> · </template>{{ ld.testimonio.cargo }}</span></figcaption></figure></div></section>

    <section class="section" v-if="ld.faqs && ld.faqs.length"><div class="container land-faqs">
      <div class="section-head"><h2 class="h2" v-reveal>{{ tx('landing.faq_t','Antes de que preguntes') }}</h2></div>
      <div class="faq-list"><details v-for="(f,i) in ld.faqs" :key="i" class="glass faq-item" v-reveal :open="i===0"><summary>{{ f.q }}</summary><p>{{ f.a }}</p></details></div></div></section>

    <section class="section"><div class="container"><div class="glass glass-lit card land-final" v-reveal>
      <h2 class="h2">{{ tx('caso.final_t','Los resultados no son suerte: son método') }}</h2>
      <p class="lead">{{ tx('caso.final_s','Apliquémoslo a tu empresa. Empieza con un diagnóstico ejecutivo gratuito.') }}</p>
      <div class="land-cta-row"><button class="btn btn-grad" @click="modal=true">{{ tx('caso.cta','Quiero resultados así') }}</button>
        <router-link :to="pageUrl('casos')" class="btn btn-ghost">{{ tx('caso.ver_mas','Ver más casos') }}</router-link></div></div></div></section>

    <div class="land-sticky"><span>{{ tr(c.titulo) }}</span><button class="btn btn-grad btn-sm" @click="modal=true">{{ tx('caso.cta_corto','Quiero esto') }}</button></div>
    <LeadModal v-if="modal" :titulo="tr(c.titulo)" :origen="'caso:'+slug" @close="modal=false"/>
  </div><NoEncontrado v-else-if="cargado" volver="casos"/>`,
  data(){ return { c:null, cargado:false, modal:false, slug:'' }; },
  computed:{ t:()=>t, tr:()=>tr, pageUrl:()=>pageUrl,
    // Fusión: JSON `landing` del CMS (prioridad) → copy curado (CASO) → vacío.
    // El copy curado se indexa por el slug ES del título (estable entre idiomas).
    base(){ const db=(this.c&&this.c.landing&&typeof this.c.landing==='object')?this.c.landing:{};
      const esKey=slugify((this.c&&this.c.titulo&&this.c.titulo.es)||''); const cur=CASO[esKey]||{};
      return { ...cur, ...db, historia:(db.historia&&db.historia.length)?db.historia:(cur.historia||[]),
        timeline:(db.timeline&&db.timeline.length)?db.timeline:(cur.timeline||[]),
        testimonio:(db.testimonio&&db.testimonio.on)?db.testimonio:(cur.testimonio||null),
        confidencial:(db.confidencial!==undefined)?db.confidencial:!!cur.confidencial }; },
    ld(){ const c=this.base;
      return { promesa:L(c.promesa), confidencial:!!c.confidencial,
        beneficios:(c.beneficios||[]).map(b=>({ icon:b.icon, t:L(b.t), x:L(b.x) })).filter(b=>b.t),
        faqs:(c.faqs||[]).map(f=>({ q:L(f.q), a:L(f.a) })).filter(f=>f.q),
        testimonio:(c.testimonio&&c.testimonio.on&&L(c.testimonio.quote))?{ quote:L(c.testimonio.quote), autor:c.testimonio.autor||'', cargo:L(c.testimonio.cargo) }:null }; },
    // Capítulos de la historia; si no hay curados ni editados, se arman del contexto/intervención del CMS.
    historia(){ const hs=(this.base.historia||[]).map(h=>({ tag:L(h.tag), t:L(h.t), x:L(h.x), viz:h.viz||'bars', img:h.img||'' })).filter(h=>h.x);
      if(hs.length) return hs;
      return [
        { tag:tx('caso.reto','El reto'), t:tx('caso.contexto','El contexto'), x:tr(this.c.contexto), viz:'radar', img:'' },
        { tag:tx('caso.jugada','La jugada'), t:tx('caso.intervencion','Qué hicimos'), x:tr(this.c.intervencion), viz:'flow', img:'' },
      ].filter(h=>h.x); },
    timeline(){ return (this.base.timeline||[]).map(s=>({ fase:L(s.fase), t:L(s.t), x:L(s.x) })).filter(s=>s.t); },
    star(){ return (this.c.resultados||[])[0]||null; },
    resto(){ return (this.c.resultados||[]).slice(1,3); },
    sub(){ if(!this.c) return ''; return this.ld.promesa || (tr(this.c.contexto).split('.')[0]+'.'); } },
  methods:{ tx },
  async mounted(){ this.slug=this.$route.params.slug; const items=await fc('casos');
    this.c=items.find(x=>slugify(tr(x.titulo,'es'))===this.slug)||null; this.cargado=true;
    if(this.c) setMeta(tr(this.c.titulo)+' · ExperientIA', tr(this.c.contexto).slice(0,150)); },
};
