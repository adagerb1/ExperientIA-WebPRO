<?php
namespace Core;

/** Router REST simplificado (MVC). Mapea método+patrón a Controlador@acción. */
final class Router
{
    private array $rutas = [];

    public function add(string $metodo, string $patron, string $controlador, string $accion): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $patron) . '$#';
        $this->rutas[] = compact('metodo', 'regex', 'controlador', 'accion');
    }

    public function get(string $p, string $c, string $a): void { $this->add('GET', $p, $c, $a); }
    public function post(string $p, string $c, string $a): void { $this->add('POST', $p, $c, $a); }
    public function put(string $p, string $c, string $a): void { $this->add('PUT', $p, $c, $a); }
    public function patch(string $p, string $c, string $a): void { $this->add('PATCH', $p, $c, $a); }
    public function delete(string $p, string $c, string $a): void { $this->add('DELETE', $p, $c, $a); }

    public function resolve(Request $req): void
    {
        foreach ($this->rutas as $r) {
            if ($r['metodo'] !== $req->method) {
                continue;
            }
            if (preg_match($r['regex'], $req->path, $m)) {
                $args = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                $clase = 'Controllers\\' . $r['controlador'];
                $ctrl = new $clase($req);
                $ctrl->dispatch($r['accion'], array_values($args));
                return;
            }
        }
        Response::error('Endpoint no encontrado.', 404);
    }
}
