<?php

declare(strict_types=1);

namespace MicroHis\CatalogoLab\Application;

use MicroHis\CatalogoLab\Domain\LabTest;

/** Puerto de persistencia que necesita la aplicación; lo implementa Persistence. */
interface LabTestStore
{
    public function latestVersionNo(string $code): int;
    public function hasActiveOverlap(string $code, string $validFrom): bool;
    /** Guarda la versión y su auditoría en una sola transacción. @throws PersistenceFailure */
    public function save(LabTest $test, string $actor): void;
    /** @return list<array<string, mixed>> */
    public function findActive(string $date, string $search): array;
}
