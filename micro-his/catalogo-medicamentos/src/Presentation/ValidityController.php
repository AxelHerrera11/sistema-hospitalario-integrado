<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Presentation;

use MicroHis\MedicationCatalog\Application\MedicationNotFound;
use MicroHis\MedicationCatalog\Application\MedicationValidityControl;
use MicroHis\MedicationCatalog\Application\PersistenceFailure;
use MicroHis\MedicationCatalog\Domain\MedicationRuleViolation;
use MicroHis\MedicationCatalog\Domain\Validity;

final readonly class ValidityController
{
    public function __construct(private MedicationValidityControl $control)
    {
    }

    /** @return array{status: int, body: array} */
    public function change(string $id, string $validity): array
    {
        try {
            $medication = $this->control->changeValidity($id, Validity::fromInput($validity));

            return ['status' => 200, 'body' => ['message' => 'Vigencia actualizada', 'medication' => CatalogResponse::medication($medication)]];
        } catch (MedicationRuleViolation|MedicationNotFound|PersistenceFailure $error) {
            return CatalogResponse::failure($error);
        }
    }
}
