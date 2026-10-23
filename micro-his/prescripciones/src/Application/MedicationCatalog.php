<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Application;

use MicroHis\Prescripciones\Domain\MedicationSnapshot;

/**
 * Puerto de consulta del catálogo de medicamentos.
 * Implementaciones: InMemory (tests), HTTP client + caché (producción).
 */
interface MedicationCatalog
{
    /**
     * Busca un medicamento por ID.
     *
     * @throws MedicationNotFound si no existe o está inactivo
     * @throws PersistenceFailure si falla la consulta
     */
    public function find(string $medicationId): MedicationSnapshot;
}
