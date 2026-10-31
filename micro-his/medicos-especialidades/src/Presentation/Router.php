<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Presentation;

/** Rutas del módulo: cada una apunta a una acción del controlador; lo demás responde 404. */
final class Router
{
    public function __construct(private readonly DoctorController $doctors, private readonly JsonView $view)
    {
    }

    public function dispatch(Request $request): array
    {
        return match (true) {
            $request->method === 'POST' && $request->path === '/doctors' => $this->doctors->register($request),
            $request->method === 'POST' && preg_match('#^/doctors/([\w-]{1,64})/specialties$#', $request->path, $m) === 1 => $this->doctors->associateSpecialty($request, $m[1]),
            $request->method === 'GET' && $request->path === '/availability' => $this->doctors->availability($request),
            default => $this->view->render(404, ['code' => 'ROUTE_NOT_FOUND']),
        };
    }
}
