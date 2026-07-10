<?php
namespace Services\Connectors;

use Services\Http;

/** Google Calendar: crea eventos de las reservas 1:1 (OAuth refresh token). */
final class GoogleCalendarConnector
{
    private static function accessToken(array $cfg): ?string
    {
        if (empty($cfg['refresh_token'])) { return null; }
        $r = Http::form('POST', 'https://oauth2.googleapis.com/token', [
            'client_id' => $cfg['client_id'] ?? '', 'client_secret' => $cfg['client_secret'] ?? '',
            'refresh_token' => $cfg['refresh_token'], 'grant_type' => 'refresh_token',
        ]);
        return $r['body']['access_token'] ?? null;
    }

    /** Crea un evento; devuelve el id o null si no está configurado. */
    public static function createEvent(string $summary, string $startUtc, string $endUtc, ?string $description = null): ?string
    {
        $cfg = ConnectorRegistry::config('google_calendar');
        $token = self::accessToken($cfg);
        if (! $token) { return null; }
        $calId = $cfg['calendar_id'] ?? 'primary';
        $r = Http::json('POST', "https://www.googleapis.com/calendar/v3/calendars/{$calId}/events", [
            'summary' => $summary, 'description' => $description,
            'start' => ['dateTime' => gmdate('c', strtotime($startUtc))],
            'end' => ['dateTime' => gmdate('c', strtotime($endUtc))],
        ], ['Authorization: Bearer ' . $token]);
        return $r['body']['id'] ?? null;
    }

    public static function test(): array
    {
        $cfg = ConnectorRegistry::config('google_calendar');
        if (empty($cfg['refresh_token'])) {
            return ['ok' => false, 'error' => 'Configure el refresh token de Google (OAuth).'];
        }
        return self::accessToken($cfg)
            ? ['ok' => true, 'message' => 'Autenticación con Google válida.']
            : ['ok' => false, 'error' => 'No se pudo renovar el token de Google.'];
    }
}
