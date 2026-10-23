<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Presentation;

use MicroHis\NotasSoap\Application\PermissionChecker;
use MicroHis\NotasSoap\Domain\Uuid;

/**
 * Servidor API v1: versiona las rutas, autentica, autoriza con RBAC y agrega trace_id a toda respuesta.
 * Delega en el Router MVC de la Semana 4; no conoce casos de uso, SQL ni reglas de negocio.
 */
final class ApiKernel
{
    public const PREFIX = '/api/v1';

    /** Permiso `modulo.accion` que protege cada ruta (método + patrón sin el prefijo). */
    private const PERMISSIONS = [
        ['POST', '#^/care-episodes/[\w.-]+/soap-notes$#', 'notas_soap.crear'],
        ['POST', '#^/soap-notes/[\w.-]+/diagnoses$#', 'notas_soap.asociar_diagnostico'],
    ];

    /** @var \Closure(): string */
    private readonly \Closure $traceIds;

    /** @param (\Closure(array{trace_id: string, actor: string, method: string, path: string, status: int}): void)|null $log bitácora de acceso, sin el cuerpo */
    public function __construct(
        private readonly Router $router,
        private readonly PermissionChecker $permissions,
        private readonly JsonView $view,
        ?\Closure $traceIds = null,
        private readonly ?\Closure $log = null,
    ) {
        $this->traceIds = $traceIds ?? Uuid::v4(...);
    }

    /**
     * @param bool $validJson false si el front controller no pudo decodificar el cuerpo: 422 con trace_id, como cualquier otro error
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public function handle(Request $request, bool $validJson = true): array
    {
        $traceId = ($this->traceIds)();
        $response = $validJson ? $this->route($request) : $this->view->render(422, ['code' => 'PAYLOAD_INVALID']);
        $data = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR) + ['trace_id' => $traceId];
        $response = $this->view->render($response['status'], $data);
        $response['headers']['X-Trace-Id'] = $traceId;
        $actor = $request->headers['X-User-ID'] ?? '';
        $this->log === null || ($this->log)(['trace_id' => $traceId, 'actor' => is_string($actor) ? $actor : '', 'method' => $request->method, 'path' => $request->path, 'status' => $response['status']]);
        return $response;
    }

    /** Cada salida temprana termina aquí con su código: 404, 401, 403 o 503 (RBAC caído: falla cerrada). Después decide el dominio (p. ej. 403 NOT_TREATING_PHYSICIAN). */
    private function route(Request $request): array
    {
        if (!str_starts_with($request->path, self::PREFIX . '/')) {
            return $this->view->render(404, ['code' => 'ROUTE_NOT_FOUND']);
        }
        $path = substr($request->path, strlen(self::PREFIX));
        $actor = $request->headers['X-User-ID'] ?? '';
        if (!is_string($actor) || trim($actor) === '') {
            return $this->view->render(401, ['code' => 'UNAUTHENTICATED']);
        }
        // Falla cerrada: una ruta sin permiso configurado no se expone, aunque el Router la conozca.
        $permission = self::permissionFor($request->method, $path);
        if ($permission === null) {
            return $this->view->render(404, ['code' => 'ROUTE_NOT_FOUND']);
        }
        try {
            $allowed = $this->permissions->allows($actor, $permission);
        } catch (\RuntimeException) {
            return $this->view->render(503, ['code' => 'AUTHORIZATION_UNAVAILABLE']);
        }
        if (!$allowed) {
            return $this->view->render(403, ['code' => 'FORBIDDEN', 'permission' => $permission]);
        }
        return $this->router->dispatch(new Request($request->method, $path, $request->headers, $request->body));
    }

    public static function permissionFor(string $method, string $path): ?string
    {
        foreach (self::PERMISSIONS as [$verb, $pattern, $permission]) {
            if ($verb === $method && preg_match($pattern, $path) === 1) {
                return $permission;
            }
        }
        return null;
    }
}
