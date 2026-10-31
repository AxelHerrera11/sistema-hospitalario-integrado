<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Client;

/**
 * Cliente HTTP de la API de órdenes. Solo conoce el contrato; no carga el dominio ni el repositorio.
 * Reintenta una vez únicamente las lecturas (GET); las escrituras dependen de Idempotency-Key.
 */
final class LabOrderClient
{
    /** @param list<string> $permissions */
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $tenant,
        private readonly string $userRef,
        private readonly float $timeoutSeconds = 2.0,
        private readonly array $permissions = [
            'laboratorio.ordenes.crear',
            'laboratorio.ordenes.ver',
            'laboratorio.ordenes.actualizar',
            'laboratorio.ordenes.cancelar',
        ],
    ) {
    }

    /** @param array<string, mixed> $draft @return array{status: int, body: array<string, mixed>} */
    public function create(array $draft): array
    {
        $draft['idempotency_key'] ??= bin2hex(random_bytes(16));

        return $this->request('POST', '/api/v1/lab-orders', $draft, ['Idempotency-Key: ' . $draft['idempotency_key']], false);
    }

    /** @return array{status: int, body: array<string, mixed>} */
    public function get(string $orderId): array
    {
        return $this->request('GET', '/api/v1/lab-orders/' . rawurlencode($orderId), null, [], true);
    }

    /** @param array<string, string> $filters @return array{status: int, body: array<string, mixed>} */
    public function list(array $filters = []): array
    {
        $query = $filters === [] ? '' : '?' . http_build_query($filters);

        return $this->request('GET', '/api/v1/lab-orders' . $query, null, [], true);
    }

    /** @return array{status: int, body: array<string, mixed>} */
    public function advance(string $orderId, string $status): array
    {
        return $this->request('PATCH', '/api/v1/lab-orders/' . rawurlencode($orderId) . '/status', ['status' => $status], [], false);
    }

    /** @return array{status: int, body: array<string, mixed>} */
    public function cancel(string $orderId, string $reason): array
    {
        return $this->request('POST', '/api/v1/lab-orders/' . rawurlencode($orderId) . '/cancel', ['reason' => $reason], [], false);
    }

    /**
     * @param array<string, mixed>|null $body
     * @param list<string> $extraHeaders
     * @return array{status: int, body: array<string, mixed>}
     */
    private function request(string $method, string $path, ?array $body, array $extraHeaders, bool $retry): array
    {
        $attempts = $retry ? 2 : 1;
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $handle = curl_init($this->baseUrl . $path);
            $headers = array_merge([
                'Accept: application/json, application/problem+json',
                'Content-Type: application/json',
                'X-Tenant-ID: ' . $this->tenant,
                'X-User-Ref: ' . $this->userRef,
                'X-Permissions: ' . implode(',', $this->permissions),
            ], $extraHeaders);
            curl_setopt_array($handle, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT_MS => (int) ($this->timeoutSeconds * 1000),
                CURLOPT_TIMEOUT_MS => (int) ($this->timeoutSeconds * 1000),
                CURLOPT_POSTFIELDS => $body === null ? null : json_encode($body, JSON_UNESCAPED_UNICODE),
            ]);
            $raw = curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            curl_close($handle);

            if (is_string($raw) && $status > 0 && $status < 500) {
                $decoded = json_decode($raw, true);

                return ['status' => $status, 'body' => is_array($decoded) ? $decoded : ['raw' => $raw]];
            }
            if ($attempt < $attempts) {
                usleep(100_000);
            }
        }

        throw new LabOrdersUnavailable('No fue posible comunicarse con el servidor de órdenes.');
    }
}
