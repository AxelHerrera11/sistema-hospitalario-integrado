<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Persistence;

use MicroHis\QaCalidad\Domain\GateDecision;
use MicroHis\QaCalidad\Domain\TestRun;
use MicroHis\QaCalidad\Domain\TestRunRepository;

/**
 * Adaptador en memoria para pruebas, CI y demo. Guarda copias: modificar un
 * objeto devuelto no altera lo almacenado hasta llamar save(), igual que con PDO.
 */
final class InMemoryTestRunRepository implements TestRunRepository
{
    /** @var array<string, TestRun> clave "tenant|run" */
    private array $runs = [];

    /** @var array<string, list<GateDecision>> */
    private array $decisions = [];

    public function nextIdentity(): string
    {
        return Uuid::v4();
    }

    public function save(TestRun $run): void
    {
        $this->runs[$run->tenantId.'|'.$run->id] = clone $run;
    }

    public function find(string $tenantId, string $runId): ?TestRun
    {
        $run = $this->runs[$tenantId.'|'.$runId] ?? null;

        return $run === null ? null : clone $run;
    }

    public function listByTenant(string $tenantId, int $limit): array
    {
        $runs = array_values(array_filter($this->runs, static fn (TestRun $r): bool => $r->tenantId === $tenantId));
        usort($runs, static fn (TestRun $a, TestRun $b): int => [$b->createdAt, $b->id] <=> [$a->createdAt, $a->id]);

        return array_map(static fn (TestRun $r): TestRun => clone $r, array_slice($runs, 0, $limit));
    }

    public function saveDecision(GateDecision $decision): void
    {
        $this->decisions[$decision->tenantId.'|'.$decision->runId][] = $decision;
    }

    public function latestDecision(string $tenantId, string $runId): ?GateDecision
    {
        $all = $this->decisions[$tenantId.'|'.$runId] ?? [];

        return $all === [] ? null : $all[array_key_last($all)];
    }
}
