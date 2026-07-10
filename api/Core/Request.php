<?php
namespace Core;

/** Encapsula la petición HTTP entrante. */
final class Request
{
    public string $method;
    public string $path;
    public array $query;
    public array $body;
    public array $headers;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        // Normaliza: quita prefijo /api
        $this->path = '/' . trim(preg_replace('#^/api#', '', rawurldecode($uri)), '/');
        $this->query = $_GET;
        $this->headers = self::readHeaders();

        $raw = file_get_contents('php://input') ?: '';
        $ctype = $this->headers['content-type'] ?? '';
        if (str_contains($ctype, 'application/json')) {
            $this->body = json_decode($raw, true) ?: [];
        } else {
            $this->body = $_POST ?: [];
        }
    }

    private static function readHeaders(): array
    {
        $h = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($k, 5)));
                $h[$name] = $v;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $h['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        return $h;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->headers['authorization'] ?? '';
        if (preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function page(): int
    {
        return max(1, (int) ($this->query['page'] ?? 1));
    }

    public function perPage(int $default = 20, int $max = 100): int
    {
        $pp = (int) ($this->query['per_page'] ?? $default);
        return max(1, min($pp, $max));
    }
}
