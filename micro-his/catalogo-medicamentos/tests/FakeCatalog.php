<?php

declare(strict_types=1);

use MicroHis\MedicationCatalog\Application\MedicationReader;
use MicroHis\MedicationCatalog\Application\MedicationRegistry;
use MicroHis\MedicationCatalog\Application\MedicationValidityStore;
use MicroHis\MedicationCatalog\Application\PersistenceFailure;
use MicroHis\MedicationCatalog\Domain\Medication;
use MicroHis\MedicationCatalog\Domain\Validity;

/** DOBLE de prueba de los tres puertos (no es un adaptador). $failOn simula fallos de persistencia por método. */
final class FakeCatalog implements MedicationRegistry, MedicationReader, MedicationValidityStore
{
    /** @var array<string, Medication> */
    public array $rows = [];
    public int $writes = 0;

    public function __construct(private array $failOn = [])
    {
    }

    public function findByCode(string $code): ?Medication
    {
        $this->fail(__FUNCTION__);
        foreach ($this->rows as $row) {
            if ($row->code === $code) {
                return $row;
            }
        }

        return null;
    }

    public function insert(Medication $medication): Medication
    {
        $this->fail(__FUNCTION__);
        $id = (string) (count($this->rows) + 1);
        $this->writes++;

        return $this->rows[$id] = Medication::restore($id, $medication->code, $medication->name,
            $medication->activeIngredient, $medication->pharmacologicalGroup, $medication->validity);
    }

    public function search(string $term, int $limit): array
    {
        $this->fail(__FUNCTION__);
        $matches = array_filter($this->rows, static fn (Medication $m): bool => stripos("{$m->code} {$m->name} {$m->activeIngredient}", $term) !== false);

        return array_slice(array_values($matches), 0, $limit);
    }

    public function findById(string $id): ?Medication
    {
        $this->fail(__FUNCTION__);

        return $this->rows[$id] ?? null;
    }

    public function updateValidity(string $id, Validity $validity): void
    {
        $this->fail(__FUNCTION__);
        $this->writes++;
        $this->rows[$id] = $this->rows[$id]->changeValidity($validity);
    }

    private function fail(string $method): void
    {
        if (in_array($method, $this->failOn, true)) {
            throw new PersistenceFailure("simulated {$method}");
        }
    }
}
