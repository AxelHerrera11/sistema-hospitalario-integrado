<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Application;

use MicroHis\MedicationCatalog\Domain\Medication;
use MicroHis\MedicationCatalog\Domain\MedicationRuleViolation;

final readonly class ConsultMedications implements MedicationLookup
{
    public const RESULT_LIMIT = 50;

    public function __construct(private MedicationReader $reader)
    {
    }

    public function search(string $term): array
    {
        $term = trim($term);
        $length = mb_strlen($term);
        if ($length < 2 || $length > 60) {
            throw new MedicationRuleViolation('term', 'La búsqueda admite de 2 a 60 caracteres.');
        }

        return $this->reader->search($term, self::RESULT_LIMIT);
    }

    public function findById(string $id): ?Medication
    {
        return $this->reader->findById($id);
    }
}
