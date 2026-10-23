<?php

declare(strict_types=1);

namespace MicroHis\Alergias\Presentation;

use Closure;

/** Servidor API v1: correlación (X-Request-Id), tenant, salud y errores RFC 9457 alrededor del Controller. */
final class ApiKernel
{
    private const TITLES = [400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 409 => 'Conflict',
        422 => 'Unprocessable Content', 500 => 'Internal Server Error'];

    /** @param Closure(): bool $ready ¿la base responde? */
    public function __construct(private readonly AllergyController $controller, private readonly string $tenant, private readonly Closure $ready) {}

    /** @param array<string, string> $headers nombres en minúsculas @return array{0: int, 1: array<string, string>, 2: string} */
    public function handle(string $method, string $uri, array $headers, string $body): array
    {
        $rid = preg_match('/^[\w.-]{8,128}$/', $headers['x-request-id'] ?? '') ? $headers['x-request-id'] : bin2hex(random_bytes(8));
        $path = (string) parse_url($uri, PHP_URL_PATH);
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
        if ($method === 'GET' && ($path === '/health' || $path === '/health/ready')) {
            $ok = $path === '/health' || ($this->ready)();
            return $this->json($ok ? 200 : 503, ['status' => $ok ? 'ok' : 'unavailable', 'service' => 'alergias'], $rid);
        }
        $input = $method === 'POST' ? json_decode($body, true) : $query;
        $error = match (true) {
            !str_starts_with($path, '/api/v1/') => [404, ['error' => 'NOT_FOUND']],
            ($headers['x-tenant-id'] ?? '') === '' => [400, ['error' => 'TENANT_REQUIRED', 'field' => 'X-Tenant-ID']],
            $headers['x-tenant-id'] !== $this->tenant => [403, ['error' => 'TENANT_MISMATCH', 'field' => 'X-Tenant-ID']],
            !is_array($input) => [400, ['error' => 'INVALID_JSON']],
            default => null,
        };
        [$status, $payload] = $error ?? $this->controller->handle($method, substr($path, 7), $input, $headers['x-user-ref'] ?? null);
        if (is_string($payload)) {
            return [$status, ['Content-Type' => 'text/html; charset=utf-8', 'X-Request-Id' => $rid], $payload];
        }

        return $status < 400 ? $this->json($status, $payload, $rid) : [$status, ['Content-Type' => 'application/problem+json', 'X-Request-Id' => $rid],
            json_encode(array_filter(['type' => 'about:blank', 'title' => self::TITLES[$status], 'status' => $status, 'code' => $payload['error'],
                'field' => $payload['field'] ?? null, 'request_id' => $rid]), JSON_UNESCAPED_UNICODE)];
    }

    /** @return array{0: int, 1: array<string, string>, 2: string} */
    private function json(int $status, array $payload, string $rid): array
    {
        return [$status, ['Content-Type' => 'application/json', 'X-Request-Id' => $rid], json_encode($payload, JSON_UNESCAPED_UNICODE)];
    }
}
