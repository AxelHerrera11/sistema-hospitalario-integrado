<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Presentation;

use MicroHis\MedicationCatalog\Application\MedicationLookup;
use MicroHis\MedicationCatalog\Application\MedicationNotFound;
use MicroHis\MedicationCatalog\Application\PersistenceFailure;
use MicroHis\MedicationCatalog\Domain\MedicationRuleViolation;

/** Cliente de solo lectura: búsqueda y consulta de vigencia. */
final readonly class SearchController
{
    public function __construct(private MedicationLookup $lookup)
    {
    }

    /** @return array{status: int, body: array} */
    public function search(string $term): array
    {
        try {
            $results = array_map(CatalogResponse::medication(...), $this->lookup->search($term));

            return ['status' => 200, 'body' => ['results' => $results, 'count' => count($results)]];
        } catch (MedicationRuleViolation|PersistenceFailure $error) {
            return CatalogResponse::failure($error);
        }
    }

    /** @return array{status: int, body: array} */
    public function validity(string $id): array
    {
        try {
            $medication = $this->lookup->findById($id) ?? throw new MedicationNotFound('Medicamento no encontrado.');

            return ['status' => 200, 'body' => ['id' => $medication->id, 'code' => $medication->code, 'validity' => $medication->validity->value]];
        } catch (MedicationNotFound|PersistenceFailure $error) {
            return CatalogResponse::failure($error);
        }
    }
}
