<?php
namespace Core;

/** Tokens de sesión tipo JWT (HS256) firmados con APP_SECRET. Sin dependencias. */
final class Token
{
    public static function issue(array $payload, int $ttlSeg = 43200): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload['iat'] = time();
        $payload['exp'] = time() + $ttlSeg;
        $h = self::b64(json_encode($header));
        $p = self::b64(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $sig = self::b64(hash_hmac('sha256', "{$h}.{$p}", self::secret(), true));
        return "{$h}.{$p}.{$sig}";
    }

    public static function verify(?string $token): ?array
    {
        if (! $token || substr_count($token, '.') !== 2) {
            return null;
        }
        [$h, $p, $sig] = explode('.', $token);
        $expected = self::b64(hash_hmac('sha256', "{$h}.{$p}", self::secret(), true));
        if (! hash_equals($expected, $sig)) {
            return null;
        }
        $payload = json_decode(self::unb64($p), true);
        if (! is_array($payload) || ($payload['exp'] ?? 0) < time()) {
            return null;
        }
        return $payload;
    }

    private static function secret(): string
    {
        $s = Env::get('APP_SECRET', '');
        // En producción jamás se opera con el secreto por defecto: un atacante
        // podría forjar tokens de administrador. Instalar .env es obligatorio.
        if (($s === '' || $s === 'inseguro-cambiar') && Env::get('APP_ENV') !== 'dev') {
            throw new \RuntimeException('Configura APP_SECRET en el archivo .env (instalación incompleta).', 500);
        }
        return $s !== '' ? $s : 'inseguro-cambiar';
    }

    private static function b64(string $d): string
    {
        return rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
    }

    private static function unb64(string $d): string
    {
        return base64_decode(strtr($d, '-_', '+/'));
    }
}
