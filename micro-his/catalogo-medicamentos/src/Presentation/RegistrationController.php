<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Presentation;

use MicroHis\MedicationCatalog\Application\DuplicateMedicationCode;
use MicroHis\MedicationCatalog\Application\MedicationRegistration;
use MicroHis\MedicationCatalog\Application\PersistenceFailure;
use MicroHis\MedicationCatalog\Domain\MedicationRuleViolation;

final readonly class RegistrationController
{
    public function __construct(private MedicationRegistration $registration)
    {
    }

    /** @return array{status: int, body: array} */
    public function register(array $input): array
    {
        try {
            $medication = $this->registration->register($input);

            return ['status' => 201, 'body' => ['message' => 'Medicamento registrado', 'medication' => CatalogResponse::medication($medication)]];
        } catch (MedicationRuleViolation|DuplicateMedicationCode|PersistenceFailure $error) {
            return CatalogResponse::failure($error);
        }
    }
}
