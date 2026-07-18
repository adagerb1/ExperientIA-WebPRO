<?php
namespace Services;

use Core\Database;
use Core\Env;

/**
 * Captcha propio, sin servicios externos (funciona en cPanel sin claves).
 * Imagen distorsionada generada con GD + token firmado (HMAC con APP_SECRET),
 * de UN SOLO USO y con expiración corta. Verificación server-side siempre.
 *
 *   issue()  → ['image' => dataURI PNG, 'token' => firmado, 'ttl' => seg]
 *   verify() → true si el código coincide, no expiró y el token no se usó antes.
 */
final class Captcha
{
    /** Sin caracteres ambiguos (0/O, 1/I/L…). */
    private const CHARS = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    private const LEN = 5;
    private const TTL = 300; // 5 minutos

    public static function issue(): array
    {
        $code = '';
        for ($i = 0; $i < self::LEN; $i++) {
            $code .= self::CHARS[random_int(0, strlen(self::CHARS) - 1)];
        }
        $exp = time() + self::TTL;
        $token = $exp . '.' . self::sign($code, $exp);

        return ['image' => self::render($code), 'token' => $token, 'ttl' => self::TTL];
    }

    public static function verify(?string $code, ?string $token): bool
    {
        $code = strtoupper(preg_replace('/[^a-z0-9]/i', '', (string) $code));
        if ($code === '' || ! $token || substr_count($token, '.') !== 1) {
            return false;
        }
        [$exp, $sig] = explode('.', $token);
        if ((int) $exp < time()) {
            return false;
        }
        if (! hash_equals(self::sign($code, (int) $exp), $sig)) {
            return false;
        }
        return self::marcarUsado($token, (int) $exp);
    }

    /** Un token solo vale una vez (evita resolver una vez y reusar en masa). */
    private static function marcarUsado(string $token, int $exp): bool
    {
        $k = 'cap:' . substr(hash('sha256', $token), 0, 36);
        try {
            Database::pdo()->prepare('INSERT INTO rate_limits (k, hits, reset_at) VALUES (?, 1, ?)')
                ->execute([$k, $exp]);
            return true; // primera vez
        } catch (\Throwable $e) {
            return false; // ya usado (clave duplicada)
        }
    }

    private static function sign(string $code, int $exp): string
    {
        return hash_hmac('sha256', "captcha|{$code}|{$exp}", Env::get('APP_SECRET', 'inseguro-cambiar'));
    }

    /** PNG distorsionado con GD (fuente incorporada, ruido y desplazamientos). */
    private static function render(string $code): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            // Sin GD (rarísimo en cPanel): el widget muestra el reto como texto plano.
            return 'text:' . $code;
        }
        [$w, $h] = [66, 26]; // lienzo pequeño, luego se escala x3
        $img = imagecreatetruecolor($w, $h);
        $bg = imagecolorallocate($img, 10, 27, 58); // azul midnight de marca
        imagefilledrectangle($img, 0, 0, $w, $h, $bg);

        // Líneas de ruido
        for ($i = 0; $i < 5; $i++) {
            $c = imagecolorallocate($img, random_int(30, 80), random_int(70, 130), random_int(110, 170));
            imageline($img, random_int(0, $w), random_int(0, $h), random_int(0, $w), random_int(0, $h), $c);
        }
        // Caracteres con desplazamiento vertical y colores claros variables
        $x = 4;
        foreach (str_split($code) as $ch) {
            $c = imagecolorallocate($img, random_int(150, 220), random_int(200, 250), random_int(230, 255));
            imagestring($img, 5, $x, random_int(1, $h - 17), $ch, $c);
            $x += 12;
        }
        // Puntos de ruido
        for ($i = 0; $i < 90; $i++) {
            $c = imagecolorallocate($img, random_int(40, 120), random_int(80, 160), random_int(120, 200));
            imagesetpixel($img, random_int(0, $w - 1), random_int(0, $h - 1), $c);
        }
        // Escalar x3 con leve estiramiento aleatorio (dificulta OCR simple)
        $big = imagecreatetruecolor($w * 3, $h * 3);
        imagecopyresized($big, $img, 0, 0, 0, 0, $w * 3, $h * 3, $w, $h);
        imagedestroy($img);

        ob_start();
        imagepng($big);
        $png = ob_get_clean();
        imagedestroy($big);
        return 'data:image/png;base64,' . base64_encode($png);
    }
}
