<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Client;

/**
 * Cliente del API v1 de signos vitales para los módulos que lo consumen (Notas SOAP, paneles, Alertas críticas).
 * Solo la lectura se reintenta, una vez, porque es idempotente; registrar nunca, para no duplicar mediciones ni alertas.
 */
final class VitalSignApiClient
{
    public function __construct(private readonly HttpTransport $transport, private readonly string $actorId)
    {
    }

    /**
     * @param array<string, float|int> $measurements signos medidos, p. ej. ['heart_rate' => 138]
     * @return array{vital_sign_id: string, code: string, severity: string, findings: array<string, string>}
     */
    public function record(string $episodeId, array $measurements, string $measuredAt): array
    {
        $path = '/api/v1/care-episodes/' . rawurlencode($episodeId) . '/vital-signs';
        return $this->withoutTrace($this->expect(201, $this->transport->send('POST', $path, $this->actorId, $measurements + ['measured_at' => $measuredAt])));
    }

    /** @return array{vital_sign_id: string, care_episode_id: string, measured_at: string, values: array<string, float>} */
    public function vitalSign(string $vitalSignId): array
    {
        $path = '/api/v1/vital-signs/' . rawurlencode($vitalSignId);
        try {
            $response = $this->transport->send('GET', $path, $this->actorId);
        } catch (VitalSignApiUnavailable) {
            $response = $this->transport->send('GET', $path, $this->actorId); // único reintento; si falla, se propaga
        }
        return $this->withoutTrace($this->expect(200, $response));
    }

    /** Cualquier estado distinto del esperado termina en VitalSignApiError con el código y el trace_id del servidor. */
    private function expect(int $status, array $response): array
    {
        if ($response['status'] !== $status) {
            throw new VitalSignApiError($response['status'], (string) ($response['body']['code'] ?? 'UNEXPECTED_RESPONSE'), (string) ($response['body']['trace_id'] ?? ''));
        }
        return $response['body'];
    }

    private function withoutTrace(array $data): array
    {
        unset($data['trace_id']);
        return $data;
    }
}
