<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Application;

use MicroHis\QaCalidad\Domain\TestRun;
use MicroHis\QaCalidad\Domain\TestRunRepository;

/** RF-01: planificar una ejecución automatizada para una versión y un commit. */
final class PlanTestRun
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly RunInputParser $parser,
        private readonly TestRunRepository $runs,
        private readonly AuditTrail $audit,
        private readonly Clock $clock,
    ) {}

    public function execute(Actor $actor, array $body, string $traceId): TestRun
    {
        $this->authorizer->require($actor, QaPermissions::REGISTER, 'qa_test_run', $traceId);
        [$plan, $environment] = $this->parser->plan($body);
        $run = TestRun::plan($this->runs->nextIdentity(), $actor->tenantId, $plan, $environment, $actor->userId, $this->clock->now());
        $this->runs->save($run);
        $this->audit->record($actor, 'qa.ejecucion.planificada', 'success', $run->id, $traceId, null, [
            'version' => $plan->releaseVersion,
            'ambiente' => $environment->value,
            'suites' => count($plan->suites),
        ]);

        return $run;
    }
}
