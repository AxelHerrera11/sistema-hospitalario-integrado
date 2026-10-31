<?php

declare(strict_types=1);

/**
 * Dobles de prueba compartidos por las suites y por el servidor de demostración.
 * Datos ficticios: no representan usuarios ni hospitales reales.
 */
use MicroHis\QaCalidad\Application\AccessGate;
use MicroHis\QaCalidad\Application\Actor;
use MicroHis\QaCalidad\Application\AuditTrail;
use MicroHis\QaCalidad\Application\Clock;
use MicroHis\QaCalidad\Application\QaPermissions;
use MicroHis\QaCalidad\Domain\GateDecision;
use MicroHis\QaCalidad\Domain\QaStorageFailure;
use MicroHis\QaCalidad\Domain\QualityGatePolicy;
use MicroHis\QaCalidad\Domain\TestRun;
use MicroHis\QaCalidad\Domain\TestRunRepository;
use MicroHis\QaCalidad\Integration\StaticAccessGate;
use MicroHis\QaCalidad\Persistence\InMemoryTestRunRepository;
use MicroHis\QaCalidad\QaModule;

const H1 = 'hospital-pruebas-1';
const H2 = 'hospital-pruebas-2';
const LIDER = 'qa-lider';
const OBS = 'observador';
const SHA = '4f2c9a1be07d3c6a8e5b9d0f1a2b3c4d5e6f7a8b';

final class FixedClock implements Clock
{
    public function __construct(private string $now = '2026-10-21T15:00:00Z') {}

    public function now(): string
    {
        return $this->now;
    }

    public function set(string $now): void
    {
        $this->now = $now;
    }
}

final class RecordingAuditTrail implements AuditTrail
{
    /** @var list<array<string, mixed>> */
    public array $events = [];

    public function __construct(private readonly bool $broken = false) {}

    public function record(Actor $actor, string $action, string $outcome, string $targetId, string $traceId, ?string $reason = null, array $context = []): void
    {
        if ($this->broken) {
            throw new QaStorageFailure('auditoría caída (doble)');
        }
        $this->events[] = compact('action', 'outcome', 'targetId', 'reason', 'context') + ['user' => $actor->userId, 'tenant' => $actor->tenantId];
    }

    public function actions(): array
    {
        return array_column($this->events, 'action');
    }
}

/** Repositorio que falla en todas las operaciones (simula caída del almacén). */
final class BrokenRepository implements TestRunRepository
{
    public function nextIdentity(): string
    {
        return 'run-roto';
    }

    public function save(TestRun $run): void
    {
        throw new QaStorageFailure('almacén caído (doble)');
    }

    public function find(string $tenantId, string $runId): ?TestRun
    {
        throw new QaStorageFailure('almacén caído (doble)');
    }

    public function listByTenant(string $tenantId, int $limit): array
    {
        throw new QaStorageFailure('almacén caído (doble)');
    }

    public function saveDecision(GateDecision $decision): void
    {
        throw new QaStorageFailure('almacén caído (doble)');
    }

    public function latestDecision(string $tenantId, string $runId): ?GateDecision
    {
        throw new QaStorageFailure('almacén caído (doble)');
    }
}

function policy(array $override = []): QualityGatePolicy
{
    return QualityGatePolicy::fromArray($override + [
        'version' => 'qg-test',
        'min_pass_rate' => ['unit' => 1.0, 'e2e' => 1.0, 'integration' => 1.0],
        'require_evidence' => true,
        'allow_skipped_in_required' => false,
        'max_e2e_duration_ms' => 60000,
    ]);
}

/** Permisos ficticios por "usuario@tenant". */
function grants(): array
{
    $all = [QaPermissions::VIEW, QaPermissions::REGISTER, QaPermissions::EVALUATE];

    return [LIDER.'@'.H1 => $all, OBS.'@'.H1 => [QaPermissions::VIEW], LIDER.'@'.H2 => $all];
}

/** @return array{module: QaModule, repo: TestRunRepository, audit: RecordingAuditTrail, clock: FixedClock} */
function compose(?TestRunRepository $repo = null, ?AccessGate $gate = null, ?RecordingAuditTrail $audit = null, ?QualityGatePolicy $policy = null): array
{
    $repo ??= new InMemoryTestRunRepository;
    $audit ??= new RecordingAuditTrail;
    $clock = new FixedClock;
    $module = QaModule::compose($repo, $gate ?? new StaticAccessGate(grants()), $audit, $clock, $policy ?? policy());

    return compact('module', 'repo', 'audit', 'clock');
}

function planBody(array $override = []): array
{
    return $override + [
        'release_version' => '1.4.0-rc.1',
        'commit_sha' => SHA,
        'environment' => 'ci',
        'suites' => [
            ['name' => 'phpunit-unit', 'kind' => 'unit', 'required' => true],
            ['name' => 'e2e-auth', 'kind' => 'e2e', 'required' => true],
            ['name' => 'pint', 'kind' => 'lint', 'required' => false],
        ],
    ];
}

function evidence(string $path = 'phpunit/unit.xml'): array
{
    return ['kind' => 'junit', 'path' => $path, 'sha256' => hash('sha256', $path), 'bytes' => 120];
}

function resultBody(string $suite, int $total, int $failed = 0, int $skipped = 0, int $ms = 1200, ?array $evidence = null): array
{
    return ['suite' => $suite, 'total' => $total, 'failed' => $failed, 'skipped' => $skipped, 'duration_ms' => $ms, 'evidence' => $evidence ?? [evidence("{$suite}.xml")]];
}
