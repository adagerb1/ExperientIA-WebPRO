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

return ['industries' => $industries, 'countries' => $countries];
