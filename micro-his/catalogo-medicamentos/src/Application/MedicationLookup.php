<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Application;

use MicroHis\MedicationCatalog\Domain\Medication;

/** Contrato de solo lectura del cliente de búsqueda y consulta de vigencia (Semana 2, ISP). */
interface MedicationLookup
{
    /** @return list<Medication> */
    public function search(string $term, int $limit = 50): array;

    public function findById(string $id): ?Medication;
}
