<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Application;

use MicroHis\QaCalidad\Domain\GateDecision;
use MicroHis\QaCalidad\Domain\TestRun;
use MicroHis\QaCalidad\Domain\TestRunRepository;

/** RF-06: consultar ejecuciones y su última decisión, solo del tenant del actor. */
final class ViewTestRuns
{
    public const MAX_LIMIT = 50;

    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly RunLoader $loader,
        private readonly TestRunRepository $runs,
    ) {}

    /** @return array{run: TestRun, decision: ?GateDecision} */
    public function get(Actor $actor, string $runId, string $traceId): array
    {
        $this->authorizer->require($actor, QaPermissions::VIEW, $runId, $traceId);
        $run = $this->loader->load($actor, $runId);

        return ['run' => $run, 'decision' => $this->runs->latestDecision($actor->tenantId, $run->id)];
    }

    /** @return list<array{run: TestRun, decision: ?GateDecision}> */
    public function list(Actor $actor, int $limit, string $traceId): array
    {
        $this->authorizer->require($actor, QaPermissions::VIEW, 'qa_test_run', $traceId);
        $limit = max(1, min(self::MAX_LIMIT, $limit));

        return array_map(
            fn (TestRun $run): array => ['run' => $run, 'decision' => $this->runs->latestDecision($actor->tenantId, $run->id)],
            $this->runs->listByTenant($actor->tenantId, $limit),
        );
    }
}
