<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Persistence;

use MicroHis\QaCalidad\Domain\EvidenceRef;
use MicroHis\QaCalidad\Domain\GateDecision;
use MicroHis\QaCalidad\Domain\GateFinding;
use MicroHis\QaCalidad\Domain\GateVerdict;
use MicroHis\QaCalidad\Domain\PlannedSuite;
use MicroHis\QaCalidad\Domain\RunStatus;
use MicroHis\QaCalidad\Domain\SuiteKind;
use MicroHis\QaCalidad\Domain\SuiteResult;
use MicroHis\QaCalidad\Domain\TestEnvironment;
use MicroHis\QaCalidad\Domain\TestPlan;
use MicroHis\QaCalidad\Domain\TestRun;

/** Traducción fila ↔ objeto. Sin SQL: lo usa PdoTestRunRepository. */
final class RunRowMapper
{
    public function planJson(TestPlan $plan): string
    {
        return json_encode(array_map(
            static fn (PlannedSuite $s): array => ['name' => $s->name, 'kind' => $s->kind->value, 'required' => $s->required],
            $plan->suites,
        ), JSON_THROW_ON_ERROR);
    }

    public function evidenceJson(SuiteResult $result): string
    {
        return json_encode(array_map(
            static fn (EvidenceRef $e): array => ['kind' => $e->kind, 'path' => $e->path, 'sha256' => $e->sha256, 'bytes' => $e->bytes],
            $result->evidence,
        ), JSON_THROW_ON_ERROR);
    }

    /** @param list<array<string, mixed>> $resultRows */
    public function run(array $row, array $resultRows): TestRun
    {
        $suites = array_map(
            static fn (array $s): PlannedSuite => new PlannedSuite($s['name'], SuiteKind::from($s['kind']), $s['required']),
            json_decode((string) $row['plan_json'], true, 8, JSON_THROW_ON_ERROR),
        );

        return TestRun::restore(
            (string) $row['run_id'],
            (string) $row['tenant_id'],
            new TestPlan((string) $row['release_version'], (string) $row['commit_sha'], $suites),
            TestEnvironment::from((string) $row['environment']),
            (string) $row['created_by'],
            (string) $row['created_at'],
            RunStatus::from((string) $row['status']),
            $row['finished_at'] === null ? null : (string) $row['finished_at'],
            $row['abort_reason'] === null ? null : (string) $row['abort_reason'],
            array_map(fn (array $r): SuiteResult => $this->result($r), $resultRows),
        );
    }

    public function decision(array $row): GateDecision
    {
        return new GateDecision(
            (string) $row['run_id'],
            (string) $row['tenant_id'],
            GateVerdict::from((string) $row['verdict']),
            (string) $row['policy_version'],
            array_map(
                static fn (array $f): GateFinding => new GateFinding($f['rule'], $f['severity'], $f['suite'], $f['detail']),
                json_decode((string) $row['findings_json'], true, 8, JSON_THROW_ON_ERROR),
            ),
            (string) $row['evaluated_by'],
            (string) $row['evaluated_at'],
        );
    }

    private function result(array $row): SuiteResult
    {
        return new SuiteResult(
            (string) $row['suite'],
            (int) $row['total'],
            (int) $row['failed'],
            (int) $row['skipped'],
            (int) $row['duration_ms'],
            array_map(
                static fn (array $e): EvidenceRef => new EvidenceRef($e['kind'], $e['path'], $e['sha256'], $e['bytes']),
                json_decode((string) $row['evidence_json'], true, 8, JSON_THROW_ON_ERROR),
            ),
        );
    }
}
