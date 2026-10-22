<?php

declare(strict_types=1);

namespace MicroHis\Alergias\Application;

use MicroHis\Alergias\Domain\Allergy;

/** Puerto de persistencia que necesita la aplicación; lo implementa Persistence. */
interface AllergyStore
{
    public function patientExists(string $patientId): bool;
    /** Guarda la alergia y su auditoría en una sola transacción. @throws PersistenceFailure */
    public function save(Allergy $allergy, string $actor): void;
    /** @return list<array<string, mixed>> @throws PersistenceFailure */
    public function activeFor(string $patientId): array;
}
