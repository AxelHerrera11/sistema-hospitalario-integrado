<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Presentation;

use MicroHis\Medicos\Application\AssociateSpecialty;
use MicroHis\Medicos\Application\QueryAvailability;
use MicroHis\Medicos\Application\RegisterDoctor;
use MicroHis\Medicos\Domain\DoctorRuleViolation;

/** Traduce la petición HTTP al caso de uso y el resultado a estado + cuerpo JSON. Sin SQL ni reglas de negocio. */
final class DoctorHttpHandler
{
    public function __construct(private readonly RegisterDoctor $register, private readonly AssociateSpecialty $associate, private readonly QueryAvailability $availability)
    {
    }

    public function registerDoctor(array $headers, array $body): array
    {
        return $this->respond(201, fn () => ['doctor_profile_id' => $this->register->execute(
            $headers['X-User-ID'] ?? '',
            (string) ($body['local_user_ref_id'] ?? ''),
            (string) ($body['license_number'] ?? ''),
            (string) ($body['specialty_code'] ?? ''),
            (string) ($body['valid_from'] ?? ''),
        )]);
    }

    public function associateSpecialty(array $headers, string $doctorId, array $body): array
    {
        return $this->respond(201, function () use ($headers, $doctorId, $body): array {
            $link = $this->associate->execute($headers['X-User-ID'] ?? '', $doctorId, (string) ($body['specialty_code'] ?? ''), (bool) ($body['is_primary'] ?? false), (string) ($body['valid_from'] ?? ''), $body['valid_to'] ?? null);
            return ['doctor_profile_id' => $doctorId, 'specialty_id' => $link->specialtyId, 'is_primary' => $link->isPrimary];
        });
    }

    public function availability(array $query): array
    {
        return $this->respond(200, fn () => $this->availability->execute((string) ($query['specialty'] ?? ''), (string) ($query['date'] ?? '')));
    }

    private function respond(int $status, callable $action): array
    {
        try {
            return ['status' => $status, 'body' => $action()];
        } catch (DoctorRuleViolation $e) {
            return ['status' => $e->status, 'body' => ['code' => $e->getMessage()]];
        } catch (\RuntimeException) {
            return ['status' => 500, 'body' => ['code' => 'DOCTOR_PERSISTENCE_ERROR']];
        }
    }
}
