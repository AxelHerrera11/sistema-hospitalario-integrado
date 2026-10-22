<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Application;

use MicroHis\Prescripciones\Domain\Prescription;

/**
 * Puerto de persistencia de prescripciones.
 * Implementaciones: InMemory (tests), PDO (producción).
 */
interface PrescriptionRepository
{
    /** Guarda una prescripción nueva (draft) o actualiza (firmada). */
    public function save(Prescription $prescription): void;

    /** Busca por ID. Devuelve null si no existe. */
    public function findById(string $id): ?Prescription;

    /** Lista prescripciones de un paciente. */
    public function listByPatient(string $patientId): array;
}