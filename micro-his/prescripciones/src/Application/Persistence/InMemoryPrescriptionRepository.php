<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Application\Persistence;

use MicroHis\Prescripciones\Application\PrescriptionRepository;
use MicroHis\Prescripciones\Domain\Prescription;

/** Implementación InMemory para tests. */
final class InMemoryPrescriptionRepository implements PrescriptionRepository
{
    /** @var array<string, Prescription> */
    private array $store = [];

    public function save(Prescription $prescription): void
    {
        $this->store[$prescription->id] = $prescription;
    }

    public function findById(string $id): ?Prescription
    {
        return $this->store[$id] ?? null;
    }

    /** @return list<Prescription> */
    public function listByPatient(string $patientId): array
    {
        return array_values(array_filter($this->store, static fn (Prescription $p): bool => $p->patientId === $patientId));
    }

    /** Para tests: limpia el store. */
    public function clear(): void
    {
        $this->store = [];
    }
}