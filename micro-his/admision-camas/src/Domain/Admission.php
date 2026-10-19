<?php

declare(strict_types=1);

namespace MicroHis\Admision\Domain;

/** Hospitalización: episodio inpatient abierto + estancia en una cama (UC-ADM-01 y 03). Nace válida o no nace. */
final readonly class Admission
{
    private function __construct(
        public string $episodeId, public string $stayId, public string $patientId,
        public string $bedId, public string $reason, public string $startedAt,
    ) {}

    /** @param array{bed_id: string, type: string, status: string} $bed */
    public static function admit(string $episodeId, string $stayId, string $patientId, array $bed, ?string $requiredType,
        string $reason, bool $alreadyAdmitted, string $now): self
    {
        $rule = match (true) {
            trim($reason) === '' => ['REASON_REQUIRED', 'reason'],
            $alreadyAdmitted => ['PATIENT_ALREADY_ADMITTED', 'patient_id'],
            $bed['status'] !== 'available' => ['BED_NOT_AVAILABLE', 'bed_id'],
            $requiredType !== null && $requiredType !== $bed['type'] => ['BED_TYPE_MISMATCH', 'bed_type'],
            default => null,
        };
        if ($rule !== null) {
            throw new DomainRuleViolation(...$rule);
        }

        return new self($episodeId, $stayId, $patientId, $bed['bed_id'], trim($reason), $now);
    }
}
