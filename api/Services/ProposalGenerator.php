<?php
namespace Services;

use Core\Database;

/**
 * Generador de propuestas comerciales con IA, calcado del modelo probado de
 * ExperientIA (resumen → contexto → objetivos → alcance → fases → entregables →
 * tiempo → inversión → forma de pago → consideraciones → próximos pasos).
 * AlexIA orquesta: estratega (ángulo/objetivos) + redactor (secciones).
 * NO envía nada: devuelve el borrador para revisar en el editor del panel.
 */
final class ProposalGenerator
{
    public static function generar(?array $lead, string $brief, string $locale = 'es'): array
    {
        $ficha = self::ficha($lead);
        $idioma = ['es' => 'español', 'en' => 'inglés', 'pt' => 'portugués'][$locale] ?? 'español';

        // Agente 1 · Estratega comercial: ángulo, objetivos y alcance.
        $estr = AlexIA::askJson(
            "Eres el estratega comercial de ExperientIA SAS (automatización, growth e IA aplicada a negocios; consultor "
            . "principal Tonny Dager, herramienta insignia GrowthBoard). A partir del cliente y el brief, define el ángulo "
            . "de una propuesta comercial B2B premium, honesta y sin humo. No inventes cifras ni precios.\n"
            . "Responde JSON: {\"angulo\":\"...\",\"objetivos\":[\"...\"],\"alcance\":[\"...\"],\"fases\":[{\"phase\":\"Fase 1\",\"title\":\"...\",\"text\":\"...\"}]} en {$idioma}. "
            . "3-4 objetivos, 4-6 puntos de alcance, 3-4 fases.",
            "CLIENTE:\n{$ficha}\n\nBRIEF DEL COMERCIAL:\n" . ($brief ?: '(sin brief)')
        );

        // Agente 2 · Redactor: el documento completo.
        $doc = AlexIA::askJson(
            "Eres redactor de propuestas comerciales de ExperientIA. Con el cliente, el brief y la estrategia, redacta en "
            . "{$idioma} las secciones del documento. Tono ejecutivo premium, claro y sin humo. NO inventes precios: si el "
            . "brief no trae monto, deja amount en 'Por definir'. Responde JSON EXACTO:\n"
            . "{\"eyebrow\":\"Propuesta confidencial\",\"title\":\"...\",\"subtitle\":\"...\",\"hero_note\":\"...\","
            . "\"resumen\":{\"title\":\"Resumen ejecutivo\",\"text\":\"...\"},"
            . "\"contexto\":{\"title\":\"Contexto\",\"text\":\"...\"},"
            . "\"objetivos\":{\"title\":\"Objetivos\",\"items\":[\"...\"]},"
            . "\"alcance\":{\"title\":\"Alcance\",\"items\":[\"...\"]},"
            . "\"fases\":[{\"phase\":\"Fase 1\",\"title\":\"...\",\"text\":\"...\"}],"
            . "\"entregables\":{\"title\":\"Entregables\",\"items\":[\"...\"]},"
            . "\"tiempo\":{\"title\":\"Tiempos\",\"text\":\"...\"},"
            . "\"inversion\":{\"title\":\"Inversión\",\"amount\":\"...\",\"note\":\"...\"},"
            . "\"payment_terms\":{\"title\":\"Forma de pago\",\"items\":[\"...\"]},"
            . "\"conditions\":{\"title\":\"Consideraciones importantes\",\"items\":[\"...\"]},"
            . "\"next_steps\":{\"title\":\"Próximos pasos\",\"items\":[\"...\"]},"
            . "\"cta\":{\"title\":\"...\",\"text\":\"...\"}}",
            "CLIENTE:\n{$ficha}\n\nBRIEF:\n" . ($brief ?: '(sin brief)') . "\n\nESTRATEGIA:\n" . json_encode($estr, JSON_UNESCAPED_UNICODE)
        );

        // Estructura garantizada (la IA puede omitir claves).
        $texto = fn ($k, $fb) => ['title' => (string) ($doc[$k]['title'] ?? $fb), 'text' => (string) ($doc[$k]['text'] ?? '')];
        $lista = fn ($k, $fb) => ['title' => (string) ($doc[$k]['title'] ?? $fb), 'items' => array_values(array_filter(array_map('strval', $doc[$k]['items'] ?? [])))];
        $fases = [];
        foreach (array_slice((array) ($doc['fases'] ?? $estr['fases'] ?? []), 0, 6) as $i => $f) {
            if (trim((string) ($f['title'] ?? '')) === '') { continue; }
            $fases[] = ['phase' => (string) ($f['phase'] ?? ('Fase ' . ($i + 1))), 'title' => (string) $f['title'], 'text' => (string) ($f['text'] ?? '')];
        }
        return [
            'eyebrow' => (string) ($doc['eyebrow'] ?? 'Propuesta confidencial'),
            'title' => (string) ($doc['title'] ?? ''),
            'subtitle' => (string) ($doc['subtitle'] ?? ''),
            'hero_note' => (string) ($doc['hero_note'] ?? ''),
            'resumen' => $texto('resumen', 'Resumen ejecutivo'),
            'contexto' => $texto('contexto', 'Contexto'),
            'objetivos' => $lista('objetivos', 'Objetivos'),
            'alcance' => $lista('alcance', 'Alcance'),
            'fases' => $fases,
            'entregables' => $lista('entregables', 'Entregables'),
            'tiempo' => $texto('tiempo', 'Tiempos'),
            'inversion' => ['title' => (string) ($doc['inversion']['title'] ?? 'Inversión'), 'amount' => (string) ($doc['inversion']['amount'] ?? 'Por definir'), 'note' => (string) ($doc['inversion']['note'] ?? '')],
            'payment_terms' => $lista('payment_terms', 'Forma de pago'),
            'conditions' => $lista('conditions', 'Consideraciones importantes'),
            'next_steps' => $lista('next_steps', 'Próximos pasos'),
            'cta' => $texto('cta', 'Demos el siguiente paso'),
        ];
    }

    private static function ficha(?array $lead): string
    {
        if (! $lead) { return '(cliente sin ficha en el CRM; usar solo el brief)'; }
        $out = 'Nombre: ' . ($lead['name'] ?? '') . "\n";
        if (! empty($lead['company'])) { $out .= 'Empresa: ' . $lead['company'] . "\n"; }
        foreach (['industry' => 'Industria', 'company_size' => 'Tamaño', 'country' => 'País'] as $k => $l) {
            if (! empty($lead[$k])) { $out .= $l . ': ' . $lead[$k] . "\n"; }
        }
        if (! empty($lead['notes'])) { $out .= 'Notas del CRM: ' . mb_substr((string) $lead['notes'], 0, 800) . "\n"; }
        // Historial reciente (diagnósticos, chats): contexto valioso para la propuesta.
        try {
            $tps = Database::run('SELECT type, title FROM touchpoints WHERE lead_id = ? ORDER BY id DESC LIMIT 8', [$lead['id']])->fetchAll();
            if ($tps) { $out .= "Interacciones recientes:\n"; foreach ($tps as $t) { $out .= '  - [' . $t['type'] . '] ' . $t['title'] . "\n"; } }
        } catch (\Throwable $e) { /* opcional */ }
        return $out;
    }
}
