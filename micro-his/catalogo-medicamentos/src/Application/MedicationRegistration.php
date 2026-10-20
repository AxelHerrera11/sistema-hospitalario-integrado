<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Application;

use MicroHis\MedicationCatalog\Domain\Medication;

/** Contrato del cliente de alta (Semana 2, ISP). */
interface MedicationRegistration
{
    public function register(array $draft): Medication;
}
