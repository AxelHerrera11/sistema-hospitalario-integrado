<?php

declare(strict_types=1);

/**
 * Ejemplo ejecutable de OCP (Semana 2):
 *   php docs/gobernanza-auditoria/semana-02/ejemplo/ocp-antes-despues.php
 *
 * ANTES: un servicio que conoce cada almacén y cada política con if/switch.
 * DESPUÉS: el caso de uso real RecordAuditEvent depende de AuditLogger; un
 * almacén nuevo se agrega con una clase nueva, sin tocar el caso de uso.
 */
require __DIR__ . '/../../../../micro-his/gobernanza-auditoria/bootstrap.php';

use MicroHis\Gobernanza\Application\FailureReporter;
use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\Gobernanza\Domain\AuditCriticality;
use MicroHis\Gobernanza\Domain\AuditEvent;
use MicroHis\Gobernanza\Domain\AuditLogger;
use MicroHis\Gobernanza\Domain\AuditOutcome;
use MicroHis\Gobernanza\Integration\SystemClock;
use MicroHis\Gobernanza\Persistence\InMemoryAuditLog;
use MicroHis\Gobernanza\Persistence\PdoAuditLog;

// ---------------------------------------------------------------- ANTES
final class AuditServiceAntes
{
    public array $memory = [];

    public function __construct(private string $storage, private ?PDO $pdo = null)
    {
    }

    public function log(string $user, string $tenant, string $event, string $result, bool $mandatory): void
    {
        switch ($this->storage) {           // cada almacén nuevo obliga a editar esta clase
            case 'memory':
                $this->memory[] = compact('user', 'tenant', 'event', 'result');
                break;
            case 'pdo':
                // SQL concatenado y mezclado con la regla de negocio (lo que se evita después)
                $this->pdo->exec("INSERT INTO audit_log (event, actor_id) VALUES ('{$event}', '{$user}')");
                break;
            default:
                if ($mandatory) {           // la política de fallo también vive aquí
                    throw new RuntimeException('almacén desconocido');
                }
        }
    }
}

// ---------------------------------------------------------------- DESPUÉS
/** Extensión nueva: solo una clase, RecordAuditEvent no cambia. */
final class StdoutAuditLogger implements AuditLogger
{
    public function append(AuditEvent $event): void
    {
        echo "  [stdout] {$event->eventName()} {$event->outcome->value} actor={$event->actorId} tenant={$event->tenantId}" . PHP_EOL;
    }
}

$reporter = new class implements FailureReporter {
    public function report(string $code, array $context = []): void
    {
        echo "  [reporte] {$code}" . PHP_EOL;
    }
};

$pdo = new PDO('sqlite::memory:');
$pdo->exec(file_get_contents(__DIR__ . '/../../../../micro-his/gobernanza-auditoria/database/audit_log.sqlite.sql'));

echo 'ANTES: agregar "stdout" exige modificar el switch de AuditServiceAntes.' . PHP_EOL;
(new AuditServiceAntes('memory'))->log('u1', 'h1', 'rbac.role_assigned', 'success', true);
echo '  memoria: OK (con SQL concatenado en el caso "pdo", vulnerable a inyección)' . PHP_EOL . PHP_EOL;

echo 'DESPUÉS: el mismo RecordAuditEvent con tres AuditLogger distintos:' . PHP_EOL;
foreach (['InMemoryAuditLog' => new InMemoryAuditLog(), 'PdoAuditLog' => new PdoAuditLog($pdo), 'StdoutAuditLogger (nuevo)' => new StdoutAuditLogger()] as $name => $logger) {
    $ok = (new RecordAuditEvent($logger, new SystemClock(), $reporter))
        ->record('aaaaaaaa-0000-4000-8000-000000000001', '11111111-1111-4111-8111-111111111111', 'rbac', 'role_assigned', AuditOutcome::Success, AuditCriticality::Security);
    echo "  {$name}: " . ($ok ? 'registrado' : 'no registrado') . PHP_EOL;
}
echo '  filas en audit_log (SQLite): ' . $pdo->query('SELECT COUNT(*) FROM audit_log')->fetchColumn() . PHP_EOL;
