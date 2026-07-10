<?php
namespace Services\Connectors;

use Core\Database;
use Core\Env;

/** Acceso central a la configuración de conectores (BD + fallback .env). */
final class ConnectorRegistry
{
    private static array $cache = [];

    public static function get(string $provider): array
    {
        if (isset(self::$cache[$provider])) {
            return self::$cache[$provider];
        }
        $st = Database::run('SELECT enabled, config, status FROM connectors WHERE provider = ?', [$provider]);
        $row = $st->fetch() ?: ['enabled' => 0, 'config' => null, 'status' => 'sin_configurar'];
        $row['config'] = $row['config'] ? (json_decode($row['config'], true) ?: []) : [];
        return self::$cache[$provider] = $row;
    }

    /** Devuelve la config efectiva del conector: BD, con fallback a .env. */
    public static function config(string $provider): array
    {
        $row = self::get($provider);
        $cfg = $row['config'];
        $env = self::envFallback($provider);
        foreach ($env as $k => $v) {
            if (empty($cfg[$k]) && $v) { $cfg[$k] = $v; }
        }
        return $cfg;
    }

    public static function enabled(string $provider): bool
    {
        $cfg = self::config($provider);
        return (int) self::get($provider)['enabled'] === 1 && ! empty($cfg);
    }

    private static function envFallback(string $provider): array
    {
        return match ($provider) {
            'openai' => ['api_key' => Env::get('OPENAI_API_KEY'), 'model' => Env::get('OPENAI_MODEL', 'gpt-4o-mini'), 'image_model' => Env::get('OPENAI_IMAGE_MODEL', 'gpt-image-1'), 'audio_model' => Env::get('OPENAI_AUDIO_MODEL', 'gpt-4o-mini-tts'), 'voice' => Env::get('OPENAI_VOICE', 'shimmer')],
            'anthropic' => ['api_key' => Env::get('ANTHROPIC_API_KEY'), 'model' => Env::get('ANTHROPIC_MODEL', 'claude-sonnet-4-5')],
            'sendgrid' => ['api_key' => Env::get('SENDGRID_API_KEY'), 'from_email' => Env::get('MAIL_FROM'), 'from_name' => Env::get('MAIL_FROM_NAME')],
            'telegram' => ['commercial_token' => Env::get('TELEGRAM_BOT_COMMERCIAL_TOKEN'), 'internal_token' => Env::get('TELEGRAM_BOT_INTERNAL_TOKEN'), 'webhook_secret' => Env::get('TELEGRAM_WEBHOOK_SECRET')],
            'whatsapp' => ['token' => Env::get('WHATSAPP_TOKEN'), 'phone_id' => Env::get('WHATSAPP_PHONE_ID'), 'verify_token' => Env::get('WHATSAPP_VERIFY_TOKEN')],
            'wompi' => ['public_key' => Env::get('WOMPI_PUBLIC_KEY'), 'private_key' => Env::get('WOMPI_PRIVATE_KEY')],
            'epayco' => ['public_key' => Env::get('EPAYCO_PUBLIC_KEY'), 'private_key' => Env::get('EPAYCO_PRIVATE_KEY')],
            'stripe' => ['secret_key' => Env::get('STRIPE_SECRET_KEY'), 'webhook_secret' => Env::get('STRIPE_WEBHOOK_SECRET')],
            'paypal' => ['client_id' => Env::get('PAYPAL_CLIENT_ID'), 'secret' => Env::get('PAYPAL_SECRET')],
            'google_calendar' => ['client_id' => Env::get('GOOGLE_CLIENT_ID'), 'client_secret' => Env::get('GOOGLE_CLIENT_SECRET'), 'refresh_token' => Env::get('GOOGLE_REFRESH_TOKEN'), 'calendar_id' => Env::get('GOOGLE_CALENDAR_ID', 'primary')],
            default => [],
        };
    }
}
