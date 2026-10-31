<?php

declare(strict_types=1);

namespace MicroHis\Admision\Application;

use Closure;
use MicroHis\Admision\Domain\Admission;
use MicroHis\Admision\Domain\DomainRuleViolation;

/** UC-ADM-01, 02, 04 y 05 Admitir paciente y asignar cama: sin dos pacientes por cama ni dos camas por episodio, con auditoría. */
final class AdmitPatient
{
    /** @param Closure(): string $now @param Closure(): string $newId */
    public function __construct(private readonly AdmissionRepository $store, private readonly Closure $now, private readonly Closure $newId) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data, string $actor): Admission
    {
        // Solo texto: un arreglo en el JSON se trata como ausente (regla de dominio), nunca como «Array» ni como error 500.
        $text = static fn (string $k): string => is_string($data[$k] ?? null) ? $data[$k] : '';
        $patientId = $text('patient_id');
        $bed = $this->store->bed($text('bed_id'));
        match (true) {
            !$this->store->patientExists($patientId) => throw new DomainRuleViolation('PATIENT_NOT_FOUND', 'patient_id'),
            $bed === null => throw new DomainRuleViolation('BED_NOT_FOUND', 'bed_id'),
            default => null,
        };
        $type = is_string($data['bed_type'] ?? null) ? $data['bed_type'] : null;
        $admission = Admission::admit(($this->newId)(), ($this->newId)(), $patientId, $bed, $type, $text('reason'),
            $this->store->hasOpenInpatientEpisode($patientId), ($this->now)());
        try {
            $this->store->save($admission, $actor);
        } catch (PersistenceFailure $failure) {
            // Otra admisión tomó la cama entre la lectura y el guardado: la base decidió.
            throw $failure->duplicate ? new DomainRuleViolation('BED_NOT_AVAILABLE', 'bed_id') : $failure;
        }

        return $admission;
    }
}
