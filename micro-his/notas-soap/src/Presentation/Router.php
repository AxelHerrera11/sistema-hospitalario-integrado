<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Presentation;

/** Rutas del módulo: cada una apunta a una acción del controlador; lo demás responde 404. */
final class Router
{
    public function __construct(private readonly SoapNoteController $notes, private readonly JsonView $view)
    {
    }

    public function dispatch(Request $request): array
    {
        return match (true) {
            $request->method === 'POST' && preg_match('#^/care-episodes/([\w.-]+)/soap-notes$#', $request->path, $m) === 1 => $this->notes->registerNote($request, $m[1]),
            $request->method === 'POST' && preg_match('#^/soap-notes/([\w.-]+)/diagnoses$#', $request->path, $m) === 1 => $this->notes->associateDiagnosis($request, $m[1]),
            default => $this->view->render(404, ['code' => 'ROUTE_NOT_FOUND']),
        };
    }
}
