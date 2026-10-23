<?php

declare(strict_types=1);

namespace MicroHisClient\CatalogoLab;

use Closure;

require_once __DIR__ . '/CatalogUnavailable.php';

/**
 * Cliente del catálogo para los consumidores (Órdenes ASII-16, Resultados ASII-19, Alertas ASII-21).
 * No depende del código del servidor: solo del contrato HTTP de openapi.yaml.
 */
final class LabCatalogClient
{
    public int $attempts = 0;

    /** @param Closure(string, string, list<string>, ?string): ?array{0: int, 1: string} $transport null = sin respuesta (timeout o red) */
    public function __construct(private readonly string $baseUrl, private readonly string $tenant, private readonly string $user, private ?Closure $transport = null)
    {
        $this->transport ??= self::curl();
    }

    /** @return list<array<string, mixed>> @throws CatalogUnavailable */
    public function activeTests(string $date, string $search = ''): array
    {
        return $this->get('/api/v1/lab-tests?' . http_build_query(['date' => $date, 'search' => $search]))['lab_tests'];
    }

    /** GET es idempotente: un solo reintento ante 502/503/504 o sin respuesta; un 4xx no se reintenta. */
    private function get(string $path): array
    {
        $headers = ["X-Tenant-ID: {$this->tenant}", "X-User-Ref: {$this->user}", 'X-Request-Id: ' . bin2hex(random_bytes(8))];
        $this->attempts = 0;
        do {
            $response = ($this->transport)('GET', $this->baseUrl . $path, $headers, null);
        } while (++$this->attempts < 2 && ($response === null || in_array($response[0], [502, 503, 504], true)));
        if ($response === null || $response[0] >= 500) {
            throw new CatalogUnavailable('Catálogo de pruebas no disponible');
        }
        $body = json_decode($response[1], true);
        if ($response[0] >= 400) {
            throw new \DomainException($body['code'] ?? "HTTP {$response[0]}");
        }

        return $body;
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
