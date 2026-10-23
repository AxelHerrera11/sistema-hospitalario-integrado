<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Presentation;

use MicroHis\QaCalidad\Application\Actor;

/**
 * Punto de entrada HTTP /api/v1/qa/*: valida identidad y tenant, enruta al
 * controlador y convierte excepciones con ErrorMapper.
 *
 * $server trae la identidad resuelta por el servidor (user_id del JWT, tenant_id
 * del middleware de tenant); nunca se toma del cuerpo ni de la query.
 */
final class QaHttpHandler
{
    public const MAX_BODY_BYTES = 65536;

    private const PREFIX = '/api/v1/qa/runs';

    public function __construct(
        private readonly QaController $controller,
        private readonly ErrorMapper $errors,
    ) {}

    /** @return array{status: int, body: array<string, mixed>, headers: array<string, string>} */
    public function handle(string $method, string $path, array $server, array $headers, array $query, string $rawBody = ''): array
    {
        $headers = array_change_key_case($headers, CASE_LOWER);
        $trace = HttpResponse::correlation($headers['x-request-id'] ?? null);
        $route = $this->route($method, rtrim($path, '/'));
        if ($route === null) {
            return HttpResponse::error(404, 'ROUTE_NOT_FOUND', $trace);
        }

        $userId = $server['user_id'] ?? null;
        $tenantId = $server['tenant_id'] ?? null;
        $header = $headers['x-tenant-id'] ?? null;
        if (! is_string($userId) || $userId === '') {
            return HttpResponse::error(401, 'AUTH_REQUIRED', $trace);
        }
        if (! is_string($tenantId) || $tenantId === '') {
            return HttpResponse::error(503, 'TENANT_CONTEXT_UNAVAILABLE', $trace);
        }
        if (! is_string($header) || $header === '') {
            return HttpResponse::error(400, 'TENANT_REQUIRED', $trace);
        }
        if (! hash_equals($tenantId, $header)) {
            return HttpResponse::error(403, 'TENANT_MISMATCH', $trace);
        }

        $body = [];
        if ($method === 'POST') {
            if (strlen($rawBody) > self::MAX_BODY_BYTES) {
                return HttpResponse::error(413, 'PAYLOAD_TOO_LARGE', $trace);
            }
            $body = json_decode($rawBody === '' ? '{}' : $rawBody, true);
            if (! is_array($body)) {
                return HttpResponse::error(400, 'INVALID_JSON', $trace);
            }
        }

        try {
            return $this->dispatch($route, new Actor($userId, $tenantId), $query, $body, $trace);
        } catch (\Throwable $error) {
            return $this->errors->map($error, $trace);
        }
    }

    /** @return array{0: string, 1: ?string}|null [acción, id de ejecución] */
    private function route(string $method, string $path): ?array
    {
        if ($path === self::PREFIX) {
            return match ($method) {
                'GET' => ['index', null], 'POST' => ['store', null], default => null
            };
        }
        if (preg_match('#^'.self::PREFIX.'/([^/]+)(?:/(transitions|results|gate-decisions))?$#', $path, $m) !== 1) {
            return null;
        }
        $action = $m[2] ?? '';

        return match (true) {
            $action === '' && $method === 'GET' => ['show', $m[1]],
            $action === 'transitions' && $method === 'POST' => ['transition', $m[1]],
            $action === 'results' && $method === 'POST' => ['result', $m[1]],
            $action === 'gate-decisions' && $method === 'POST' => ['gate', $m[1]],
            default => null,
        };
    }

    private function dispatch(array $route, Actor $actor, array $query, array $body, string $trace): array
    {
        [$action, $runId] = $route;

        return match ($action) {
            'index' => $this->controller->index($actor, $query, $trace),
            'store' => $this->controller->store($actor, $body, $trace),
            'show' => $this->controller->show($actor, (string) $runId, $trace),
            'transition' => $this->controller->transition($actor, (string) $runId, $body, $trace),
            'result' => $this->controller->result($actor, (string) $runId, $body, $trace),
            'gate' => $this->controller->gate($actor, (string) $runId, $trace),
        };
    }
}
