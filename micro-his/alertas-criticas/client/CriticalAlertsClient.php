<?php

declare(strict_types=1);

namespace MicroHisClient\Alertas;

use Closure;

/**
 * Cliente de Alertas: lo usan el consumidor del inbox de ASII-20 (publish) y la bandeja del médico (inbox).
 * No depende del código del servidor: solo del contrato HTTP de openapi.yaml.
 */
final class CriticalAlertsClient
{
    public int $attempts = 0;

    /** @param Closure(string, string, list<string>, ?string): ?array{0: int, 1: string} $transport null = sin respuesta (timeout o red) */
    public function __construct(private readonly string $baseUrl, private readonly string $tenant, private readonly string $user, private ?Closure $transport = null)
    {
        $this->transport ??= self::curl();
    }

    /**
     * Entrega «resultado validado». El servidor deduplica por result_version_id, así que reintentar es seguro.
     * Devuelve created, duplicate, not_critical o retry_later (el mensaje queda en el inbox para el siguiente ciclo).
     * @param array<string, mixed> $event
     */
    public function publish(array $event): string
    {
        $response = $this->send('POST', '/api/v1/internal/result-validated', json_encode($event), retryOn5xx: true);
        if ($response === null || $response[0] >= 500) {
            return 'retry_later';
        }
        $data = json_decode($response[1], true);

        return $response[0] < 400 ? $data['outcome'] : throw new \DomainException($data['code'] ?? "HTTP {$response[0]}");
    }

    /** @return list<array<string, mixed>>|null alertas pendientes del usuario, o null si Alertas no respondió */
    public function inbox(): ?array
    {
        $response = $this->send('GET', '/api/v1/critical-alerts', null, retryOn5xx: false);

        return $response !== null && $response[0] === 200 ? json_decode($response[1], true)['alerts'] : null;
    }

    /** Un solo reintento ante 502/503/504 o sin respuesta (y ante cualquier 5xx si la operación es idempotente). */
    private function send(string $method, string $path, ?string $body, bool $retryOn5xx): ?array
    {
        $headers = ["X-Tenant-ID: {$this->tenant}", "X-User-Ref: {$this->user}", 'X-Request-Id: ' . bin2hex(random_bytes(8)), 'Content-Type: application/json'];
        $this->attempts = 0;
        do {
            $response = ($this->transport)($method, $this->baseUrl . $path, $headers, $body);
        } while (++$this->attempts < 2 && ($response === null || in_array($response[0], [502, 503, 504], true) || ($retryOn5xx && $response[0] >= 500)));

        return $response;
    }

    private static function curl(): Closure
    {
        return static function (string $method, string $url, array $headers, ?string $body): ?array {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => $body, CURLOPT_CONNECTTIMEOUT_MS => 500, CURLOPT_TIMEOUT_MS => 2000]);
            $out = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

            return $out === false || $status === 0 ? null : [$status, (string) $out];
        };
    }
}
