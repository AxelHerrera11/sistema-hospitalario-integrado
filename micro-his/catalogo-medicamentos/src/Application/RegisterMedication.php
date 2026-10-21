<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Application;

use MicroHis\MedicationCatalog\Domain\Medication;

final readonly class RegisterMedication implements MedicationRegistration
{
    public function __construct(private MedicationRegistry $registry)
    {
    }

    public function register(array $draft): Medication
    {
        $medication = Medication::register($draft);
        if ($this->registry->findByCode($medication->code) !== null) {
            throw new DuplicateMedicationCode("El código {$medication->code} ya está registrado.");
        }

        return $this->registry->insert($medication);
    }
}
