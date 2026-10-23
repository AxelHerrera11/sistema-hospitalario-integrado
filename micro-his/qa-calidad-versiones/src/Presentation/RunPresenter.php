<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Presentation;

use MicroHis\QaCalidad\Domain\EvidenceRef;
use MicroHis\QaCalidad\Domain\GateDecision;
use MicroHis\QaCalidad\Domain\GateFinding;
use MicroHis\QaCalidad\Domain\PlannedSuite;
use MicroHis\QaCalidad\Domain\TestRun;

/** Vista JSON (snake_case) de ejecuciones y decisiones. Sin lógica de negocio. */
final class RunPresenter
{
    public function run(TestRun $run, ?GateDecision $decision = null): array
    {
        return [
            'id' => $run->id,
            'tenant_id' => $run->tenantId,
            'release_version' => $run->plan->releaseVersion,
            'commit_sha' => $run->plan->commitSha,
            'environment' => $run->environment->value,
            'status' => $run->status()->value,
            'created_by' => $run->createdBy,
            'created_at' => $run->createdAt,
            'finished_at' => $run->finishedAt(),
            'abort_reason' => $run->abortReason(),
            'suites' => array_map(fn (PlannedSuite $s): array => $this->suite($run, $s), $run->plan->suites),
            'gate' => $decision === null ? null : $this->decision($decision),
        ];
    }

    public function decision(GateDecision $decision): array
    {
        return [
            'run_id' => $decision->runId,
            'verdict' => $decision->verdict->value,
            'policy_version' => $decision->policyVersion,
            'evaluated_by' => $decision->evaluatedBy,
            'evaluated_at' => $decision->evaluatedAt,
            'findings' => array_map(static fn (GateFinding $f): array => $f->toArray(), $decision->findings),
        ];
    }

    private function suite(TestRun $run, PlannedSuite $planned): array
    {
        $result = $run->result($planned->name);

        return [
            'name' => $planned->name,
            'kind' => $planned->kind->value,
            'required' => $planned->required,
            'result' => $result === null ? null : [
                'total' => $result->total,
                'passed' => $result->passed(),
                'failed' => $result->failed,
                'skipped' => $result->skipped,
                'duration_ms' => $result->durationMs,
                'evidence' => array_map(
                    static fn (EvidenceRef $e): array => ['kind' => $e->kind, 'path' => $e->path, 'sha256' => $e->sha256, 'bytes' => $e->bytes],
                    $result->evidence,
                ),
            ],
        ];
    }
}
