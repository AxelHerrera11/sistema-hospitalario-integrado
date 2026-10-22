<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Domain;

/**
 * Puerto de persistencia (Repository). Adaptadores: InMemoryTestRunRepository y
 * PdoTestRunRepository. Cualquier fallo técnico se traduce a QaStorageFailure.
 */
interface TestRunRepository
{
    public function nextIdentity(): string;

    public function save(TestRun $run): void;

    /** Busca solo dentro del tenant: una ejecución de otro hospital "no existe" (sin fuga). */
    public function find(string $tenantId, string $runId): ?TestRun;

    /** @return list<TestRun> más recientes primero */
    public function listByTenant(string $tenantId, int $limit): array;

    public function saveDecision(GateDecision $decision): void;

    public function latestDecision(string $tenantId, string $runId): ?GateDecision;
}
