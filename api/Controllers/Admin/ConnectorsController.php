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

/** Gestión de conectores + plantillas de email desde el panel. */
final class ConnectorsController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $defs = biz('connectors');
        $filas = Database::run('SELECT provider, enabled, config, status, last_check FROM connectors')->fetchAll();
        $byProv = [];
        foreach ($filas as $f) { $byProv[$f['provider']] = $f; }

        $out = [];
        foreach ($defs as $prov => $def) {
            $row = $byProv[$prov] ?? ['enabled' => 0, 'config' => null, 'status' => 'sin_configurar', 'last_check' => null];
            $cfg = $row['config'] ? json_decode($row['config'], true) : [];
            // Enmascarar secretos
            $masked = [];
            foreach ($def['campos'] as $campo) {
                $val = $cfg[$campo] ?? '';
                $masked[$campo] = $val ? (str_contains($campo, 'model') || str_contains($campo, 'email') || str_contains($campo, 'calendar') || str_contains($campo, 'name') ? $val : '••••••' . substr($val, -4)) : '';
            }
            $out[] = [
                'provider' => $prov, 'nombre' => $def['nombre'], 'grupo' => $def['grupo'],
                'campos' => $def['campos'], 'enabled' => (int) $row['enabled'],
                'status' => $row['status'], 'last_check' => $row['last_check'], 'config' => $masked,
            ];
        }
        Response::ok($out);
    }

    public function update(string $provider): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $defs = biz('connectors');
        if (! isset($defs[$provider])) { Response::error('Conector desconocido.', 404); }

        $existing = Database::run('SELECT config FROM connectors WHERE provider = ?', [$provider])->fetch();
        $cfg = $existing && $existing['config'] ? json_decode($existing['config'], true) : [];

        foreach ($defs[$provider]['campos'] as $campo) {
            $val = $this->req->input($campo, null);
            // Ignorar valores enmascarados (no sobrescribir con ••••)
            if ($val !== null && $val !== '' && ! str_starts_with((string) $val, '••••')) {
                $cfg[$campo] = trim((string) $val);
            }
        }
        $enabled = $this->req->input('enabled') ? 1 : 0;

        Database::run(
            Database::isSqlite()
                ? 'INSERT INTO connectors (provider, enabled, config, status, updated_at) VALUES (?,?,?,?,?) ON CONFLICT(provider) DO UPDATE SET enabled=excluded.enabled, config=excluded.config, updated_at=excluded.updated_at'
                : 'INSERT INTO connectors (provider, enabled, config, status, updated_at) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled), config=VALUES(config), updated_at=VALUES(updated_at)',
            [$provider, $enabled, json_encode($cfg, JSON_UNESCAPED_UNICODE), 'configurado', now_utc()]
        );
        Response::ok(['message' => 'ok']);
    }

    public function test(string $provider): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $res = match ($provider) {
            'openai' => OpenAIConnector::test(),
            'telegram' => TelegramConnector::test(),
            'whatsapp' => WhatsAppConnector::test(),
            'google_calendar' => GoogleCalendarConnector::test(),
            'wompi', 'epayco', 'stripe', 'paypal' => PaymentConnector::test($provider),
            'sendgrid' => ['ok' => ! empty(\Services\Connectors\ConnectorRegistry::config('sendgrid')['api_key']), 'message' => 'API key presente.', 'error' => 'Falta la API key de SendGrid.'],
            default => ['ok' => false, 'error' => 'Conector sin prueba disponible.'],
        };
        $estado = ($res['ok'] ?? false) ? 'ok' : 'error';
        Database::run('UPDATE connectors SET status = ?, last_check = ? WHERE provider = ?', [$estado, now_utc(), $provider]);
        Response::ok($res);
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
