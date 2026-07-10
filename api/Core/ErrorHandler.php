<?php
namespace Core;

/** Manejador central de errores: nada de 500 silenciosos (bulletproof). */
final class ErrorHandler
{
    public static function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');

        set_error_handler(function ($severity, $message, $file, $line) {
            if (! (error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(function (\Throwable $e) {
            self::respond($e);
        });

        register_shutdown_function(function () {
            $err = error_get_last();
            if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::respond(new \ErrorException($err['message'], 0, $err['type'], $err['file'], $err['line']));
            }
        });
    }

    public static function respond(\Throwable $e): void
    {
        $requestId = substr(bin2hex(random_bytes(6)), 0, 12);
        self::log($e, $requestId);

        $dev = Env::get('APP_ENV') === 'dev';
        $status = ($e instanceof \RuntimeException && $e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;

        if (headers_sent()) {
            return;
        }
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'error' => $dev ? $e->getMessage() : 'No fue posible completar la operación. Intente de nuevo.',
            'request_id' => $requestId,
            'detail' => $dev ? [
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ] : null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private static function log(\Throwable $e, string $requestId): void
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $line = sprintf(
            "[%s] %s %s: %s @ %s:%d\n",
            gmdate('Y-m-d H:i:s'),
            $requestId,
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
        @file_put_contents($dir . '/error-' . gmdate('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
