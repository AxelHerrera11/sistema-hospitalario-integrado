<?php

declare(strict_types=1);

namespace MicroHis\Admision\Application;

use MicroHis\Admision\Domain\Admission;

/** Puerto de persistencia que necesita la aplicación; lo implementa Persistence. */
interface AdmissionRepository
{
    public function patientExists(string $patientId): bool;
    public function hasOpenInpatientEpisode(string $patientId): bool;
    /** @return array{bed_id: string, type: string, status: string}|null */
    public function bed(string $bedId): ?array;
    /** Episodio, estancia, cama ocupada y auditoría en una sola transacción. @throws PersistenceFailure */
    public function save(Admission $admission, string $actor): void;
    /** @return list<array<string, mixed>> */
    public function availableBeds(string $type): array;
}
