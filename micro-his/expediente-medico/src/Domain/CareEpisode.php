<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Domain;

/**
 * Episodio clínico de solo lectura (care_episodes, migración hospital 000200). Lo crean Admisión y los flujos
 * clínicos; el expediente solo lo reúne en la línea de tiempo. Replica care_episodes_type_check,
 * care_episodes_status_check y care_episodes_status_period_check.
 */
final readonly class CareEpisode
{
    private const TYPES = ['outpatient', 'emergency', 'inpatient'];

    public function __construct(
        public string $id,
        public string $patientId,
        public string $type,
        public string $status,
        public \DateTimeImmutable $startedAt,
        public ?\DateTimeImmutable $endedAt,
        public string $reason,
        public ?string $serviceCode,
    ) {
        $period = match ($status) {
            'open' => $endedAt === null,
            'closed', 'cancelled' => $endedAt !== null && $endedAt > $startedAt,
            default => false,
        };
        if ($id === '' || $patientId === '' || !in_array($type, self::TYPES, true) || !$period || trim($reason) === '') {
            throw new ExpedienteRuleViolation('EPISODIO_INCONSISTENTE', 500);
        }
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
