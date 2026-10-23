<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Persistence;

use MicroHis\QaCalidad\Domain\GateDecision;
use MicroHis\QaCalidad\Domain\GateFinding;
use MicroHis\QaCalidad\Domain\InvalidQaInput;
use MicroHis\QaCalidad\Domain\QaStorageFailure;
use MicroHis\QaCalidad\Domain\TestRun;
use MicroHis\QaCalidad\Domain\TestRunRepository;

/**
 * Adaptador PDO con sentencias preparadas. Probado sobre SQLite con el esquema de
 * PRUEBA database/qa_runs.sqlite.sql: NO es una migración del HIS (ver la propuesta
 * para Javier en docs/CI-E2E/semana-04). Los resultados son de solo inserción.
 */
final class PdoTestRunRepository implements TestRunRepository
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly RunRowMapper $mapper = new RunRowMapper,
    ) {
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function nextIdentity(): string
    {
        return Uuid::v4();
    }

    public function save(TestRun $run): void
    {
        $this->guard(function () use ($run): void {
            $this->pdo->beginTransaction();
            try {
                $this->pdo->prepare(
                    'INSERT INTO qa_test_runs (run_id, tenant_id, release_version, commit_sha, environment, status, plan_json, created_by, created_at, finished_at, abort_reason)
                     VALUES (:id, :tenant, :version, :sha, :env, :status, :plan, :by, :at, :finished, :reason)
                     ON CONFLICT (run_id) DO UPDATE SET status = excluded.status, finished_at = excluded.finished_at, abort_reason = excluded.abort_reason',
                )->execute([
                    'id' => $run->id, 'tenant' => $run->tenantId, 'version' => $run->plan->releaseVersion,
                    'sha' => $run->plan->commitSha, 'env' => $run->environment->value, 'status' => $run->status()->value,
                    'plan' => $this->mapper->planJson($run->plan), 'by' => $run->createdBy, 'at' => $run->createdAt,
                    'finished' => $run->finishedAt(), 'reason' => $run->abortReason(),
                ]);
                $insert = $this->pdo->prepare(
                    'INSERT INTO qa_suite_results (run_id, suite, total, failed, skipped, duration_ms, evidence_json)
                     VALUES (:id, :suite, :total, :failed, :skipped, :ms, :evidence) ON CONFLICT (run_id, suite) DO NOTHING',
                );
                foreach ($run->results() as $r) {
                    $insert->execute([
                        'id' => $run->id, 'suite' => $r->suite, 'total' => $r->total, 'failed' => $r->failed,
                        'skipped' => $r->skipped, 'ms' => $r->durationMs, 'evidence' => $this->mapper->evidenceJson($r),
                    ]);
                }
                $this->pdo->commit();
            } catch (\Throwable $e) {
                $this->pdo->inTransaction() && $this->pdo->rollBack();
                throw $e;
            }
        });
    }

    public function find(string $tenantId, string $runId): ?TestRun
    {
        return $this->guard(function () use ($tenantId, $runId): ?TestRun {
            $stmt = $this->pdo->prepare('SELECT * FROM qa_test_runs WHERE tenant_id = :tenant AND run_id = :id');
            $stmt->execute(['tenant' => $tenantId, 'id' => $runId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $row === false ? null : $this->mapper->run($row, $this->resultRows($runId));
        });
    }

    public function listByTenant(string $tenantId, int $limit): array
    {
        return $this->guard(function () use ($tenantId, $limit): array {
            $stmt = $this->pdo->prepare('SELECT * FROM qa_test_runs WHERE tenant_id = :tenant ORDER BY created_at DESC, run_id DESC LIMIT :limit');
            $stmt->bindValue('tenant', $tenantId);
            $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();

            return array_map(
                fn (array $row): TestRun => $this->mapper->run($row, $this->resultRows((string) $row['run_id'])),
                $stmt->fetchAll(\PDO::FETCH_ASSOC),
            );
        });
    }

    public function saveDecision(GateDecision $decision): void
    {
        $this->guard(fn () => $this->pdo->prepare(
            'INSERT INTO qa_gate_decisions (run_id, tenant_id, verdict, policy_version, findings_json, evaluated_by, evaluated_at)
             VALUES (:run, :tenant, :verdict, :policy, :findings, :by, :at)',
        )->execute([
            'run' => $decision->runId, 'tenant' => $decision->tenantId, 'verdict' => $decision->verdict->value,
            'policy' => $decision->policyVersion, 'by' => $decision->evaluatedBy, 'at' => $decision->evaluatedAt,
            'findings' => json_encode(array_map(static fn (GateFinding $f): array => $f->toArray(), $decision->findings), JSON_THROW_ON_ERROR),
        ]));
    }

    public function latestDecision(string $tenantId, string $runId): ?GateDecision
    {
        return $this->guard(function () use ($tenantId, $runId): ?GateDecision {
            $stmt = $this->pdo->prepare('SELECT * FROM qa_gate_decisions WHERE tenant_id = :tenant AND run_id = :run ORDER BY decision_id DESC LIMIT 1');
            $stmt->execute(['tenant' => $tenantId, 'run' => $runId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $row === false ? null : $this->mapper->decision($row);
        });
    }

    /** @return list<array<string, mixed>> */
    private function resultRows(string $runId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM qa_suite_results WHERE run_id = :id ORDER BY suite');
        $stmt->execute(['id' => $runId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Traduce cualquier error técnico a QaStorageFailure: el caso de uso no ve PDOException.
     * Una fila que ya no cumple las reglas del dominio (ValueError, InvalidQaInput) también
     * es un fallo de integridad del almacén, no un error del cliente.
     */
    private function guard(callable $operation): mixed
    {
        try {
            return $operation();
        } catch (\PDOException|\JsonException|\ValueError|InvalidQaInput $e) {
            throw new QaStorageFailure('El almacén de ejecuciones QA no está disponible.', 0, $e);
        }
    }
}
