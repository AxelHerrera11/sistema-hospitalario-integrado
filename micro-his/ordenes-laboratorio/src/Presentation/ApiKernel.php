<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Presentation;

use Closure;

/**
 * Kernel HTTP de la API v1 (semana 5).
 * Resuelve tenant y usuario, propaga X-Request-ID, delega en el controller y devuelve problem+json
 * (RFC 9457) en los errores. No contiene reglas de negocio ni SQL.
 */
final class ApiKernel
{
    /** @param Closure(): string $newRequestId */
    public function __construct(
        private readonly LabOrderController $controller,
        private readonly Closure $newRequestId,
    ) {
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $input
     * @return array{status: int, headers: array<string, string>, body: array<string, mixed>|string}
     */
    public function handle(string $method, string $path, array $headers, array $input): array
    {
        $requestId = $this->header($headers, 'x-request-id') ?: ($this->newRequestId)();

        if ($method === 'GET' && in_array($path, ['/health', '/health/ready'], true)) {
            return $this->json(200, ['status' => $path === '/health' ? 'ok' : 'ready'], $requestId);
        }
        if (!str_starts_with($path, '/api/v1/')) {
            return $this->problem(404, 'NOT_FOUND', $requestId);
        }

        $tenant = $this->header($headers, 'x-tenant-id');
        if ($tenant === '') {
            return $this->problem(400, 'TENANT_REQUIRED', $requestId);
        }
        $user = $this->header($headers, 'x-user-ref');
        if ($user === '') {
            return $this->problem(401, 'UNAUTHENTICATED', $requestId);
        }
        $required = $this->requiredPermission($method, $path);
        $granted = array_filter(array_map('trim', explode(',', $this->header($headers, 'x-permissions'))));
        if ($required !== null && !in_array($required, $granted, true)) {
            return $this->problem(403, 'FORBIDDEN', $requestId);
        }
        $idempotency = $this->header($headers, 'idempotency-key');
        if ($idempotency !== '' && !isset($input['idempotency_key'])) {
            $input['idempotency_key'] = $idempotency;
        }

        $result = $this->controller->handle($method, substr($path, strlen('/api/v1')), $input, $user, $tenant);
        if ($result['status'] >= 400) {
            $body = $result['body'];
            $code = is_array($body) ? (string) ($body['error'] ?? 'ERROR') : 'ERROR';
            $field = is_array($body) ? (string) ($body['field'] ?? '') : '';

            return $this->problem($result['status'], $code, $requestId, $field);
        }

        return [
            'status' => $result['status'],
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'X-Request-ID' => $requestId,
                'Cache-Control' => 'no-store',
            ],
            'body' => $result['body'],
        ];
    }

    /** @param array<string, mixed> $body @return array{status: int, headers: array<string, string>, body: array<string, mixed>} */
    private function json(int $status, array $body, string $requestId): array
    {
        return [
            'status' => $status,
            'headers' => ['Content-Type' => 'application/json; charset=utf-8', 'X-Request-ID' => $requestId],
            'body' => $body,
        ];
    }

    /** @return array{status: int, headers: array<string, string>, body: array<string, mixed>} */
    private function problem(int $status, string $code, string $requestId, string $field = ''): array
    {
        $body = [
            'type' => 'about:blank',
            'title' => $code,
            'status' => $status,
            'code' => $code,
            'request_id' => $requestId,
        ];
        if ($field !== '') {
            $body['field'] = $field;
        }

        return [
            'status' => $status,
            'headers' => ['Content-Type' => 'application/problem+json; charset=utf-8', 'X-Request-ID' => $requestId, 'Cache-Control' => 'no-store'],
            'body' => $body,
        ];
    }

    private function requiredPermission(string $method, string $path): ?string
    {
        if ($method === 'POST' && $path === '/api/v1/lab-orders') {
            return 'laboratorio.ordenes.crear';
        }
        if ($method === 'GET' && ($path === '/api/v1/lab-orders' || preg_match('#^/api/v1/lab-orders/[^/]+$#', $path) === 1)) {
            return 'laboratorio.ordenes.ver';
        }
        if ($method === 'PATCH' && preg_match('#^/api/v1/lab-orders/[^/]+/status$#', $path) === 1) {
            return 'laboratorio.ordenes.actualizar';
        }
        if ($method === 'POST' && preg_match('#^/api/v1/lab-orders/[^/]+/cancel$#', $path) === 1) {
            return 'laboratorio.ordenes.cancelar';
        }

        return null;
    }

    /** @param array<string, string> $headers */
    private function header(array $headers, string $name): string
    {
        foreach ($headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return trim($value);
            }
        }

        return '';
    }
}
