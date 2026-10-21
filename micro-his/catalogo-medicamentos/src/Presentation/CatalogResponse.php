<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Presentation;

use MicroHis\MedicationCatalog\Application\DuplicateMedicationCode;
use MicroHis\MedicationCatalog\Application\MedicationNotFound;
use MicroHis\MedicationCatalog\Application\PersistenceFailure;
use MicroHis\MedicationCatalog\Domain\Medication;
use MicroHis\MedicationCatalog\Domain\MedicationRuleViolation;

/** Traduce resultados y errores esperados a {status, body}; nunca informa éxito ante un fallo. */
final class CatalogResponse
{
    public static function medication(Medication $medication): array
    {
        return [
            'id' => $medication->id,
            'code' => $medication->code,
            'name' => $medication->name,
            'active_ingredient' => $medication->activeIngredient,
            'pharmacological_group' => $medication->pharmacologicalGroup,
            'validity' => $medication->validity->value,
        ];
    }

    /** @return array{status: int, body: array} */
    public static function failure(MedicationRuleViolation|DuplicateMedicationCode|MedicationNotFound|PersistenceFailure $error): array
    {
        return match (true) {
            $error instanceof MedicationRuleViolation => ['status' => 422, 'body' => ['error' => 'MEDICATION_INVALID', 'field' => $error->field, 'message' => $error->getMessage()]],
            $error instanceof DuplicateMedicationCode => ['status' => 409, 'body' => ['error' => 'MEDICATION_CODE_DUPLICATED', 'message' => $error->getMessage()]],
            $error instanceof MedicationNotFound => ['status' => 404, 'body' => ['error' => 'MEDICATION_NOT_FOUND', 'message' => $error->getMessage()]],
            default => ['status' => 503, 'body' => ['error' => 'CATALOG_UNAVAILABLE', 'message' => 'El catálogo no está disponible; no se confirmó la operación.']],
        };
    }
}
