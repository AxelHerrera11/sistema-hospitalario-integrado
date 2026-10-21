<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Application;

use MicroHis\MedicationCatalog\Domain\Medication;

/** Puerto de persistencia que necesita el alta. */
interface MedicationRegistry
{
    public function findByCode(string $code): ?Medication;

    /** Devuelve el registro confirmado con su identificador. */
    public function insert(Medication $medication): Medication;
}
