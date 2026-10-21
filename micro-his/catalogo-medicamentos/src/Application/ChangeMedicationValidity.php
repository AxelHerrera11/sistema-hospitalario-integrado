<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Application;

use MicroHis\MedicationCatalog\Domain\Medication;
use MicroHis\MedicationCatalog\Domain\Validity;

final readonly class ChangeMedicationValidity implements MedicationValidityControl
{
    public function __construct(private MedicationValidityStore $store)
    {
    }

    public function changeValidity(string $id, Validity $validity): Medication
    {
        $current = $this->store->findById($id) ?? throw new MedicationNotFound('Medicamento no encontrado.');
        $changed = $current->changeValidity($validity);
        $this->store->updateValidity($id, $validity);

        return $changed;
    }
}
