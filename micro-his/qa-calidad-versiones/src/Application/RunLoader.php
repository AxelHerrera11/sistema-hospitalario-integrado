<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Application;

use MicroHis\QaCalidad\Domain\TestRun;
use MicroHis\QaCalidad\Domain\TestRunRepository;

/** Carga una ejecución del tenant del actor o lanza RunNotFound. */
final class RunLoader
{
    public function __construct(private readonly TestRunRepository $runs) {}

    public function load(Actor $actor, string $runId): TestRun
    {
        $run = preg_match('/^[A-Za-z0-9-]{1,64}$/', $runId) === 1 ? $this->runs->find($actor->tenantId, $runId) : null;
        if ($run === null) {
            throw new RunNotFound("Ejecución {$runId} no encontrada.");
        }

        return $run;
    }
}
