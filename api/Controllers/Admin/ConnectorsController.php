<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Database;
use Core\Middleware\AuthMiddleware;
use Services\Connectors\OpenAIConnector;
use Services\Connectors\TelegramConnector;
use Services\Connectors\WhatsAppConnector;
use Services\Connectors\PaymentConnector;
use Services\Connectors\GoogleCalendarConnector;
use Services\Connectors\LinkedInConnector;
use Services\Connectors\GoogleBusinessConnector;

/** Gestión de conectores + plantillas de email desde el panel. */
final class ConnectorsController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $defs = biz('connectors');
        $groups = biz('connector_groups');
        $help = require __DIR__ . '/../../config/connector_help.php';
        $filas = Database::run('SELECT provider, enabled, config, status, last_check FROM connectors')->fetchAll();
        $byProv = [];
        foreach ($filas as $f) { $byProv[$f['provider']] = $f; }

        $out = [];
        $configurados = 0;
        $activos = 0;
        foreach ($defs as $prov => $def) {
            $row = $byProv[$prov] ?? ['enabled' => 0, 'config' => null, 'status' => 'sin_configurar', 'last_check' => null];
            $cfg = $row['config'] ? json_decode($row['config'], true) : [];

            $h = $help[$prov] ?? [];
            // Secretos nunca se devuelven; text/select sí. 'saved' indica qué guarda credencial.
            $config = [];
            $saved = [];
            $campos = [];
            foreach ($def['campos'] as $c) {
                $n = $c['n'];
                $has = isset($cfg[$n]) && $cfg[$n] !== '';
                $saved[$n] = $has;
                if (! empty($c['transient'])) { $config[$n] = ''; }
                else { $config[$n] = (($c['t'] ?? 'text') === 'secret') ? '' : ($cfg[$n] ?? ''); }
                // Ayuda por campo (qué es y dónde se obtiene) para el botón "?".
                $c['ayuda'] = $h['campos'][$n] ?? '';
                $campos[] = $c;
            }
            $isConfigured = ! empty(array_filter($saved));
            if ($isConfigured) { $configurados++; }
            if ((int) $row['enabled'] === 1) { $activos++; }

            $out[] = [
                'provider' => $prov, 'nombre' => $def['nombre'], 'grupo' => $def['grupo'],
                'grupo_label' => $groups[$def['grupo']] ?? $def['grupo'], 'desc' => $def['desc'] ?? '',
                'campos' => $campos, 'acciones' => $def['acciones'] ?? [],
                'ayuda' => ['que' => $h['que'] ?? ($def['desc'] ?? ''), 'guia' => $h['guia'] ?? []],
                'config' => $config, 'saved' => $saved, 'enabled' => (int) $row['enabled'],
                'status' => $isConfigured ? ($row['status'] === 'sin_configurar' ? 'configurado' : $row['status']) : 'sin_configurar',
                'last_check' => $row['last_check'],
            ];
        }
        $gout = [];
        foreach ($groups as $k => $l) { $gout[] = ['key' => $k, 'label' => $l]; }

        Response::ok([
            'items' => $out, 'groups' => $gout,
            'summary' => ['disponibles' => count($defs), 'configurados' => $configurados, 'activos' => $activos],
        ]);
    }

    public function update(string $provider): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $defs = biz('connectors');
        if (! isset($defs[$provider])) { Response::error('Conector desconocido.', 404); }
        $def = $defs[$provider];

        $existing = Database::run('SELECT config FROM connectors WHERE provider = ?', [$provider])->fetch();
        $cfg = $existing && $existing['config'] ? json_decode($existing['config'], true) : [];

        foreach ($def['campos'] as $c) {
            if (! empty($c['transient'])) { continue; }
            $n = $c['n'];
            $val = $this->req->input($n, null);
            if ($val === null) { continue; }
            $val = trim((string) $val);
            // Secretos: solo se sobrescriben si el usuario escribe uno nuevo.
            if (($c['t'] ?? 'text') === 'secret') {
                if ($val !== '') { $cfg[$n] = $val; }
            } else {
                $cfg[$n] = $val;
            }
        }
        $enabled = $this->req->input('enabled') ? 1 : 0;

        $this->upsert($provider, $enabled, $cfg);

        // Pasarelas de pago: solo una activa a la vez.
        if ($enabled && ($def['grupo'] ?? '') === 'pagos') {
            foreach ($defs as $p => $d) {
                if ($p !== $provider && ($d['grupo'] ?? '') === 'pagos') {
                    Database::run('UPDATE connectors SET enabled = 0 WHERE provider = ?', [$p]);
                }
            }
        }
        Response::ok(['message' => 'ok']);
    }

    private function upsert(string $provider, int $enabled, array $cfg): void
    {
        Database::run(
            Database::isSqlite()
                ? 'INSERT INTO connectors (provider, enabled, config, status, updated_at) VALUES (?,?,?,?,?) ON CONFLICT(provider) DO UPDATE SET enabled=excluded.enabled, config=excluded.config, updated_at=excluded.updated_at'
                : 'INSERT INTO connectors (provider, enabled, config, status, updated_at) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled), config=VALUES(config), updated_at=VALUES(updated_at)',
            [$provider, $enabled, json_encode($cfg, JSON_UNESCAPED_UNICODE), 'configurado', now_utc()]
        );
    }

    /** Compat: /admin/connectors/{provider}/test → acción 'test'. */
    public function test(string $provider): void
    {
        $this->accion($provider, 'test');
    }

    /** Ejecuta una acción del conector (probar, enviar prueba, webhooks, etc.). */
    public function accion(string $provider, string $accion): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $reg = '\\Services\\Connectors\\ConnectorRegistry';
        $appUrl = \Core\Env::get('APP_URL', '');

        $res = match ($accion . ':' . $provider) {
            'test:openai' => OpenAIConnector::test(),
            'test:anthropic' => \Services\Connectors\AnthropicConnector::test(),
            'test:telegram' => TelegramConnector::test(),
            'test:whatsapp' => WhatsAppConnector::test(),
            'test:google_calendar' => GoogleCalendarConnector::test(),
            'test:linkedin' => LinkedInConnector::test(),
            'publish_test:linkedin' => LinkedInConnector::publish('Publicación de prueba desde el panel de ExperientIA · GrowthBoard. ' . gmdate('Y-m-d H:i') . ' UTC'),
            'test:google_business' => GoogleBusinessConnector::test(),
            'sync_reviews:google_business' => GoogleBusinessConnector::syncReviews(),
            'test:wompi', 'test:epayco', 'test:stripe', 'test:paypal' => PaymentConnector::test($provider),
            'test:sendgrid' => ['ok' => ! empty($reg::config('sendgrid')['api_key']), 'message' => 'API key presente.', 'error' => 'Falta la API key de SendGrid.'],
            'send_test:sendgrid' => $this->enviarCorreoPrueba(),
            'register_webhooks:telegram' => TelegramConnector::registerWebhooks($appUrl),
            'webhook_url:whatsapp' => ['ok' => true, 'message' => 'Configura en Meta: ' . $appUrl . '/api/webhook/whatsapp · Verify token: ' . ($reg::config('whatsapp')['verify_token'] ?: '(define uno)')],
            'create_test_event:google_calendar' => $this->crearEventoPrueba(),
            default => ['ok' => false, 'error' => 'Acción no disponible para este conector.'],
        };

        if ($accion === 'test') {
            $estado = ($res['ok'] ?? false) ? 'ok' : 'error';
            Database::run('UPDATE connectors SET status = ?, last_check = ? WHERE provider = ?', [$estado, now_utc(), $provider]);
        }
        Response::ok($res);
    }

    private function enviarCorreoPrueba(): array
    {
        $to = trim((string) $this->req->input('test_to', '')) ?: \Core\Env::get('MAIL_NOTIFY', 'hello@experientia.pro');
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) { return ['ok' => false, 'error' => 'Correo de prueba no válido.']; }
        $ok = \Services\Mailer::send($to, 'Prueba de ExperientIA', '<h2>SendGrid operativo</h2><p>Este es un correo de prueba desde el panel de ExperientIA.</p>');
        return $ok ? ['ok' => true, 'message' => 'Correo de prueba enviado a ' . $to] : ['ok' => false, 'error' => 'No se pudo enviar. Revise la API key de SendGrid.'];
    }

    private function crearEventoPrueba(): array
    {
        $inicio = gmdate('Y-m-d\TH:i:s\Z', time() + 86400);
        $fin = gmdate('Y-m-d\TH:i:s\Z', time() + 86400 + 1800);
        $id = GoogleCalendarConnector::createEvent('ExperientIA · evento de prueba', $inicio, $fin, 'Creado desde el panel de conectores.');
        return $id ? ['ok' => true, 'message' => 'Evento de prueba creado (' . $id . ').'] : ['ok' => false, 'error' => 'No se pudo crear. Revise credenciales de Google.'];
    }

    public function templates(): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $rows = array_map(function ($r) {
            $r['subject'] = json_decode($r['subject'], true);
            $r['body'] = json_decode($r['body'], true);
            return $r;
        }, Database::run('SELECT * FROM email_templates ORDER BY tkey')->fetchAll());
        Response::ok($rows);
    }

    public function saveTemplate(string $tkey): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $subject = $this->req->input('subject', []);
        $body = $this->req->input('body', []);
        Database::run('UPDATE email_templates SET subject = ?, body = ?, updated_at = ? WHERE tkey = ?',
            [json_encode($subject, JSON_UNESCAPED_UNICODE), json_encode($body, JSON_UNESCAPED_UNICODE), now_utc(), $tkey]);
        Response::ok(['message' => 'ok']);
    }
}
