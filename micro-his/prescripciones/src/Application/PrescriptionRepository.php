<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Application;

interface PrescriptionRepository
{
    /** Revierte las escrituras si la operación (incluida auditoría) falla. */
    public function transaction(callable $operation): mixed;

    public function save(PrescriptionRecord $record): void;

    public function findById(string $prescriptionId): ?PrescriptionRecord;

    /** @return list<PrescriptionRecord> */
    public function forPatient(string $patientId): array;
}
