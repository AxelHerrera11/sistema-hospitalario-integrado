<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Application;

use MicroHis\QaCalidad\Domain\GateDecision;
use MicroHis\QaCalidad\Domain\QualityGate;
use MicroHis\QaCalidad\Domain\QualityGatePolicy;
use MicroHis\QaCalidad\Domain\TestRunRepository;

/**
 * RF-05: aplicar el quality gate. Cada evaluación queda guardada y auditada;
 * reevaluar con otra política crea una decisión nueva, nunca reescribe la anterior.
 */
final class EvaluateQualityGate
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly RunLoader $loader,
        private readonly TestRunRepository $runs,
        private readonly QualityGate $gate,
        private readonly QualityGatePolicy $policy,
        private readonly AuditTrail $audit,
        private readonly Clock $clock,
    ) {}

    public function execute(Actor $actor, string $runId, string $traceId): GateDecision
    {
        $this->authorizer->require($actor, QaPermissions::EVALUATE, $runId, $traceId);
        $run = $this->loader->load($actor, $runId);
        $decision = $this->gate->evaluate($run, $this->policy, $actor->userId, $this->clock->now());
        $this->runs->saveDecision($decision);
        $this->audit->record($actor, 'qa.gate.evaluado', 'success', $run->id, $traceId, null, [
            'veredicto' => $decision->verdict->value,
            'politica' => $decision->policyVersion,
            'hallazgos' => count($decision->findings),
        ]);

        return $decision;
    }
}
