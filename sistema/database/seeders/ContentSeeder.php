<?php

namespace Database\Seeders;

use App\Models\CaseStudy;
use App\Models\Faq;
use App\Models\Product;
use App\Models\Solution;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->solutions();
        $this->products();
        $this->cases();
        $this->faqs();
    }

    private function solutions(): void
    {
        $items = [
            [
                'key' => 'estrategia', 'icon' => 'target', 'sort' => 1,
                'titulo' => ['es' => 'Estrategia & Roadmap de IA', 'en' => 'AI Strategy & Roadmap', 'pt' => 'Estratégia & Roadmap de IA'],
                'pilar' => ['es' => 'Claridad estratégica', 'en' => 'Strategic clarity', 'pt' => 'Clareza estratégica'],
                'problema' => [
                    'es' => 'La dirección sabe que la IA importa, pero no dónde empezar ni cómo priorizar la inversión.',
                    'en' => 'Leadership knows AI matters, but not where to start or how to prioritize the investment.',
                    'pt' => 'A diretoria sabe que a IA importa, mas não sabe por onde começar nem como priorizar o investimento.',
                ],
                'como' => [
                    'es' => "Diagnóstico de madurez en datos, procesos y talento.\nPriorización por impacto en P&L y viabilidad real.\nRoadmap ejecutivo con hitos, dueños y métricas.",
                    'en' => "Maturity diagnostic across data, processes and talent.\nPrioritization by P&L impact and real feasibility.\nExecutive roadmap with milestones, owners and metrics.",
                    'pt' => "Diagnóstico de maturidade em dados, processos e talento.\nPriorização por impacto no resultado e viabilidade real.\nRoadmap executivo com marcos, donos e métricas.",
                ],
                'cambia' => [
                    'es' => 'El comité pasa de conversaciones abstractas sobre IA a una ruta de inversión con retornos definidos.',
                    'en' => 'The committee moves from abstract AI conversations to an investment route with defined returns.',
                    'pt' => 'O comitê passa de conversas abstratas sobre IA a uma rota de investimento com retornos definidos.',
                ],
            ],
            [
                'key' => 'automatizacion', 'icon' => 'gear', 'sort' => 2,
                'titulo' => ['es' => 'Automatización de operaciones', 'en' => 'Operations automation', 'pt' => 'Automação de operações'],
                'pilar' => ['es' => 'Innovación aplicada', 'en' => 'Applied innovation', 'pt' => 'Inovação aplicada'],
                'problema' => [
                    'es' => 'Procesos manuales que consumen talento senior, generan errores y no escalan con el negocio.',
                    'en' => 'Manual processes that consume senior talent, generate errors and do not scale with the business.',
                    'pt' => 'Processos manuais que consomem talento sênior, geram erros e não escalam com o negócio.',
                ],
                'como' => [
                    'es' => "Mapeo de procesos críticos y cuellos de botella.\nAutomatización con IA donde el retorno lo justifica.\nOperación monitoreada con indicadores de eficiencia.",
                    'en' => "Mapping of critical processes and bottlenecks.\nAI-powered automation where the return justifies it.\nMonitored operations with efficiency indicators.",
                    'pt' => "Mapeamento de processos críticos e gargalos.\nAutomação com IA onde o retorno justifica.\nOperação monitorada com indicadores de eficiência.",
                ],
                'cambia' => [
                    'es' => 'Los equipos recuperan horas estratégicas y la operación escala sin escalar la nómina.',
                    'en' => 'Teams recover strategic hours and operations scale without scaling headcount.',
                    'pt' => 'As equipes recuperam horas estratégicas e a operação escala sem escalar a folha.',
                ],
            ],
            [
                'key' => 'datos', 'icon' => 'analitica', 'sort' => 3,
                'titulo' => ['es' => 'Inteligencia de datos', 'en' => 'Data intelligence', 'pt' => 'Inteligência de dados'],
                'pilar' => ['es' => 'Control con datos', 'en' => 'Control through data', 'pt' => 'Controle com dados'],
                'problema' => [
                    'es' => 'Datos dispersos en sistemas que no conversan. Reportes manuales que llegan tarde a las decisiones.',
                    'en' => 'Data scattered across systems that do not talk to each other. Manual reports that arrive late for decisions.',
                    'pt' => 'Dados dispersos em sistemas que não conversam. Relatórios manuais que chegam tarde às decisões.',
                ],
                'como' => [
                    'es' => "Consolidación de fuentes en una arquitectura simple.\nTableros ejecutivos con indicadores accionables.\nModelos predictivos donde anticipar vale dinero.",
                    'en' => "Consolidation of sources into a simple architecture.\nExecutive dashboards with actionable indicators.\nPredictive models where anticipating is worth money.",
                    'pt' => "Consolidação de fontes em uma arquitetura simples.\nPainéis executivos com indicadores acionáveis.\nModelos preditivos onde antecipar vale dinheiro.",
                ],
                'cambia' => [
                    'es' => 'La organización decide sobre una sola fuente de verdad, en ciclos de horas y no de semanas.',
                    'en' => 'The organization decides on one single source of truth, in cycles of hours instead of weeks.',
                    'pt' => 'A organização decide sobre uma única fonte de verdade, em ciclos de horas e não de semanas.',
                ],
            ],
            [
                'key' => 'growth', 'icon' => 'growth', 'sort' => 4,
                'titulo' => ['es' => 'Growth & experiencia de cliente', 'en' => 'Growth & customer experience', 'pt' => 'Growth & experiência do cliente'],
                'pilar' => ['es' => 'Crecimiento medible', 'en' => 'Measurable growth', 'pt' => 'Crescimento mensurável'],
                'problema' => [
                    'es' => 'Adquisición cara, retención frágil y una experiencia de cliente que no aprende de sus propios datos.',
                    'en' => 'Expensive acquisition, fragile retention and a customer experience that does not learn from its own data.',
                    'pt' => 'Aquisição cara, retenção frágil e uma experiência do cliente que não aprende com os próprios dados.',
                ],
                'como' => [
                    'es' => "Funnels instrumentados de punta a punta.\nPersonalización y scoring con IA aplicada.\nExperimentación continua con gobierno de métricas.",
                    'en' => "Funnels instrumented end to end.\nPersonalization and scoring with applied AI.\nContinuous experimentation with metric governance.",
                    'pt' => "Funis instrumentados de ponta a ponta.\nPersonalização e scoring com IA aplicada.\nExperimentação contínua com governança de métricas.",
                ],
                'cambia' => [
                    'es' => 'El crecimiento deja de depender de campañas aisladas y se vuelve un sistema que compone.',
                    'en' => 'Growth stops depending on isolated campaigns and becomes a compounding system.',
                    'pt' => 'O crescimento deixa de depender de campanhas isoladas e vira um sistema que compõe.',
                ],
            ],
        ];

        foreach ($items as $item) {
            Solution::updateOrCreate(['key' => $item['key']], $item);
        }
    }

    private function products(): void
    {
        if (Product::count() > 0) {
            return;
        }

        Product::insert(array_map(fn ($p) => array_merge($p, [
            'nombre' => json_encode($p['nombre'], JSON_UNESCAPED_UNICODE),
            'rol' => json_encode($p['rol'], JSON_UNESCAPED_UNICODE),
            'texto' => json_encode($p['texto'], JSON_UNESCAPED_UNICODE),
            'created_at' => now(), 'updated_at' => now(),
        ]), [
            [
                'icon' => 'analitica', 'destacado' => true, 'sort' => 1, 'active' => true,
                'nombre' => ['es' => 'Tablero de Crecimiento', 'en' => 'Growth Board', 'pt' => 'Painel de Crescimento'],
                'rol' => ['es' => 'El activo central', 'en' => 'The central asset', 'pt' => 'O ativo central'],
                'texto' => [
                    'es' => 'Centro de comando ejecutivo con indicadores en vivo, alertas inteligentes y gobierno de métricas.',
                    'en' => 'Executive command center with live indicators, intelligent alerts and metric governance.',
                    'pt' => 'Centro de comando executivo com indicadores ao vivo, alertas inteligentes e governança de métricas.',
                ],
            ],
            [
                'icon' => 'target', 'destacado' => false, 'sort' => 2, 'active' => true,
                'nombre' => ['es' => 'Diagnóstico Ejecutivo', 'en' => 'Executive Diagnostic', 'pt' => 'Diagnóstico Executivo'],
                'rol' => ['es' => 'El punto de partida', 'en' => 'The starting point', 'pt' => 'O ponto de partida'],
                'texto' => [
                    'es' => 'Evaluación de madurez en datos, procesos y talento, con roadmap priorizado por impacto en el negocio.',
                    'en' => 'Maturity assessment across data, processes and talent, with a roadmap prioritized by business impact.',
                    'pt' => 'Avaliação de maturidade em dados, processos e talento, com roadmap priorizado por impacto no negócio.',
                ],
            ],
            [
                'icon' => 'gear', 'destacado' => false, 'sort' => 3, 'active' => true,
                'nombre' => ['es' => 'Programas de Automatización', 'en' => 'Automation Programs', 'pt' => 'Programas de Automação'],
                'rol' => ['es' => 'La ejecución', 'en' => 'The execution', 'pt' => 'A execução'],
                'texto' => [
                    'es' => 'Ciclos de implementación de 90 días por área: operaciones, finanzas, experiencia de cliente.',
                    'en' => '90-day implementation cycles by area: operations, finance, customer experience.',
                    'pt' => 'Ciclos de implementação de 90 dias por área: operações, finanças, experiência do cliente.',
                ],
            ],
            [
                'icon' => 'people', 'destacado' => false, 'sort' => 4, 'active' => true,
                'nombre' => ['es' => 'Academia Ejecutiva de IA', 'en' => 'AI Executive Academy', 'pt' => 'Academia Executiva de IA'],
                'rol' => ['es' => 'La cultura', 'en' => 'The culture', 'pt' => 'A cultura'],
                'texto' => [
                    'es' => 'Programas para directorios y equipos líderes: criterio, adopción y gobierno de la IA en el negocio.',
                    'en' => 'Programs for boards and leadership teams: judgment, adoption and governance of AI in the business.',
                    'pt' => 'Programas para conselhos e equipes líderes: critério, adoção e governança da IA no negócio.',
                ],
            ],
        ]));
    }

    private function cases(): void
    {
        if (CaseStudy::count() > 0) {
            return;
        }

        $cases = [
            [
                'sort' => 1,
                'sector' => ['es' => 'Retail regional · 40+ tiendas', 'en' => 'Regional retail · 40+ stores', 'pt' => 'Varejo regional · 40+ lojas'],
                'titulo' => ['es' => 'Del reporte semanal al pulso diario', 'en' => 'From weekly reports to a daily pulse', 'pt' => 'Do relatório semanal ao pulso diário'],
                'contexto' => [
                    'es' => 'Una cadena de retail decidía precios e inventario con reportes manuales que llegaban cada lunes, consolidados a mano desde las tiendas.',
                    'en' => 'A retail chain made pricing and inventory decisions from manual reports that arrived every Monday, consolidated by hand from the stores.',
                    'pt' => 'Uma rede de varejo decidia preços e estoque com relatórios manuais que chegavam toda segunda-feira, consolidados à mão pelas lojas.',
                ],
                'intervencion' => [
                    'es' => 'Tablero de Crecimiento conectado a ventas e inventario, automatización del pipeline de datos y alertas de quiebre y rotación con IA.',
                    'en' => 'Growth Board connected to sales and inventory, automated data pipeline, and AI-powered stock-out and rotation alerts.',
                    'pt' => 'Painel de Crescimento conectado a vendas e estoque, automação do pipeline de dados e alertas de ruptura e giro com IA.',
                ],
                'resultados' => [
                    ['valor' => '+31%', 'label' => ['es' => 'margen operativo en categorías intervenidas', 'en' => 'operating margin in targeted categories', 'pt' => 'margem operacional nas categorias trabalhadas']],
                    ['valor' => '24 h', 'label' => ['es' => 'de dato a decisión (antes: 7 días)', 'en' => 'from data to decision (before: 7 days)', 'pt' => 'de dado a decisão (antes: 7 dias)']],
                    ['valor' => '-52%', 'label' => ['es' => 'horas de reporting manual', 'en' => 'hours of manual reporting', 'pt' => 'horas de reporte manual']],
                ],
            ],
            [
                'sort' => 2,
                'sector' => ['es' => 'Servicios financieros', 'en' => 'Financial services', 'pt' => 'Serviços financeiros'],
                'titulo' => ['es' => 'Onboarding de 12 días a 48 horas', 'en' => 'Onboarding from 12 days to 48 hours', 'pt' => 'Onboarding de 12 dias para 48 horas'],
                'contexto' => [
                    'es' => 'Una entidad financiera perdía clientes en un onboarding de 12 días promedio, con verificación documental manual y reprocesos constantes.',
                    'en' => 'A financial institution was losing customers to a 12-day average onboarding, with manual document verification and constant rework.',
                    'pt' => 'Uma instituição financeira perdia clientes em um onboarding de 12 dias em média, com verificação documental manual e retrabalho constante.',
                ],
                'intervencion' => [
                    'es' => 'Automatización del flujo de verificación con IA documental, orquestación de casos límite y tablero de conversión en tiempo real.',
                    'en' => 'Automation of the verification flow with document AI, edge-case orchestration and a real-time conversion board.',
                    'pt' => 'Automação do fluxo de verificação com IA documental, orquestração de casos-limite e painel de conversão em tempo real.',
                ],
                'resultados' => [
                    ['valor' => '48 h', 'label' => ['es' => 'tiempo de onboarding promedio', 'en' => 'average onboarding time', 'pt' => 'tempo médio de onboarding']],
                    ['valor' => '+22', 'label' => ['es' => 'puntos de NPS en apertura de cuentas', 'en' => 'NPS points on account opening', 'pt' => 'pontos de NPS na abertura de contas']],
                    ['valor' => '3.4x', 'label' => ['es' => 'capacidad de procesamiento sin sumar personal', 'en' => 'processing capacity without adding headcount', 'pt' => 'capacidade de processamento sem aumentar equipe']],
                ],
            ],
            [
                'sort' => 3,
                'sector' => ['es' => 'Manufactura industrial', 'en' => 'Industrial manufacturing', 'pt' => 'Manufatura industrial'],
                'titulo' => ['es' => 'Anticiparse a la parada no planificada', 'en' => 'Getting ahead of unplanned downtime', 'pt' => 'Antecipar a parada não planejada'],
                'contexto' => [
                    'es' => 'Paradas no planificadas de línea erosionaban el margen y obligaban a mantener inventarios de seguridad costosos.',
                    'en' => 'Unplanned line stoppages eroded margin and forced the company to keep costly safety inventory.',
                    'pt' => 'Paradas não planejadas de linha corroíam a margem e obrigavam a manter estoques de segurança caros.',
                ],
                'intervencion' => [
                    'es' => 'Analítica predictiva sobre sensores existentes, alertas tempranas integradas al flujo de mantenimiento y tablero de salud operativa.',
                    'en' => 'Predictive analytics on existing sensors, early alerts integrated into the maintenance flow and an operational health board.',
                    'pt' => 'Analítica preditiva sobre sensores existentes, alertas antecipados integrados ao fluxo de manutenção e painel de saúde operacional.',
                ],
                'resultados' => [
                    ['valor' => '-38%', 'label' => ['es' => 'horas de parada no planificada', 'en' => 'hours of unplanned downtime', 'pt' => 'horas de parada não planejada']],
                    ['valor' => '-17%', 'label' => ['es' => 'inventario de seguridad', 'en' => 'safety inventory', 'pt' => 'estoque de segurança']],
                    ['valor' => '6 m', 'label' => ['es' => 'para recuperar la inversión del programa', 'en' => 'to recover the program investment', 'pt' => 'para recuperar o investimento do programa']],
                ],
            ],
        ];

        foreach ($cases as $c) {
            CaseStudy::create($c + ['active' => true]);
        }
    }

    private function faqs(): void
    {
        if (Faq::count() > 0) {
            return;
        }

        $faqs = [
            [
                'pregunta' => [
                    'es' => '¿Qué hace exactamente ExperientIA?',
                    'en' => 'What exactly does ExperientIA do?',
                    'pt' => 'O que exatamente a ExperientIA faz?',
                ],
                'respuesta' => [
                    'es' => 'Somos una firma de crecimiento empresarial: traducimos estrategia, automatización e inteligencia artificial en resultados medibles. No vendemos herramientas sueltas; diseñamos e implementamos sistemas que mejoran el P&L, con método y acompañamiento ejecutivo.',
                    'en' => 'We are a business growth firm: we translate strategy, automation and artificial intelligence into measurable results. We do not sell scattered tools; we design and implement systems that improve the P&L, with method and executive guidance.',
                    'pt' => 'Somos uma firma de crescimento empresarial: traduzimos estratégia, automação e inteligência artificial em resultados mensuráveis. Não vendemos ferramentas soltas; desenhamos e implementamos sistemas que melhoram o resultado, com método e acompanhamento executivo.',
                ],
            ],
            [
                'pregunta' => [
                    'es' => '¿En cuánto tiempo se ven resultados?',
                    'en' => 'How soon do results show?',
                    'pt' => 'Em quanto tempo aparecem os resultados?',
                ],
                'respuesta' => [
                    'es' => 'Trabajamos por ciclos de 90 días con resultados visibles desde el primer trimestre. El diagnóstico inicial define los quick wins: automatizaciones y tableros que generan valor en semanas, mientras se construye la transformación de fondo.',
                    'en' => 'We work in 90-day cycles with visible results from the first quarter. The initial diagnostic defines the quick wins: automations and boards that generate value within weeks, while the deeper transformation is built.',
                    'pt' => 'Trabalhamos em ciclos de 90 dias com resultados visíveis desde o primeiro trimestre. O diagnóstico inicial define os quick wins: automações e painéis que geram valor em semanas, enquanto a transformação de fundo é construída.',
                ],
            ],
            [
                'pregunta' => [
                    'es' => '¿Necesito tener mis datos "perfectos" para empezar?',
                    'en' => 'Do I need "perfect" data to start?',
                    'pt' => 'Preciso ter dados "perfeitos" para começar?',
                ],
                'respuesta' => [
                    'es' => 'No. Ninguna empresa los tiene. El diagnóstico evalúa el estado real de sus datos y el roadmap incluye ponerlos al nivel necesario para cada caso de uso. Empezar con datos imperfectos es normal; lo importante es empezar con criterio.',
                    'en' => 'No. No company has perfect data. The diagnostic assesses the real state of your data, and the roadmap includes bringing it to the level each use case requires. Starting with imperfect data is normal; what matters is starting with judgment.',
                    'pt' => 'Não. Nenhuma empresa os tem. O diagnóstico avalia o estado real dos seus dados e o roadmap inclui colocá-los no nível necessário para cada caso de uso. Começar com dados imperfeitos é normal; o importante é começar com critério.',
                ],
            ],
            [
                'pregunta' => [
                    'es' => '¿Trabajan con empresas de cualquier tamaño e industria?',
                    'en' => 'Do you work with companies of any size and industry?',
                    'pt' => 'Vocês trabalham com empresas de qualquer porte e indústria?',
                ],
                'respuesta' => [
                    'es' => 'Trabajamos con empresas desde pequeñas en crecimiento hasta corporativos, principalmente en retail, servicios financieros, salud, manufactura y servicios profesionales. Lo determinante no es el tamaño sino la decisión de la dirección de crecer con método.',
                    'en' => 'We work with companies from growing small businesses to corporates, mainly in retail, financial services, healthcare, manufacturing and professional services. What matters is not the size but leadership’s decision to grow with method.',
                    'pt' => 'Trabalhamos com empresas desde pequenas em crescimento até corporações, principalmente em varejo, serviços financeiros, saúde, manufatura e serviços profissionais. O determinante não é o porte, mas a decisão da diretoria de crescer com método.',
                ],
            ],
            [
                'pregunta' => [
                    'es' => '¿Cómo se mide el retorno de la inversión?',
                    'en' => 'How is the return on investment measured?',
                    'pt' => 'Como se mede o retorno do investimento?',
                ],
                'respuesta' => [
                    'es' => 'Cada iniciativa se define con métricas de negocio antes de implementarse: margen, horas recuperadas, conversión, tiempos de ciclo. El Tablero de Crecimiento las monitorea en vivo, así el retorno no es una promesa sino un indicador que usted ve.',
                    'en' => 'Every initiative is defined with business metrics before implementation: margin, hours recovered, conversion, cycle times. The Growth Board monitors them live, so the return is not a promise but an indicator you can see.',
                    'pt' => 'Cada iniciativa é definida com métricas de negócio antes de ser implementada: margem, horas recuperadas, conversão, tempos de ciclo. O Painel de Crescimento as monitora ao vivo, então o retorno não é uma promessa, é um indicador que você vê.',
                ],
            ],
            [
                'pregunta' => [
                    'es' => '¿Qué incluye el diagnóstico sin costo?',
                    'en' => 'What does the free diagnostic include?',
                    'pt' => 'O que inclui o diagnóstico sem custo?',
                ],
                'respuesta' => [
                    'es' => 'Una lectura ejecutiva de su punto de partida: dónde está perdiendo eficiencia, qué oportunidades de IA y automatización tienen mayor retorno probable y cuáles serían los primeros pasos. Se entrega en una sesión de 30–45 minutos, sin compromiso.',
                    'en' => 'An executive reading of your starting point: where you are losing efficiency, which AI and automation opportunities have the highest likely return, and what the first steps would be. Delivered in a 30–45 minute session, no commitment.',
                    'pt' => 'Uma leitura executiva do seu ponto de partida: onde você está perdendo eficiência, quais oportunidades de IA e automação têm maior retorno provável e quais seriam os primeiros passos. Entregue em uma sessão de 30–45 minutos, sem compromisso.',
                ],
            ],
            [
                'pregunta' => [
                    'es' => '¿La IA va a reemplazar a mi equipo?',
                    'en' => 'Will AI replace my team?',
                    'pt' => 'A IA vai substituir a minha equipe?',
                ],
                'respuesta' => [
                    'es' => 'Nuestro enfoque es liberar a las personas de lo repetitivo para que hagan trabajo de mayor valor. La automatización bien diseñada amplifica al equipo: menos horas en tareas manuales, más capacidad para atender clientes, analizar y decidir. La adopción y la cultura son parte del programa.',
                    'en' => 'Our approach is to free people from repetitive work so they can do higher-value work. Well-designed automation amplifies the team: fewer hours on manual tasks, more capacity to serve customers, analyze and decide. Adoption and culture are part of the program.',
                    'pt' => 'Nossa abordagem é liberar as pessoas do repetitivo para que façam trabalho de maior valor. A automação bem desenhada amplifica a equipe: menos horas em tarefas manuais, mais capacidade para atender clientes, analisar e decidir. Adoção e cultura fazem parte do programa.',
                ],
            ],
            [
                'pregunta' => [
                    'es' => '¿Cómo protegen la información de mi empresa?',
                    'en' => 'How do you protect my company’s information?',
                    'pt' => 'Como vocês protegem as informações da minha empresa?',
                ],
                'respuesta' => [
                    'es' => 'Con confidencialidad desde el primer contacto (acuerdos de confidencialidad cuando se requieren), acceso mínimo necesario a sistemas y datos, y arquitecturas que mantienen su información bajo su control. La seguridad es un criterio de diseño, no un anexo.',
                    'en' => 'With confidentiality from first contact (NDAs where required), least-necessary access to systems and data, and architectures that keep your information under your control. Security is a design criterion, not an appendix.',
                    'pt' => 'Com confidencialidade desde o primeiro contato (acordos de confidencialidade quando necessários), acesso mínimo necessário a sistemas e dados, e arquiteturas que mantêm suas informações sob seu controle. Segurança é critério de projeto, não um anexo.',
                ],
            ],
        ];

        foreach ($faqs as $i => $f) {
            Faq::create($f + ['sort' => $i + 1, 'active' => true]);
        }
    }
}
