<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Ci;

use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\Gobernanza\Integration\ErrorLogFailureReporter;
use MicroHis\Gobernanza\Integration\SystemClock as GobernanzaClock;
use MicroHis\Gobernanza\Persistence\InMemoryAuditLog;
use MicroHis\QaCalidad\Domain\QualityGatePolicy;
use MicroHis\QaCalidad\Domain\TestRunRepository;
use MicroHis\QaCalidad\Integration\GobernanzaAuditTrail;
use MicroHis\QaCalidad\Integration\StaticAccessGate;
use MicroHis\QaCalidad\Integration\SystemClock;
use MicroHis\QaCalidad\Persistence\InMemoryTestRunRepository;
use MicroHis\QaCalidad\Persistence\PdoTestRunRepository;
use MicroHis\QaCalidad\QaModule;

/**
 * Cableado para procesos fuera de Laravel (CLI de CI y servidor de demostración):
 * auditoría real de Gobernanza en memoria y almacén InMemory o SQLite (archivo).
 */
final class ModuleFactory
{
    public static function repository(?string $sqlitePath): TestRunRepository
    {
        if ($sqlitePath === null) {
            return new InMemoryTestRunRepository;
        }
        $pdo = new \PDO('sqlite:'.$sqlitePath);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec((string) file_get_contents(dirname(__DIR__, 2).'/database/qa_runs.sqlite.sql'));

        return new PdoTestRunRepository($pdo);
    }

    public static function policy(string $file): QualityGatePolicy
    {
        $data = json_decode((string) @file_get_contents($file), true);
        if (! is_array($data)) {
            throw new \RuntimeException("Política ilegible: {$file}");
        }

        return QualityGatePolicy::fromArray($data);
    }

    /** @param array<string, list<string>> $grants "usuario@tenant" => permisos */
    public static function build(TestRunRepository $runs, array $grants, QualityGatePolicy $policy, bool $rbacDown = false): QaModule
    {
        $reporter = new ErrorLogFailureReporter;
        $audit = new GobernanzaAuditTrail(new RecordAuditEvent(new InMemoryAuditLog, new GobernanzaClock, $reporter));

        return QaModule::compose(
            $runs,
            new StaticAccessGate($grants, $rbacDown),
            $audit,
            new SystemClock,
            $policy,
            static fn (string $code, array $context) => $reporter->report($code, $context),
        );
    }
}
