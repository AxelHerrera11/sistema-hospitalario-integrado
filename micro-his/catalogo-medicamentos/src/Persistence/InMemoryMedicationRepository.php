<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Persistence;

use MicroHis\MedicationCatalog\Application\DuplicateMedicationCode;
use MicroHis\MedicationCatalog\Application\MedicationRepository;
use MicroHis\MedicationCatalog\Application\PersistenceFailure;
use MicroHis\MedicationCatalog\Domain\Medication;
use MicroHis\MedicationCatalog\Domain\Validity;

/** Adaptador en memoria del Repository: mismas reglas observables que PdoMedicationCatalog, sin base de datos. */
final class InMemoryMedicationRepository implements MedicationRepository
{
    /** @var array<string, Medication> */
    private array $rows = [];
    private int $sequence = 0;

    public function findByCode(string $code): ?Medication
    {
        foreach ($this->rows as $row) {
            if ($row->code === $code) {
                return $row;
            }
        }

        return null;
    }

    public function findById(string $id): ?Medication
    {
        return $this->rows[$id] ?? null;
    }

    public function insert(Medication $medication): Medication
    {
        if ($this->findByCode($medication->code) !== null) {
            throw new DuplicateMedicationCode("El código {$medication->code} ya está registrado.");
        }
        $id = (string) ++$this->sequence;

        return $this->rows[$id] = Medication::restore($id, $medication->code, $medication->name,
            $medication->activeIngredient, $medication->pharmacologicalGroup, $medication->validity);
    }

    /** Coincidencia literal sin distinguir mayúsculas en código, nombre o principio activo; orden por nombre y código. */
    public function search(string $term, int $limit): array
    {
        $needle = mb_strtolower($term);
        $matches = array_values(array_filter($this->rows, static fn (Medication $m): bool =>
            str_contains(mb_strtolower("{$m->code}\n{$m->name}\n{$m->activeIngredient}"), $needle)));
        usort($matches, static fn (Medication $a, Medication $b): int => [$a->name, $a->code] <=> [$b->name, $b->code]);

        return array_slice($matches, 0, $limit);
    }

    public function updateValidity(string $id, Validity $validity): void
    {
        $current = $this->rows[$id] ?? throw new PersistenceFailure('No se confirmó el cambio de vigencia.');
        $this->rows[$id] = Medication::restore($id, $current->code, $current->name, $current->activeIngredient,
            $current->pharmacologicalGroup, $validity);
    }
}
