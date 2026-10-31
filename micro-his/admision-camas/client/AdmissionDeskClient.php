<?php

declare(strict_types=1);

namespace MicroHisClient\Admision;

use Closure;

require_once __DIR__ . '/AdmissionUnavailable.php';

/**
 * Cliente de Admisión para el mostrador, Traslados (ASII-08) y el Dashboard de ocupación (ASII-09).
 * No depende del código del servidor: solo del contrato HTTP de openapi.yaml.
 */
final class AdmissionDeskClient
{
    public int $attempts = 0;

    /** @param Closure(string, string, list<string>, ?string): ?array{0: int, 1: string} $transport null = sin respuesta (timeout o red) */
    public function __construct(private readonly string $baseUrl, private readonly string $tenant, private readonly string $user, private ?Closure $transport = null)
    {
        $this->transport ??= self::curl();
    }

    /** @return list<array<string, mixed>> GET idempotente: se reintenta una vez. @throws AdmissionUnavailable */
    public function availableBeds(string $type = ''): array
    {
        return $this->send('GET', '/api/v1/beds/available?' . http_build_query(['type' => $type]), null, retry: true)['beds'];
    }

    /** @param array<string, mixed> $admission @return array<string, mixed> POST no idempotente: NO se reintenta. @throws AdmissionUnavailable */
    public function admit(array $admission): array
    {
        return $this->send('POST', '/api/v1/admissions', json_encode($admission), retry: false)['admission'];
    }

    private function send(string $method, string $path, ?string $body, bool $retry): array
    {
        $headers = ["X-Tenant-ID: {$this->tenant}", "X-User-Ref: {$this->user}", 'X-Request-Id: ' . bin2hex(random_bytes(8)), 'Content-Type: application/json'];
        $this->attempts = 0;
        do {
            $response = ($this->transport)($method, $this->baseUrl . $path, $headers, $body);
        } while (++$this->attempts < ($retry ? 2 : 1) && ($response === null || in_array($response[0], [502, 503, 504], true)));
        if ($response === null || $response[0] >= 500) {
            throw new AdmissionUnavailable($method === 'POST' ? 'Admisión no respondió: verifique la cama antes de repetir el alta' : 'Admisión no disponible');
        }
        $data = json_decode($response[1], true);
        if ($response[0] >= 400) {
            throw new \DomainException($data['code'] ?? "HTTP {$response[0]}");
        }

        return $data;
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
