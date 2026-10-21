<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Application;

use MicroHis\MedicationCatalog\Domain\Medication;
use MicroHis\MedicationCatalog\Domain\Validity;

/** Contrato del cliente de cambio de vigencia (Semana 2, ISP). */
interface MedicationValidityControl
{
    public function changeValidity(string $id, Validity $validity): Medication;
}
