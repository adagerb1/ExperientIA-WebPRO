<?php
namespace Core;

/** Respuestas estrictas en JSON (el backend nunca renderiza HTML). */
final class Response
{
    public static function json(mixed $data, int $status = 200, array $headers = []): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        foreach ($headers as $k => $v) {
            header("{$k}: {$v}");
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function ok(mixed $data = [], array $extra = []): never
    {
        self::json(['ok' => true] + (is_array($data) ? ['data' => $data] : ['data' => $data]) + $extra);
    }

    public static function error(string $message, int $status = 400, array $extra = []): never
    {
        self::json(['ok' => false, 'error' => $message] + $extra, $status);
    }

    /** Resultado paginado estándar. */
    public static function paginated(array $items, int $total, int $page, int $perPage): never
    {
        self::json([
            'ok' => true,
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'pages' => (int) ceil($total / max(1, $perPage)),
            ],
        ]);
    }
}
