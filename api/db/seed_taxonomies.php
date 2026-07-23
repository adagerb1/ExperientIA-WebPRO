<?php
/**
 * Semilla de taxonomías: industrias (trilingüe) y países (ISO 3166-1, desde
 * config/paises.php). Usada por install.php y migrate.php.
 */
$industrias = [
    'tecnologia' => ['es' => 'Tecnología y software', 'en' => 'Technology & software', 'pt' => 'Tecnologia e software'],
    'retail' => ['es' => 'Retail y comercio', 'en' => 'Retail & commerce', 'pt' => 'Varejo e comércio'],
    'financiero' => ['es' => 'Servicios financieros', 'en' => 'Financial services', 'pt' => 'Serviços financeiros'],
    'salud' => ['es' => 'Salud', 'en' => 'Healthcare', 'pt' => 'Saúde'],
    'manufactura' => ['es' => 'Manufactura', 'en' => 'Manufacturing', 'pt' => 'Manufatura'],
    'educacion' => ['es' => 'Educación', 'en' => 'Education', 'pt' => 'Educação'],
    'logistica' => ['es' => 'Logística y transporte', 'en' => 'Logistics & transport', 'pt' => 'Logística e transporte'],
    'agroindustria' => ['es' => 'Agroindustria', 'en' => 'Agribusiness', 'pt' => 'Agroindústria'],
    'turismo' => ['es' => 'Turismo y hospitalidad', 'en' => 'Tourism & hospitality', 'pt' => 'Turismo e hospitalidade'],
    'profesionales' => ['es' => 'Servicios profesionales', 'en' => 'Professional services', 'pt' => 'Serviços profissionais'],
    'construccion' => ['es' => 'Construcción e inmobiliario', 'en' => 'Construction & real estate', 'pt' => 'Construção e imobiliário'],
    'energia' => ['es' => 'Energía', 'en' => 'Energy', 'pt' => 'Energia'],
    'gobierno' => ['es' => 'Gobierno y ONG', 'en' => 'Government & NGO', 'pt' => 'Governo e ONG'],
    'medios' => ['es' => 'Medios y marketing', 'en' => 'Media & marketing', 'pt' => 'Mídia e marketing'],
    'otro' => ['es' => 'Otra industria', 'en' => 'Other industry', 'pt' => 'Outra indústria'],
];
$industries = [];
$i = 1;
foreach ($industrias as $key => $nombre) {
    $industries[] = ['ikey' => $key, 'nombre' => $nombre, 'sort' => $i++, 'active' => 1];
}

$paises = require __DIR__ . '/../config/paises.php';
$countries = [];
$j = 1;
foreach (array_keys($paises['es']) as $iso) {
    $countries[] = ['iso' => $iso, 'nombre' => [
        'es' => $paises['es'][$iso] ?? $iso, 'en' => $paises['en'][$iso] ?? null, 'pt' => $paises['pt'][$iso] ?? null,
    ], 'dial' => null, 'sort' => $j++, 'active' => 1];
}

// ── Segmentos configurables (trilingües) ────────────────────────────────────
// category = categorías de recursos/blog · company_size = tamaño de empresa
// source = origen del lead · channel = canal de captación.
$segmentos = [
    'category' => [
        'ia_negocios' => ['es' => 'IA aplicada a negocios', 'en' => 'AI for business', 'pt' => 'IA aplicada a negócios'],
        'automatizacion' => ['es' => 'Automatización', 'en' => 'Automation', 'pt' => 'Automação'],
        'growth' => ['es' => 'Growth', 'en' => 'Growth', 'pt' => 'Growth'],
        'estrategia' => ['es' => 'Estrategia', 'en' => 'Strategy', 'pt' => 'Estratégia'],
        'marketing' => ['es' => 'Marketing estratégico', 'en' => 'Strategic marketing', 'pt' => 'Marketing estratégico'],
        'crm' => ['es' => 'CRM', 'en' => 'CRM', 'pt' => 'CRM'],
        'ventas' => ['es' => 'Ventas', 'en' => 'Sales', 'pt' => 'Vendas'],
        'experiencia_cliente' => ['es' => 'Experiencia de cliente', 'en' => 'Customer experience', 'pt' => 'Experiência do cliente'],
        'agentes' => ['es' => 'Agentes inteligentes', 'en' => 'Intelligent agents', 'pt' => 'Agentes inteligentes'],
        'datos' => ['es' => 'Datos y analítica', 'en' => 'Data & analytics', 'pt' => 'Dados e analítica'],
        'liderazgo' => ['es' => 'Liderazgo', 'en' => 'Leadership', 'pt' => 'Liderança'],
        'transformacion' => ['es' => 'Transformación digital', 'en' => 'Digital transformation', 'pt' => 'Transformação digital'],
    ],
    'company_size' => [
        'micro' => ['es' => '1–10 (Micro)', 'en' => '1–10 (Micro)', 'pt' => '1–10 (Micro)'],
        'pequena' => ['es' => '11–50 (Pequeña)', 'en' => '11–50 (Small)', 'pt' => '11–50 (Pequena)'],
        'mediana' => ['es' => '51–200 (Mediana)', 'en' => '51–200 (Medium)', 'pt' => '51–200 (Média)'],
        'grande' => ['es' => '201–1000 (Grande)', 'en' => '201–1000 (Large)', 'pt' => '201–1000 (Grande)'],
        'corporativa' => ['es' => '1000+ (Corporativa)', 'en' => '1000+ (Enterprise)', 'pt' => '1000+ (Corporativa)'],
    ],
    'source' => [
        'contacto' => ['es' => 'Formulario de contacto', 'en' => 'Contact form', 'pt' => 'Formulário de contato'],
        'diagnostico' => ['es' => 'Diagnóstico', 'en' => 'Assessment', 'pt' => 'Diagnóstico'],
        'recurso' => ['es' => 'Recurso / lead magnet', 'en' => 'Resource / lead magnet', 'pt' => 'Recurso / lead magnet'],
        'newsletter' => ['es' => 'Newsletter', 'en' => 'Newsletter', 'pt' => 'Newsletter'],
        'reserva' => ['es' => 'Reserva de agenda', 'en' => 'Booking', 'pt' => 'Agendamento'],
        'interes' => ['es' => 'Interés / campaña', 'en' => 'Interest / campaign', 'pt' => 'Interesse / campanha'],
        'chat' => ['es' => 'Chat AlexIA', 'en' => 'AlexIA chat', 'pt' => 'Chat AlexIA'],
        'referido' => ['es' => 'Referido', 'en' => 'Referral', 'pt' => 'Indicação'],
        'otro' => ['es' => 'Otro', 'en' => 'Other', 'pt' => 'Outro'],
    ],
    'channel' => [
        'web' => ['es' => 'Web', 'en' => 'Web', 'pt' => 'Web'],
        'telegram' => ['es' => 'Telegram', 'en' => 'Telegram', 'pt' => 'Telegram'],
        'whatsapp' => ['es' => 'WhatsApp', 'en' => 'WhatsApp', 'pt' => 'WhatsApp'],
    ],
];
$segments = [];
foreach ($segmentos as $kind => $items) {
    $s = 1;
    foreach ($items as $key => $nombre) {
        $segments[] = ['kind' => $kind, 'skey' => $key, 'nombre' => $nombre, 'sort' => $s++, 'active' => 1];
    }
}

return ['industries' => $industries, 'countries' => $countries, 'segments' => $segments];
