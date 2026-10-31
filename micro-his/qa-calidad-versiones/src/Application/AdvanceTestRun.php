<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Application;

use MicroHis\QaCalidad\Domain\InvalidQaInput;
use MicroHis\QaCalidad\Domain\TestRun;
use MicroHis\QaCalidad\Domain\TestRunRepository;

/** RF-02: transiciones start | complete | abort de una ejecución. */
final class AdvanceTestRun
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly RunLoader $loader,
        private readonly TestRunRepository $runs,
        private readonly AuditTrail $audit,
        private readonly Clock $clock,
    ) {}

    public function execute(Actor $actor, string $runId, string $transition, ?string $reason, string $traceId): TestRun
    {
        $this->authorizer->require($actor, QaPermissions::REGISTER, $runId, $traceId);
        $run = $this->loader->load($actor, $runId);
        match ($transition) {
            'start' => $run->start(),
            'complete' => $run->complete($this->clock->now()),
            'abort' => $run->abort((string) $reason, $this->clock->now()),
            default => throw new InvalidQaInput('transicion_invalida', 'La transición debe ser start, complete o abort.'),
        };
        $this->runs->save($run);
        $this->audit->record(
            $actor, "qa.ejecucion.{$transition}", 'success', $run->id, $traceId,
            $transition === 'abort' ? 'ejecucion_abortada' : null,
            $transition === 'abort' ? ['motivo' => $run->abortReason()] : ['estado' => $run->status()->value],
        );

        return $run;
    }
}
