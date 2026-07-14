<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Database;
use Core\Middleware\AuthMiddleware;

/**
 * Gestión trilingüe de segmentos configurables (categorías de recursos, tamaños
 * de empresa, orígenes y canales de lead). Cada taxonomía se identifica por
 * `kind`; nueva taxonomía = nuevo kind, sin cambios de esquema. Espejo de la
 * gestión de industrias/países pero sobre una tabla única.
 */
final class SegmentTaxonomyController extends Controller
{
    /** Tipos permitidos (evita crear kinds arbitrarios desde el cliente). */
    private const KINDS = ['category', 'company_size', 'source', 'channel'];

    public function index(string $kind): void
    {
        AuthMiddleware::require($this->req);
        $this->assert($kind);
        $rows = Database::run('SELECT * FROM segments WHERE kind = ? ORDER BY sort, skey', [$kind])->fetchAll();
        Response::ok(array_map([$this, 'decode'], $rows));
    }

    public function store(string $kind): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $this->assert($kind);
        $d = $this->datos();
        if ($d['skey'] === '') { Response::error('La clave interna es obligatoria.', 422); }
        Database::run('INSERT INTO segments (kind, skey, nombre, sort, active) VALUES (?,?,?,?,?)',
            [$kind, $d['skey'], $d['nombre'], $d['sort'], $d['active']]);
        Response::ok(['id' => (int) Database::pdo()->lastInsertId()]);
    }

    public function update(string $kind, string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $this->assert($kind);
        $d = $this->datos();
        Database::run('UPDATE segments SET skey = ?, nombre = ?, sort = ?, active = ? WHERE id = ? AND kind = ?',
            [$d['skey'], $d['nombre'], $d['sort'], $d['active'], $id, $kind]);
        Response::ok(['message' => 'ok']);
    }

    public function destroy(string $kind, string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $this->assert($kind);
        Database::run('DELETE FROM segments WHERE id = ? AND kind = ?', [$id, $kind]);
        Response::ok(['message' => 'ok']);
    }

    private function datos(): array
    {
        $b = $this->req->body;
        $nombre = $b['nombre'] ?? [];
        return [
            'skey' => preg_replace('/[^a-z0-9_]+/', '', mb_strtolower(trim((string) ($b['skey'] ?? '')))),
            'nombre' => is_array($nombre) ? json_encode($nombre, JSON_UNESCAPED_UNICODE) : (string) $nombre,
            'sort' => (int) ($b['sort'] ?? 0),
            'active' => (int) ! empty($b['active']),
        ];
    }

    private function assert(string $kind): void
    {
        if (! in_array($kind, self::KINDS, true)) { Response::error('Segmento no encontrado.', 404); }
    }

    private function decode(array $row): array
    {
        if (isset($row['nombre']) && is_string($row['nombre'])) {
            $d = json_decode($row['nombre'], true);
            if (is_array($d)) { $row['nombre'] = $d; }
        }
        return $row;
    }
}
