<?php
namespace Services;

use Core\Database;
use Core\Env;
use Services\Connectors\TelegramConnector;

/**
 * Notificaciones internas del negocio por Telegram (bot interno de AlexIA).
 * Reciben todos los admins activos con notify_telegram=1 y telegram_user_id
 * vinculado. Siempre best-effort: una notificación jamás rompe una captura.
 */
final class Notifier
{
    /** Envía un mensaje (HTML de Telegram) a todos los suscritos. */
    public static function telegram(string $html): void
    {
        try {
            $rows = Database::run('SELECT telegram_user_id FROM admins WHERE active = 1 AND notify_telegram = 1 AND telegram_user_id IS NOT NULL AND telegram_user_id != \'\'')->fetchAll();
            foreach ($rows as $r) {
                TelegramConnector::sendMessage('internal', (string) $r['telegram_user_id'], $html);
            }
        } catch (\Throwable $e) { /* columna sin migrar o bot sin configurar */ }
    }

    /** Nueva interacción de lead: formato claro con lo esencial y enlace al CRM. */
    public static function interaccion(array $lead, string $tipo, string $titulo, array $payload = []): void
    {
        try {
            $e = fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
            $tipos = ['contacto' => '📬 Contacto', 'newsletter' => '📰 Newsletter', 'diagnostico' => '🎯 Diagnóstico',
                'descarga' => '📥 Descarga', 'reserva' => '📅 Reserva', 'chat' => '💬 Chat AlexIA',
                'growthboard' => '🏟️ Diagnóstico GrowthBoard', 'interes' => '⚡ Interés', 'telegram' => '✈️ Telegram', 'whatsapp' => '🟢 WhatsApp'];
            $lineas = ['<b>' . ($tipos[$tipo] ?? '🔔 ' . $e(ucfirst($tipo))) . '</b>'];
            $quien = $e($lead['name'] ?? 'Lead');
            if (! empty($lead['company'])) { $quien .= ' — ' . $e($lead['company']); }
            $lineas[] = '👤 ' . $quien;

            $contacto = [];
            if (! empty($lead['email'])) { $contacto[] = '✉️ ' . $e($lead['email']); }
            if (! empty($lead['phone_wa'])) { $contacto[] = '📱 +' . $e($lead['phone_wa']); }
            if ($contacto) { $lineas[] = implode('  ·  ', $contacto); }

            $lugar = array_filter([$lead['country'] ?? null, $lead['industry'] ?? null, $lead['company_size'] ?? null]);
            if ($lugar) { $lineas[] = '📍 ' . $e(implode(' · ', $lugar)); }

            // Resumen del GrowthBoard cuando aplica (lo más valioso de un vistazo).
            if (isset($payload['total'], $payload['banda'])) {
                $lineas[] = '📊 Tablero: <b>' . $e($payload['total']) . '/55</b> (' . $e($payload['banda']) . ')'
                    . (isset($payload['zona_critica']) ? ' · zona crítica: <b>' . $e($payload['zona_critica']) . '</b>' : '');
            } elseif ($titulo !== '') {
                $lineas[] = '🧭 ' . $e($titulo);
            }
            if (! empty($lead['utm_campaign'])) { $lineas[] = '🎯 Campaña: ' . $e($lead['utm_campaign']); }
            if (! empty($lead['id'])) {
                $lineas[] = '🔗 ' . rtrim(Env::get('APP_URL', 'https://experientia.pro'), '/') . '/admin/leads/' . (int) $lead['id'];
            }
            self::telegram(implode("\n", $lineas));
        } catch (\Throwable $e) { /* nunca romper la captura */ }
    }
}
