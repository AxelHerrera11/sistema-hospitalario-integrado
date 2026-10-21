<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Application;

use MicroHis\MedicationCatalog\Domain\Medication;
use MicroHis\MedicationCatalog\Domain\Validity;

/** Puerto de persistencia que necesita el cambio de vigencia. */
interface MedicationValidityStore
{
    public function findById(string $id): ?Medication;

    /** Debe confirmar la actualización o lanzar PersistenceFailure. */
    public function updateValidity(string $id, Validity $validity): void;
}
