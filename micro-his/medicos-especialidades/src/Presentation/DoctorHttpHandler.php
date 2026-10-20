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
            self::text($headers, 'X-User-ID'),
            self::text($body, 'local_user_ref_id'),
            self::text($body, 'license_number'),
            self::text($body, 'specialty_code'),
            self::text($body, 'valid_from'),
        )]);
    }

    public function associateSpecialty(array $headers, string $doctorId, array $body): array
    {
        return $this->respond(201, function () use ($headers, $doctorId, $body): array {
            $isPrimary = $body['is_primary'] ?? false;
            $validTo = $body['valid_to'] ?? null;
            if (!is_bool($isPrimary) || ($validTo !== null && !is_string($validTo))) {
                throw new DoctorRuleViolation('PAYLOAD_INVALID', 422);
            }
            $link = $this->associate->execute(self::text($headers, 'X-User-ID'), $doctorId, self::text($body, 'specialty_code'), $isPrimary, self::text($body, 'valid_from'), $validTo);
            return ['doctor_profile_id' => $doctorId, 'specialty_id' => $link->specialtyId, 'is_primary' => $link->isPrimary];
        });
    }

    public function availability(array $query): array
    {
        return $this->respond(200, fn () => $this->availability->execute(self::text($query, 'specialty'), self::text($query, 'date')));
    }

    /** Campo de texto opcional: ausente equivale a cadena vacía; cualquier otro tipo es 422 antes de llegar al caso de uso. */
    private static function text(array $source, string $key): string
    {
        $value = $source[$key] ?? '';
        return is_string($value) ? $value : throw new DoctorRuleViolation('PAYLOAD_INVALID', 422);
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
