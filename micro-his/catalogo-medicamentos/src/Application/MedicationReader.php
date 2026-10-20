<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Application;

use MicroHis\MedicationCatalog\Domain\Medication;

/** Puerto de persistencia de solo lectura. */
interface MedicationReader
{
    /** @return list<Medication> */
    public function search(string $term, int $limit): array;

    public function findById(string $id): ?Medication;
}
