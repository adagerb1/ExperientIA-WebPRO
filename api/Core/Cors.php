<?php
namespace Core;

/** CORS restringido: solo el sitio y el portal admin (orígenes de .env). */
final class Cors
{
    public static function handle(Request $req): void
    {
        $allowed = array_filter(array_map('trim', explode(',', Env::get('CORS_ALLOWED_ORIGINS', ''))));
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // En desarrollo permitimos localhost.
        if (Env::get('APP_ENV') === 'dev' && $origin && preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#', $origin)) {
            $allowed[] = $origin;
        }

        if ($origin && in_array($origin, $allowed, true)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF, X-Requested-With');
            header('Access-Control-Max-Age: 86400');
        } elseif ($origin) {
            // Origen no autorizado: se rechaza el preflight y la petición.
            if ($req->method === 'OPTIONS') {
                http_response_code(403);
                exit;
            }
        }

        if ($req->method === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
