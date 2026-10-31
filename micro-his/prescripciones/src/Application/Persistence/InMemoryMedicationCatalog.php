<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Application\Persistence;

use MicroHis\Prescripciones\Application\Exception\MedicationNotFound;
use MicroHis\Prescripciones\Application\MedicationCatalog;
use MicroHis\Prescripciones\Domain\MedicationSnapshot;

/** Implementación InMemory para tests. */
final class InMemoryMedicationCatalog implements MedicationCatalog
{
    /** @var array<string, MedicationSnapshot> */
    private array $catalog = [];

    public function __construct(array $initial = [])
    {
        foreach ($initial as $med) {
            $this->catalog[$med->id] = $med;
        }
    }

    public function find(string $medicationId): MedicationSnapshot
    {
        if (! isset($this->catalog[$medicationId])) {
            throw new MedicationNotFound($medicationId);
        }
        $med = $this->catalog[$medicationId];
        if (! $med->active) {
            throw new MedicationNotFound($medicationId);
        }

        return $med;
    }

    /** Para tests: añade/actualiza un medicamento. */
    public function upsert(MedicationSnapshot $med): void
    {
        $this->catalog[$med->id] = $med;
    }

    /** Para tests: limpia el catálogo. */
    public function clear(): void
    {
        $this->catalog = [];
    }
}
