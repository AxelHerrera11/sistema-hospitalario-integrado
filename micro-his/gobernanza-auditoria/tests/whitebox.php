<?php

declare(strict_types=1);

use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\Gobernanza\Domain\AuditCriticality;
use MicroHis\Gobernanza\Domain\AuditFilter;
use MicroHis\Gobernanza\Domain\AuditOutcome;
use MicroHis\Gobernanza\Domain\AuditStorageFailure;
use MicroHis\Gobernanza\Persistence\AuditChainHasher;
use MicroHis\Gobernanza\Persistence\PdoAuditLog;

require __DIR__ . '/harness.php';

/**
 * Caja blanca: cada prueba recorre una rama concreta del código de
 * PdoAuditLog, AuditChainHasher o RecordAuditEvent (indicada en el nombre).
 */
$hasher = new AuditChainHasher();
$rows = static fn (PDO $pdo): array => $pdo->query('SELECT * FROM audit_log ORDER BY occurred_at')->fetchAll(PDO::FETCH_ASSOC);
$head = static fn (PDO $pdo): string => (string) $pdo->query('SELECT head_hash FROM audit_chain_state')->fetchColumn();

section('PdoAuditLog::append — rama "transacción propia" + cadena de hashes');
$pdo = sqlitePdo();
$rec = new RecordAuditEvent(new PdoAuditLog($pdo), new FixedClock(), new RecordingReporter());
foreach (['role_assigned', 'access_denied', 'role_revoked'] as $action) {
    $rec->record(ADMIN, H1, 'rbac', $action, AuditOutcome::Success, AuditCriticality::Security);
}
$chain = $rows($pdo);
$valid = $chain[0]['previous_hash'] === AuditChainHasher::GENESIS;
foreach ($chain as $i => $row) {
    $recomputed = $hasher->hash($row['previous_hash'], $row['event_id'], $row['occurred_at'], $row['event'], $row['actor_id'], $row['hospital_id'], json_decode($row['detail'], true), $row['trace_id'], $row['result']);
    $valid = $valid && $recomputed === $row['event_hash'] && ($i === 0 || $row['previous_hash'] === $chain[$i - 1]['event_hash']);
}
check('TC-W-01', 'cada fila enlaza con la anterior, el hash se puede recalcular y la cabeza avanza', $valid && $head($pdo) === end($chain)['event_hash']);
check('TC-W-02', 'una alteración del detalle rompe la verificación (evidencia de manipulación)', $hasher->hash($chain[1]['previous_hash'], $chain[1]['event_id'], $chain[1]['occurred_at'], $chain[1]['event'], $chain[1]['actor_id'], $chain[1]['hospital_id'], ['context' => ['x' => 1]], $chain[1]['trace_id'], $chain[1]['result']) !== $chain[1]['event_hash']);

section('Inmutabilidad (trigger append-only del esquema)');
check('TC-W-03', 'UPDATE y DELETE sobre audit_log son rechazados por la base', thrown(fn () => $pdo->exec("UPDATE audit_log SET result = 'failure'")) === PDOException::class && thrown(fn () => $pdo->exec('DELETE FROM audit_log')) === PDOException::class && count($rows($pdo)) === 3);
check('TC-W-04', 'el adaptador no ofrece métodos de modificación ni borrado', array_values(array_diff(get_class_methods(PdoAuditLog::class), ['__construct', 'append', 'search'])) === []);

section('PdoAuditLog::append — rama "transacción del consumidor"');
$pdo = sqlitePdo();
$rec = new RecordAuditEvent(new PdoAuditLog($pdo), new FixedClock(), new RecordingReporter());
$pdo->beginTransaction();
$rec->record(ADMIN, H1, 'rbac', 'role_assigned', AuditOutcome::Success, AuditCriticality::Security);
$stillOpen = $pdo->inTransaction();
$pdo->rollBack();
check('TC-W-05', 'participa en la transacción abierta: no hace commit y el ROLLBACK del consumidor revierte el evento y la cabeza', $stillOpen && count($rows($pdo)) === 0 && $head($pdo) === AuditChainHasher::GENESIS);

section('PdoAuditLog::append — ramas de error');
$pdo = sqlitePdo();
$pdo->exec('DELETE FROM audit_chain_state');
$reporter = new RecordingReporter();
$rec = new RecordAuditEvent(new PdoAuditLog($pdo), new FixedClock(), $reporter);
check('TC-W-06', 'sin estado de cadena -> AuditStorageFailure("audit_chain_missing") y ninguna fila', thrown(fn () => $rec->record(ADMIN, H1, 'rbac', 'x', AuditOutcome::Success, AuditCriticality::Security)) === AuditStorageFailure::class && $reporter->reports[0]['context']['cause'] === 'audit_chain_missing' && count($rows($pdo)) === 0);
$pdo = sqlitePdo();
$pdo->exec('DROP TABLE audit_log');
$reporter = new RecordingReporter();
$rec = new RecordAuditEvent(new PdoAuditLog($pdo), new FixedClock(), $reporter);
$caught = null;
try {
    $rec->record(ADMIN, H1, 'rbac', 'x', AuditOutcome::Success, AuditCriticality::Security);
} catch (AuditStorageFailure $e) {
    $caught = $e->getMessage();
}
check('TC-W-07', 'tabla caída -> mensaje genérico "audit_write_failed" sin SQL ni detalle del driver, rollback y cabeza intacta', $caught === 'audit_write_failed' && !$pdo->inTransaction() && $head($pdo) === AuditChainHasher::GENESIS);
check('TC-W-08', 'búsqueda con tabla caída -> AuditStorageFailure("audit_read_failed")', (function () use ($pdo): ?string {
    try {
        (new PdoAuditLog($pdo))->search(H1, new AuditFilter());
    } catch (AuditStorageFailure $e) {
        return $e->getMessage();
    }

    return null;
})() === 'audit_read_failed');
check('TC-W-09', 'driver no soportado -> AuditStorageFailure en el constructor', thrown(function (): void {
    $fake = new class ('sqlite::memory:') extends PDO {
        public function getAttribute(int $attribute): mixed
        {
            return $attribute === PDO::ATTR_DRIVER_NAME ? 'mysql' : parent::getAttribute($attribute);
        }
    };
    new PdoAuditLog($fake);
}) === AuditStorageFailure::class);

section('PdoAuditLog::where — ramas de filtros y parametrización');
$source = file_get_contents(__DIR__ . '/../src/Persistence/PdoAuditLog.php');
preg_match_all('/->(query|exec)\(/', $source, $raw);
check('TC-W-10', 'el adaptador solo usa prepare(): no hay query()/exec() con texto dinámico', $raw[0] === [] && substr_count($source, '->prepare(') >= 5);
$pdo = sqlitePdo();
$store = new PdoAuditLog($pdo);
$rec = new RecordAuditEvent($store, new FixedClock(), new RecordingReporter());
$rec->record(ADMIN, H1, 'rbac', 'role_assigned', AuditOutcome::Success, AuditCriticality::Security);
check('TC-W-11', 'valor malicioso en filtro se trata como dato literal (0 filas, tabla intacta)', $store->search(H1, new AuditFilter(actorId: "x' OR '1'='1"))->total === 0 && count($rows($pdo)) === 1);
check('TC-W-12', 'rama module: coincide por prefijo "rbac." y no por prefijo parcial "rba"', $store->search(H1, new AuditFilter(module: 'rbac'))->total === 1 && $store->search(H1, new AuditFilter(module: 'rba'))->total === 0);

section('Lectura de filas de otro productor (logRaw de Tenants)');
$pdo->prepare('INSERT INTO audit_log (event_id, occurred_at, event, actor_id, hospital_id, detail, trace_id, result) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
    ->execute(['5f0c8a52-1d3e-4b6a-9c7d-2e1f0a9b8c7d', '2026-10-15T20:00:00.000000Z', 'rbac.access_denied', MED, H1, json_encode(['user_id' => MED, 'permission' => 'usuarios.ver', 'reason' => 'permiso_faltante', 'trace_id' => 'trace-ccc333', 'password' => 'secreto']), 'trace-ccc333', null]);
$foreign = $store->search(H1, new AuditFilter(correlationId: 'trace-ccc333'))->events[0];
check('TC-W-13', 'fila sin result -> outcome "unknown" (no se inventa "denied") y sin criticidad', $foreign->outcome === AuditOutcome::Unknown && $foreign->criticality === null && !$foreign->outcome->occurred());
check('TC-W-14', 'el contexto de filas ajenas también se filtra al leer (password no se expone)', $foreign->context === ['permission' => 'usuarios.ver'] && in_array('password', $foreign->redactedKeys, true));

$pdo->prepare('INSERT INTO audit_log (event_id, occurred_at, event, actor_id, hospital_id, detail, trace_id, result) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
    ->execute(['6a1d9b63-2e4f-4c7b-8d8e-3f2a1b0c9d8e', '2026-10-15T21:00:00.000000Z', 'rbac.access_denied', MED, H1, json_encode(['resource' => ['type' => 'Tabla Ajena', 'id' => '<img src=x>']]), "trace con espacios\n", 'denied']);
$odd = $store->search(H1, new AuditFilter(outcome: AuditOutcome::Denied))->events[0];
check('TC-W-15', 'regresión H-GOB-09: campos ajenos con formato inválido no se exponen y se anotan en redacted_keys', $odd->resourceType === null && $odd->resourceId === null && $odd->correlationId === null && array_intersect(['resource_type', 'resource_id', 'correlation_id'], $odd->redactedKeys) === ['resource_type', 'resource_id', 'correlation_id']);

summary('whitebox');
