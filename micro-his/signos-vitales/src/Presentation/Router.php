<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Presentation;

/** Rutas del módulo (sin el prefijo /api/v1, que pone el ApiKernel): cada una apunta a una acción del controlador; lo demás responde 404. */
final class Router
{
    public function __construct(private readonly VitalSignController $vitalSigns, private readonly JsonView $view)
    {
    }

    public function dispatch(Request $request): array
    {
        return match (true) {
            $request->method === 'POST' && preg_match('#^/care-episodes/([\w-]+)/vital-signs$#', $request->path, $m) === 1 => $this->vitalSigns->record($request, $m[1]),
            $request->method === 'GET' && preg_match('#^/vital-signs/([\w-]+)$#', $request->path, $m) === 1 => $this->vitalSigns->show($request, $m[1]),
            default => $this->view->render(404, ['code' => 'ROUTE_NOT_FOUND']),
        };
    }
}
