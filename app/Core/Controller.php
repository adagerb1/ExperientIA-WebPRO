<?php
namespace Core;

/**
 * Controlador base "bulletproof": envuelve cada acción en try/catch(\Throwable).
 * Si algo falla, responde 500 con JSON describiendo el error (capturado por el
 * frontend como alerta), nunca un 500 silencioso.
 */
abstract class Controller
{
    protected Request $req;

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    public function dispatch(string $accion, array $args = []): void
    {
        try {
            if (! method_exists($this, $accion)) {
                Response::error('Recurso no encontrado.', 404);
            }
            $this->{$accion}(...$args);
        } catch (\Throwable $e) {
            ErrorHandler::respond($e);
        }
    }
}
