<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\RateLimiter;
use Core\Middleware\AuthMiddleware;
use Services\AlexIA;

/**
 * Asistente de redacción del CMS: AlexIA completa los campos de cualquier tipo de
 * contenido (trilingüe) a partir de una instrucción, alineado a la estrategia y el
 * ecosistema de ExperientIA. Genérico: recibe el esquema de campos del editor.
 */
final class CmsAssistController extends Controller
{
    /** Propósito estratégico por tabla (contexto para AlexIA). */
    private const PROPOSITO = [
        'solutions' => 'una SOLUCIÓN/servicio de ExperientIA: resuelve un dolor del C-Level con automatización, datos o IA. Enfoque de venta: problema real, cómo lo resolvemos y qué cambia en el negocio.',
        'products' => 'un PRODUCTO de ExperientIA: activo o programa concreto que el cliente adquiere. Enfoque de venta y beneficio.',
        'case_studies' => 'un CASO DE ÉXITO: sector, contexto del cliente, intervención de ExperientIA y resultados. Realista y creíble; los resultados como cifras plausibles pero NO inventes clientes reales.',
        'faqs' => 'una PREGUNTA FRECUENTE y su respuesta, clara y orientada a resolver objeciones y avanzar a la conversión.',
        'industries' => 'una INDUSTRIA: solo el nombre del sector, natural en cada idioma.',
        'campaign_templates' => 'una PLANTILLA de campaña (asunto y cuerpo) para email o WhatsApp; usa {nombre} y {empresa} como variables; cálida y orientada a agendar.',
        'resources' => 'un RECURSO/artículo de valor para líderes empresariales.',
    ];

    /** Campos que NO debe tocar la IA (claves técnicas, orden, banderas). */
    private const BLOQUEADOS = ['skey', 'slug', 'ikey', 'dkey', 'iso', 'file_path', 'published_at', 'dial', 'sort', 'active', 'destacado', 'weekday', 'start_time', 'end_time'];

    public function redactar(): void
    {
        $admin = AuthMiddleware::require($this->req, 'admin');
        RateLimiter::user((int) $admin['id'], $this->req);

        $tabla = (string) $this->req->input('tabla', '');
        $instruccion = trim((string) $this->req->input('instruccion', ''));
        $campos = (array) $this->req->input('campos', []);
        if ($instruccion === '') { Response::error('Escribe qué quieres que redacte AlexIA.', 422); }

        // Construye el esquema de campos redactables + su descripción para el prompt.
        $fill = [];
        $desc = [];
        foreach ($campos as $c) {
            $n = (string) ($c['n'] ?? '');
            $t = (string) ($c['t'] ?? '');
            $l = (string) ($c['l'] ?? $n);
            if ($n === '' || in_array($n, self::BLOQUEADOS, true)) { continue; }
            if ($t === 'i18n' || $t === 'i18ta') {
                $fill[$n] = 'i18n';
                $desc[] = "\"{$n}\": objeto {\"es\":\"\",\"en\":\"\",\"pt\":\"\"} — {$l}" . ($t === 'i18ta' ? ' (texto de 2-4 frases)' : ' (texto breve)');
            } elseif ($t === 'sel') {
                $ops = implode('|', array_map('strval', (array) ($c['op'] ?? [])));
                $fill[$n] = 'sel';
                $desc[] = "\"{$n}\": elige UNO de [{$ops}] — {$l}";
            } elseif ($t === 'text') {
                $fill[$n] = 'text';
                $desc[] = "\"{$n}\": string — {$l}";
            }
        }
        if (! $fill) { Response::error('Este contenido no tiene campos redactables por IA.', 422); }

        $prop = self::PROPOSITO[$tabla] ?? 'un contenido del sitio de ExperientIA.';
        $instr = 'Eres estratega de contenidos de ExperientIA SAS: firma premium C-Level de automatización, growth e IA aplicada a negocios, '
            . 'orientada a venta y conversión. Vas a completar los campos de ' . $prop . "\n"
            . 'Devuelve ÚNICAMENTE JSON válido (sin markdown ni texto extra) con EXACTAMENTE estas claves:' . "\n"
            . implode("\n", $desc) . "\n"
            . 'Reglas: redacta genuinamente en español (es), inglés (en) y portugués (pt) —no traduzcas literal—. '
            . 'Tono premium, claro, orientado a beneficio y cierre. NO inventes cifras, precios ni clientes reales. '
            . 'Coherente con el ecosistema y los objetivos de ExperientIA.';

        try {
            $raw = AlexIA::ask($instr, 'Instrucción del usuario: ' . $instruccion);
            $data = json_decode($this->extractJson($raw), true);
            if (! is_array($data)) { Response::error('AlexIA no devolvió contenido válido. Intenta de nuevo.', 502); }
            $out = [];
            foreach ($fill as $n => $tipo) {
                if (! array_key_exists($n, $data)) { continue; }
                if ($tipo === 'i18n') {
                    $v = (array) $data[$n];
                    $out[$n] = ['es' => trim((string) ($v['es'] ?? '')), 'en' => trim((string) ($v['en'] ?? '')), 'pt' => trim((string) ($v['pt'] ?? ''))];
                } else {
                    $out[$n] = trim((string) $data[$n]);
                }
            }
            Response::ok(['campos' => $out]);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 503);
        }
    }

    /** Extrae el primer bloque JSON de una respuesta del modelo. */
    private function extractJson(string $raw): string
    {
        $raw = trim($raw);
        $raw = preg_replace('/^```(?:json)?|```$/m', '', $raw);
        $i = strpos($raw, '{');
        $j = strrpos($raw, '}');
        return ($i !== false && $j !== false && $j > $i) ? substr($raw, $i, $j - $i + 1) : $raw;
    }
}
