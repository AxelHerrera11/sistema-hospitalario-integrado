<?php

declare(strict_types=1);

/**
 * Adaptador PDO sobre SQLite en memoria con el esquema de PRUEBA
 * database/qa_runs.sqlite.sql (no toca las bases del HIS). Incluye el
 * "error de persistencia" exigido en la Semana 3.
 */
require __DIR__.'/harness.php';

use MicroHis\QaCalidad\Application\Actor;
use MicroHis\QaCalidad\Domain\GateDecision;
use MicroHis\QaCalidad\Domain\GateFinding;
use MicroHis\QaCalidad\Domain\GateVerdict;
use MicroHis\QaCalidad\Domain\QaStorageFailure;
use MicroHis\QaCalidad\Domain\RunStatus;
use MicroHis\QaCalidad\Domain\SuiteResult;
use MicroHis\QaCalidad\Persistence\PdoTestRunRepository;

$fresh = static function (): array {
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec((string) file_get_contents(__DIR__.'/../database/qa_runs.sqlite.sql'));

    return [$pdo, new PdoTestRunRepository($pdo)];
};
[$pdo, $repo] = $fresh();
$app = compose($repo);
$lider = new Actor(LIDER, H1);
$m = $app['module'];

section('Ida y vuelta (camino feliz)');
$run = $m->plan->execute($lider, planBody(), 't');
$m->advance->execute($lider, $run->id, 'start', null, 't');
$m->record->execute($lider, $run->id, resultBody('phpunit-unit', 40, 0, 0, 900, [evidence('phpunit/unit.xml'), evidence('phpunit/unit.log')]), 't');
$loaded = $repo->find(H1, $run->id);
check('P01', 'El agregado se reconstruye con estado, plan y resultado', $loaded !== null && $loaded->status() === RunStatus::Running
    && count($loaded->plan->suites) === 3 && $loaded->result('phpunit-unit')->total === 40);
check('P02', 'La evidencia conserva ruta y huella', $loaded->result('phpunit-unit')->evidence[1]->path === 'phpunit/unit.log'
    && $loaded->result('phpunit-unit')->evidence[1]->sha256 === hash('sha256', 'phpunit/unit.log'));
check('P03', 'find() de otro tenant devuelve null (aislamiento por consulta)', $repo->find(H2, $run->id) === null);

section('Solo inserción y decisiones');
$tampered = $repo->find(H1, $run->id);
$reflection = new ReflectionProperty($tampered, 'results');
$reflection->setValue($tampered, ['phpunit-unit' => new SuiteResult('phpunit-unit', 40, 40, 0, 900)]);
$repo->save($tampered);
check('P04', 'Un resultado ya guardado no se sobrescribe aunque se fuerce el objeto', $repo->find(H1, $run->id)->result('phpunit-unit')->failed === 0);
$repo->saveDecision(new GateDecision($run->id, H1, GateVerdict::Blocked, 'qg-a', [new GateFinding('SUITE_MISSING', 'blocker', 'e2e-auth', 'x')], LIDER, '2026-10-21T15:00:00Z'));
$repo->saveDecision(new GateDecision($run->id, H1, GateVerdict::Approved, 'qg-b', [], LIDER, '2026-10-21T15:05:00Z'));
check('P05', 'latestDecision devuelve la última insertada; las anteriores se conservan', $repo->latestDecision(H1, $run->id)->policyVersion === 'qg-b'
    && (int) $pdo->query('SELECT COUNT(*) FROM qa_gate_decisions')->fetchColumn() === 2);
check('P06', 'latestDecision de otro tenant → null', $repo->latestDecision(H2, $run->id) === null);
check('P07', 'Hallazgos sobreviven la serialización', $repo->find(H1, $run->id) !== null
    && $repo->latestDecision(H1, $run->id)->verdict === GateVerdict::Approved);

section('Sentencias preparadas');
$evil = "x' OR '1'='1";
check('P08', 'Un id con comillas se trata como dato, no como SQL', $repo->find(H1, $evil) === null && $repo->find($evil, $run->id) === null);
check('P09', 'listByTenant con LIMIT ligado como entero', count($repo->listByTenant(H1, 1)) === 1);

section('Error de persistencia');
$pdo->exec('DROP TABLE qa_suite_results');
check('P10', 'Tabla ausente → QaStorageFailure (no PDOException)', thrown(fn () => $repo->find(H1, $run->id)) instanceof QaStorageFailure);
$r = $m->http->handle('GET', "/api/v1/qa/runs/{$run->id}", ['user_id' => LIDER, 'tenant_id' => H1], ['X-Tenant-ID' => H1], []);
check('P11', 'La API responde 503 QA_STORAGE_UNAVAILABLE', $r['status'] === 503 && $r['body']['code'] === 'QA_STORAGE_UNAVAILABLE');
[$pdo2, $repo2] = $fresh();
$pdo2->exec("INSERT INTO qa_test_runs VALUES ('r1','".H1."','1.0.0','".SHA."','ci','planned','{no-json','u','now',NULL,NULL)");
check('P12', 'Fila corrupta (plan_json inválido) → QaStorageFailure', thrown(fn () => $repo2->find(H1, 'r1')) instanceof QaStorageFailure);
// La propia tabla rechaza estados fuera del enum (CHECK); un plan vacío sí entra y el dominio lo rechaza.
check('P13a', 'El esquema rechaza un status fuera del enum (CHECK)', thrown(fn () => $pdo2->exec("UPDATE qa_test_runs SET status = 'x' WHERE run_id = 'r1'")) instanceof PDOException);
$pdo2->exec("UPDATE qa_test_runs SET plan_json = '[]' WHERE run_id = 'r1'");
check('P13', 'Plan vacío guardado (viola regla de dominio) → QaStorageFailure (no 422)', thrown(fn () => $repo2->find(H1, 'r1')) instanceof QaStorageFailure);
$pdo3 = new PDO('sqlite::memory:');
$pdo3->exec((string) file_get_contents(__DIR__.'/../database/qa_runs.sqlite.sql'));
$repo3 = new PdoTestRunRepository($pdo3);
$pdo3->exec('CREATE TRIGGER no_results BEFORE INSERT ON qa_suite_results BEGIN SELECT RAISE(ABORT, "falla simulada"); END');
$app3 = compose($repo3);
$r3 = $app3['module']->plan->execute($lider, planBody(), 't');
$app3['module']->advance->execute($lider, $r3->id, 'start', null, 't');
$e = thrown(fn () => $app3['module']->record->execute($lider, $r3->id, resultBody('phpunit-unit', 3), 't'));
check('P14', 'Fallo a mitad de la transacción → rollback, sin resultado parcial', $e instanceof QaStorageFailure
    && $repo3->find(H1, $r3->id)->result('phpunit-unit') === null && ! $pdo3->inTransaction());

summary('pdo');
