<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Database;
use Core\Middleware\AuthMiddleware;

/** CRUD genérico de contenido (soluciones, productos, casos, faqs, recursos, disponibilidad). */
final class ContentController extends Controller
{
    private const TABLAS = [
        'solutions' => ['skey', 'icon', 'titulo', 'pilar', 'problema', 'como', 'cambia', 'sort', 'active'],
        'products' => ['icon', 'nombre', 'rol', 'texto', 'destacado', 'sort', 'active'],
        'case_studies' => ['sector', 'titulo', 'contexto', 'intervencion', 'resultados', 'sort', 'active'],
        'faqs' => ['pregunta', 'respuesta', 'sort', 'active'],
        'resources' => ['slug', 'type', 'categories', 'tipo_label', 'titulo', 'extracto', 'cuerpo', 'author', 'read_minutes', 'cover_image', 'audio_path', 'video_url', 'file_path', 'gated', 'featured', 'seo_title', 'seo_desc', 'status', 'sort', 'active', 'published_at'],
        'availability_rules' => ['weekday', 'start_time', 'end_time', 'active'],
    ];
    private const JSON_FIELDS = ['titulo', 'pilar', 'problema', 'como', 'cambia', 'nombre', 'rol', 'texto', 'sector', 'contexto', 'intervencion', 'resultados', 'pregunta', 'respuesta', 'tipo_label', 'extracto', 'cuerpo', 'categories', 'seo_title', 'seo_desc'];

    public function index(string $tabla): void
    {
        AuthMiddleware::require($this->req);
        $this->assert($tabla);
        $orden = $tabla === 'availability_rules' ? 'weekday, start_time' : 'sort';
        Response::ok(array_map([$this, 'decode'], Database::run("SELECT * FROM {$tabla} ORDER BY {$orden}")->fetchAll()));
    }

    public function store(string $tabla): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $this->assert($tabla);
        $datos = $this->datos($tabla);
        if (! $datos) { Response::error('Sin datos.'); }
        $cols = implode(',', array_keys($datos));
        $ph = implode(',', array_fill(0, count($datos), '?'));
        Database::run("INSERT INTO {$tabla} ({$cols}) VALUES ({$ph})", array_values($datos));
        Response::ok(['id' => (int) Database::pdo()->lastInsertId()]);
    }

    public function update(string $tabla, string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $this->assert($tabla);
        $datos = $this->datos($tabla);
        if (! $datos) { Response::error('Sin datos.'); }
        $sets = implode(',', array_map(fn ($c) => "{$c}=?", array_keys($datos)));
        Database::run("UPDATE {$tabla} SET {$sets} WHERE id = ?", [...array_values($datos), $id]);
        Response::ok(['message' => 'ok']);
    }

    public function destroy(string $tabla, string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $this->assert($tabla);
        Database::run("DELETE FROM {$tabla} WHERE id = ?", [$id]);
        Response::ok(['message' => 'ok']);
    }

    public function upload(): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $f = $_FILES['archivo'] ?? null;
        if (! $f || $f['error'] !== UPLOAD_ERR_OK) { Response::error('No se recibió el archivo.'); }
        if ($f['size'] > 25 * 1024 * 1024) { Response::error('El archivo supera 25 MB.'); }
        if (mime_content_type($f['tmp_name']) !== 'application/pdf') { Response::error('Solo se permiten PDF.'); }
        $nombre = gmdate('Ymd-His') . '-' . preg_replace('/[^a-z0-9\-_]+/', '-', mb_strtolower(pathinfo($f['name'], PATHINFO_FILENAME))) . '.pdf';
        if (! move_uploaded_file($f['tmp_name'], BASE_PATH . '/storage/recursos/' . $nombre)) {
            Response::error('No se pudo guardar el archivo.', 500);
        }
        Response::ok(['file_path' => $nombre]);
    }

    private static array $colsCache = [];

    /** Columnas reales de la tabla (portable). Evita fallar por columnas sin migrar. */
    private function tableColumns(string $tabla): array
    {
        if (isset(self::$colsCache[$tabla])) { return self::$colsCache[$tabla]; }
        try {
            if (Database::isSqlite()) {
                $cols = array_column(Database::pdo()->query("PRAGMA table_info({$tabla})")->fetchAll(), 'name');
            } else {
                $cols = array_column(Database::pdo()->query("SHOW COLUMNS FROM {$tabla}")->fetchAll(), 'Field');
            }
        } catch (\Throwable $e) { $cols = []; }
        return self::$colsCache[$tabla] = $cols;
    }

    private function datos(string $tabla): array
    {
        $existentes = $this->tableColumns($tabla);
        $out = [];
        foreach (self::TABLAS[$tabla] as $c) {
            if (! array_key_exists($c, $this->req->body)) { continue; }
            if ($existentes && ! in_array($c, $existentes, true)) { continue; } // columna aún no migrada
            $v = $this->req->body[$c];
            $out[$c] = in_array($c, self::JSON_FIELDS, true) && is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v;
        }
        return $out;
    }

    private function assert(string $tabla): void
    {
        if (! isset(self::TABLAS[$tabla])) { Response::error('Recurso no encontrado.', 404); }
    }

    private function decode(array $row): array
    {
        foreach ($row as $k => $v) {
            if (is_string($v) && $v !== '' && ($v[0] === '{' || $v[0] === '[')) {
                $d = json_decode($v, true);
                if ($d !== null) { $row[$k] = $d; }
            }
        }
        return $row;
    }
}
