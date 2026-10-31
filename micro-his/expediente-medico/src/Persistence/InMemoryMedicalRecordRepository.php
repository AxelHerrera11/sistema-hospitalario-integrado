<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Persistence;

use MicroHis\ExpedienteMedico\Application\MedicalRecordRepository;
use MicroHis\ExpedienteMedico\Application\PersistenceFailure;
use MicroHis\ExpedienteMedico\Domain\CareEpisode;
use MicroHis\ExpedienteMedico\Domain\MedicalRecord;

/**
 * Adaptador InMemory: SOLO para pruebas aisladas y desarrollo, no es integración real. Mismo contrato que el PDO:
 * un expediente por paciente, revierte si $confirm falla. $down simula el almacén caído; $racer simula otro
 * proceso que abre el mismo expediente justo antes del INSERT.
 */
final class InMemoryMedicalRecordRepository implements MedicalRecordRepository
{
    /** @var array<string, MedicalRecord> expedientes por local_patient_id */
    public array $records = [];

    /** @param list<CareEpisode> $episodes */
    public function __construct(private readonly array $episodes = [], public bool $down = false, private ?MedicalRecord $racer = null)
    {
    }

    public function findByPatient(string $patientId): ?MedicalRecord
    {
        $this->assertUp();

        return $this->records[$patientId] ?? null;
    }

    public function open(MedicalRecord $record, callable $confirm): MedicalRecord
    {
        $this->assertUp();
        if ($this->racer !== null) {
            $this->records[$this->racer->patientId] = $this->racer;
            $this->racer = null;
        }
        if (isset($this->records[$record->patientId])) {
            return $this->records[$record->patientId];
        }
        $this->records[$record->patientId] = $record;
        try {
            $confirm($record);
        } catch (\Throwable $e) {
            unset($this->records[$record->patientId]);
            throw $e;
        }

        return $record;
    }

    public function episodesOf(string $patientId): array
    {
        $this->assertUp();

        return array_values(array_filter($this->episodes, static fn (CareEpisode $e): bool => $e->patientId === $patientId));
    }

    private function assertUp(): void
    {
        if ($this->down) {
            throw new PersistenceFailure('memory_store_down');
        }
    }
}
