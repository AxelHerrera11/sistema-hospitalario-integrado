<?php

declare(strict_types=1);

namespace MicroHisClient\Alergias;

use Closure;

/**
 * Cliente de alergias para Prescripciones (ASII-15, contrato AllergyChecker) y el Expediente (ASII-10).
 * No depende del código del servidor: solo del contrato HTTP de openapi.yaml.
 */
final class AllergyCheckClient
{
    public const UNAVAILABLE = ['level' => 'unavailable', 'message' => 'Alergias no disponibles: verificar con el paciente antes de prescribir', 'allergies' => []];
    public int $attempts = 0;

    /** @param Closure(string, string, list<string>, ?string): ?array{0: int, 1: string} $transport null = sin respuesta (timeout o red) */
    public function __construct(private readonly string $baseUrl, private readonly string $tenant, private readonly string $user, private ?Closure $transport = null)
    {
        $this->transport ??= self::curl();
    }

    /**
     * Alerta de alergias del paciente. Falla segura: si el servicio no responde o responde 5xx, devuelve
     * «no disponible» y nunca una lista vacía, porque «sin alergias» autorizaría prescribir a ciegas.
     * @return array{level: string, message: string, allergies: list<array<string, mixed>>}
     */
    public function alertFor(string $patientId): array
    {
        $headers = ["X-Tenant-ID: {$this->tenant}", "X-User-Ref: {$this->user}", 'X-Request-Id: ' . bin2hex(random_bytes(8))];
        $this->attempts = 0;
        do { // GET es idempotente: un solo reintento ante 502/503/504 o sin respuesta
            $response = ($this->transport)('GET', "{$this->baseUrl}/api/v1/patients/" . rawurlencode($patientId) . '/allergy-alert', $headers, null);
        } while (++$this->attempts < 2 && ($response === null || in_array($response[0], [502, 503, 504], true)));
        $alert = $response !== null && $response[0] === 200 ? (json_decode($response[1], true)['alert'] ?? null) : null;
        if ($response !== null && $response[0] >= 400 && $response[0] < 500) {
            throw new \DomainException(json_decode($response[1], true)['code'] ?? "HTTP {$response[0]}");
        }

        return is_array($alert) ? $alert : self::UNAVAILABLE;
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
