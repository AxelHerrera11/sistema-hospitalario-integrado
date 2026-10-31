<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Persistence;

use MicroHis\ExpedienteMedico\Application\CareRelationship;

/** Doble InMemory del vínculo asistencial (solo pruebas): usuario => pacientes con asignación activa. */
final class InMemoryCareRelationship implements CareRelationship
{
    /** @param array<string, list<string>> $assignments */
    public function __construct(private readonly array $assignments = [])
    {
    }

    public function hasActiveAssignment(string $userRefId, string $patientId): bool
    {
        return in_array($patientId, $this->assignments[$userRefId] ?? [], true);
    }
}
