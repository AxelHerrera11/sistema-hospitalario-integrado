<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Client;

/**
 * Cliente del API v1 de Médicos para los módulos que lo consumen (Citas, Admisión).
 * Solo la consulta de disponibilidad se reintenta, una vez, porque es idempotente; las altas nunca, para no duplicarlas.
 */
final class DoctorApiClient
{
    public function __construct(private readonly HttpTransport $transport, private readonly string $actorId)
    {
    }

    public function registerDoctor(string $userRef, string $license, string $specialtyCode, string $validFrom): string
    {
        $body = ['local_user_ref_id' => $userRef, 'license_number' => $license, 'specialty_code' => $specialtyCode, 'valid_from' => $validFrom];
        return $this->expect(201, $this->transport->send('POST', '/api/v1/doctors', $this->actorId, [], $body))['doctor_profile_id'];
    }

    /** @return array{doctor_profile_id: string, specialty_id: string, is_primary: bool} */
    public function associateSpecialty(string $doctorId, string $specialtyCode, bool $isPrimary, string $validFrom, ?string $validTo = null): array
    {
        $body = ['specialty_code' => $specialtyCode, 'is_primary' => $isPrimary, 'valid_from' => $validFrom, 'valid_to' => $validTo];
        $data = $this->expect(201, $this->transport->send('POST', '/api/v1/doctors/' . rawurlencode($doctorId) . '/specialties', $this->actorId, [], $body));
        unset($data['trace_id']);
        return $data;
    }

    /** @return array{code: string, doctors: list<array{doctor_id: string, status: string, slots: list<string>}>} */
    public function availability(string $specialtyCode, string $date): array
    {
        $query = ['specialty' => $specialtyCode, 'date' => $date];
        try {
            $response = $this->transport->send('GET', '/api/v1/availability', $this->actorId, $query);
        } catch (DoctorApiUnavailable) {
            $response = $this->transport->send('GET', '/api/v1/availability', $this->actorId, $query); // único reintento; si falla, se propaga
        }
        $data = $this->expect(200, $response);
        return ['code' => $data['code'], 'doctors' => $data['doctors']];
    }

    /** Cualquier estado distinto del esperado termina en DoctorApiError con el código y el trace_id del servidor. */
    private function expect(int $status, array $response): array
    {
        if ($response['status'] !== $status) {
            throw new DoctorApiError($response['status'], (string) ($response['body']['code'] ?? 'UNEXPECTED_RESPONSE'), (string) ($response['body']['trace_id'] ?? ''));
        }
        return $response['body'];
    }
}
