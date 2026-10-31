<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Domain;

/**
 * Expediente longitudinal: uno por paciente y por hospital (medical_records, migración hospital 000400).
 * Replica en el dominio las restricciones de la tabla para detectar datos inconsistentes antes de mostrarlos:
 * status open/closed y medical_records_closure_check.
 */
final readonly class MedicalRecord
{
    /** SUPUESTO S-03: formato del número de expediente, pendiente de validar con el grupo (la columna solo exige texto único). */
    public const NUMBER_PREFIX = 'EXP-';

    public function __construct(
        public string $id,
        public string $patientId,
        public string $recordNumber,
        public \DateTimeImmutable $openedAt,
        public string $status = 'open',
        public ?\DateTimeImmutable $closedAt = null,
    ) {
        $consistent = match ($status) {
            'open' => $closedAt === null,
            'closed' => $closedAt !== null && $closedAt >= $openedAt,
            default => false,
        };
        if ($id === '' || $patientId === '' || trim($recordNumber) === '' || !$consistent) {
            throw new ExpedienteRuleViolation('EXPEDIENTE_INCONSISTENTE', 500);
        }
    }

    /** Regla de apertura: solo un paciente activo recibe expediente nuevo; nace abierto y sin fecha de cierre. */
    public static function open(string $id, PatientSummary $patient, \DateTimeImmutable $now): self
    {
        if (!$patient->isActive()) {
            throw new ExpedienteRuleViolation('PACIENTE_NO_ACTIVO', 409);
        }

        return new self($id, $patient->id, self::NUMBER_PREFIX . $patient->code, $now);
    }
}
