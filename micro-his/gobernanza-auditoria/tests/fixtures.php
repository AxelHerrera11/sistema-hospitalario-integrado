<?php

declare(strict_types=1);

use MicroHis\Gobernanza\Application\AccessDecision;
use MicroHis\Gobernanza\Application\AccessGate;
use MicroHis\Gobernanza\Application\Clock;
use MicroHis\Gobernanza\Application\FailureReporter;
use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\Gobernanza\Domain\AuditEvent;
use MicroHis\Gobernanza\Domain\AuditEventReader;
use MicroHis\Gobernanza\Domain\AuditFilter;
use MicroHis\Gobernanza\Domain\AuditLogger;
use MicroHis\Gobernanza\Domain\AuditPage;
use MicroHis\Gobernanza\Domain\AuditStorageFailure;
use MicroHis\Gobernanza\GobernanzaModule;
use MicroHis\Gobernanza\Persistence\PdoAuditLog;
use MicroHis\Gobernanza\Presentation\AuditHttpHandler;

/** Datos ficticios: identificadores sintéticos, sin personas ni pacientes reales. */
const H1 = '11111111-1111-4111-8111-111111111111';
const H2 = '22222222-2222-4222-8222-222222222222';
const ADMIN = 'aaaaaaaa-0000-4000-8000-000000000001';
const MED = 'aaaaaaaa-0000-4000-8000-000000000002';

/** Reloj controlable: avanza 1 segundo en cada lectura para ordenar eventos. */
final class FixedClock implements Clock
{
    public function __construct(private \DateTimeImmutable $now = new \DateTimeImmutable('2026-10-15T14:00:00Z'))
    {
    }

    public function now(): \DateTimeImmutable
    {
        $current = $this->now;
        $this->now = $this->now->modify('+1 second');

        return $current;
    }

    public function set(string $iso): void
    {
        $this->now = new \DateTimeImmutable($iso);
    }
}

final class RecordingReporter implements FailureReporter
{
    /** @var list<array{code: string, context: array}> */
    public array $reports = [];

    public function report(string $code, array $context = []): void
    {
        $this->reports[] = ['code' => $code, 'context' => $context];
    }

    public function codes(): array
    {
        return array_column($this->reports, 'code');
    }
}

/** Puerta de acceso configurable: permisos por "usuario@tenant". */
final class StaticGate implements AccessGate
{
    public int $calls = 0;

    /** @param array<string, list<string>> $grants */
    public function __construct(private array $grants = [], private ?string $forcedReason = null)
    {
    }

    public function decide(string $userId, string $tenantId, string $permission): AccessDecision
    {
        $this->calls++;
        if ($this->forcedReason !== null) {
            return new AccessDecision(false, $this->forcedReason, 'trace-forzado');
        }
        $allowed = in_array($permission, $this->grants["{$userId}@{$tenantId}"] ?? [], true);

        return new AccessDecision($allowed, $allowed ? null : 'permiso_faltante', $allowed ? null : 'trace-denegado');
    }
}

/** Almacén que siempre falla (simula base caída). */
final class BrokenStore implements AuditLogger, AuditEventReader
{
    public function append(AuditEvent $event): void
    {
        throw new AuditStorageFailure('audit_write_failed');
    }

    public function search(string $tenantId, AuditFilter $filter): AuditPage
    {
        throw new AuditStorageFailure('audit_read_failed');
    }
}

/** Lee de un almacén y escribe en otro (p. ej. lectura sana, escritura caída). */
final class SplitStore implements AuditLogger, AuditEventReader
{
    public function __construct(private AuditEventReader $reader, private AuditLogger $logger)
    {
    }

    public function append(AuditEvent $event): void
    {
        $this->logger->append($event);
    }

    public function search(string $tenantId, AuditFilter $filter): AuditPage
    {
        return $this->reader->search($tenantId, $filter);
    }
}

function sqlitePdo(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec(file_get_contents(__DIR__ . '/../database/audit_log.sqlite.sql'));

    return $pdo;
}

function pdoStore(?PDO $pdo = null): PdoAuditLog
{
    return new PdoAuditLog($pdo ?? sqlitePdo());
}

/**
 * Compone el módulo completo sobre un almacén dado.
 *
 * @return array{http: AuditHttpHandler, recorder: RecordAuditEvent, clock: FixedClock, reporter: RecordingReporter, gate: StaticGate}
 */
function compose(AuditLogger&AuditEventReader $store, ?StaticGate $gate = null, ?AuditLogger $writer = null): array
{
    $clock = new FixedClock();
    $reporter = new RecordingReporter();
    $gate ??= new StaticGate([ADMIN . '@' . H1 => ['auditoria.ver'], ADMIN . '@' . H2 => ['auditoria.ver']]);
    $module = GobernanzaModule::compose($writer ?? $store, $store, $gate, $clock, $reporter, 'America/Guatemala');

    return [
        'http' => $module->http,
        'recorder' => $module->recorder,
        'clock' => $clock,
        'reporter' => $reporter,
        'gate' => $gate,
    ];
}

/** Cabeceras y contexto de servidor de un usuario autenticado en un tenant. */
function as_user(string $user, string $tenant, ?string $header = null): array
{
    return [['user_id' => $user, 'tenant_id' => $tenant], ['X-Tenant-ID' => $header ?? $tenant, 'X-Request-ID' => 'req-prueba-01']];
}
