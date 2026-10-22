<?php

declare(strict_types=1);

/** Pruebas UNITARIAS de reglas de dominio (RF-01..RF-04, RNF-02, RNF-03). */
require __DIR__.'/harness.php';

use MicroHis\QaCalidad\Domain\EvidenceRef;
use MicroHis\QaCalidad\Domain\InvalidQaInput;
use MicroHis\QaCalidad\Domain\PlannedSuite;
use MicroHis\QaCalidad\Domain\QualityGatePolicy;
use MicroHis\QaCalidad\Domain\RunStateConflict;
use MicroHis\QaCalidad\Domain\RunStatus;
use MicroHis\QaCalidad\Domain\SuiteKind;
use MicroHis\QaCalidad\Domain\SuiteResult;
use MicroHis\QaCalidad\Domain\TestEnvironment;
use MicroHis\QaCalidad\Domain\TestPlan;
use MicroHis\QaCalidad\Domain\TestRun;

$suites = [new PlannedSuite('unit', SuiteKind::Unit, true), new PlannedSuite('e2e', SuiteKind::E2e, true)];
$plan = static fn (?array $s = null, string $v = '1.4.0', string $sha = SHA): TestPlan => new TestPlan($v, $sha, $s ?? $suites);

section('Plan de pruebas (RF-01)');
check('U01', 'Versión SemVer válida y con prerelease', $plan(null, '1.4.0-rc.1')->releaseVersion === '1.4.0-rc.1');
check('U02', 'Versión no SemVer → version_invalida', throwsReason(fn () => $plan(null, 'v1.4'), InvalidQaInput::class, 'version_invalida'));
check('U03', 'Commit abreviado (7) → commit_invalido', throwsReason(fn () => $plan(null, '1.4.0', '4f2c9a1'), InvalidQaInput::class, 'commit_invalido'));
check('U04', 'Nombres de suite repetidos → suite_duplicada', throwsReason(
    fn () => $plan([...$suites, new PlannedSuite('unit', SuiteKind::Lint, false)]),
    InvalidQaInput::class,
    'suite_duplicada',
));
check('U05', 'Plan sin E2E obligatoria → plan_incompleto', throwsReason(
    fn () => $plan([new PlannedSuite('unit', SuiteKind::Unit, true), new PlannedSuite('e2e', SuiteKind::E2e, false)]),
    InvalidQaInput::class,
    'plan_incompleto',
));
check('U06', 'Ambiente "production" no existe → ambiente_no_permitido (RNF-02)', throwsReason(fn () => TestEnvironment::parse('production'), InvalidQaInput::class, 'ambiente_no_permitido'));
check('U07', 'Nombre de suite con mayúsculas/espacios → nombre_suite_invalido', throwsReason(fn () => new PlannedSuite('Mi Suite', SuiteKind::Unit, true), InvalidQaInput::class, 'nombre_suite_invalido'));

section('Evidencia (RF-04, RNF-03)');
$content = '<testsuites><testsuite name="x"/></testsuites>';
$ref = new EvidenceRef('junit', 'phpunit/unit.xml', hash('sha256', $content), strlen($content));
check('U08', 'La evidencia reconoce su contenido original', $ref->matches($content));
check('U09', 'Un byte alterado invalida la evidencia', ! $ref->matches(str_replace('x', 'y', $content)));
foreach (['../secretos.txt' => 'escapa con ..', '/etc/passwd' => 'ruta absoluta', 'C:/x.xml' => 'unidad Windows', 'https://evil.test/a' => 'URL'] as $path => $why) {
    check('U10', "Ruta de evidencia rechazada: {$why}", throwsReason(fn () => new EvidenceRef('junit', $path, str_repeat('a', 64), 1), InvalidQaInput::class, 'evidencia_ruta_invalida'));
}
check('U11', 'SHA-256 en mayúsculas o corto → evidencia_sin_huella', throwsReason(fn () => new EvidenceRef('log', 'a.log', strtoupper(str_repeat('a', 64)), 1), InvalidQaInput::class, 'evidencia_sin_huella'));
check('U12', 'Evidencia de 0 bytes → evidencia_vacia', throwsReason(fn () => new EvidenceRef('log', 'a.log', str_repeat('a', 64), 0), InvalidQaInput::class, 'evidencia_vacia'));

section('Resultado de suite');
check('U13', 'Fallidas + omitidas > total → conteo_inconsistente', throwsReason(fn () => new SuiteResult('unit', 3, 2, 2, 10), InvalidQaInput::class, 'conteo_inconsistente'));
check('U14', 'Duración negativa → conteo_negativo', throwsReason(fn () => new SuiteResult('unit', 3, 0, 0, -1), InvalidQaInput::class, 'conteo_negativo'));
$r = new SuiteResult('unit', 10, 1, 2, 10);
check('U15', 'Tasa sobre ejecutadas: 7 de 8 = 0.875', abs($r->passRate() - 0.875) < 1e-9 && $r->passed() === 7);
check('U16', 'Todo omitido → tasa null (no hay qué medir)', (new SuiteResult('unit', 4, 0, 4, 10))->passRate() === null);

section('Ciclo de vida de la ejecución (RF-02, RF-03)');
$run = TestRun::plan('run-1', H1, $plan(), TestEnvironment::Ci, LIDER, '2026-10-21T15:00:00Z');
check('U17', 'Registrar antes de iniciar → estado_invalido (409)', throwsReason(fn () => $run->record(new SuiteResult('unit', 1, 0, 0, 1)), RunStateConflict::class, 'estado_invalido'));
$run->start();
check('U18', 'Suite que no está en el plan → suite_no_planificada', throwsReason(fn () => $run->record(new SuiteResult('otra', 1, 0, 0, 1)), InvalidQaInput::class, 'suite_no_planificada'));
$run->record(new SuiteResult('unit', 5, 0, 0, 30));
check('U19', 'Segundo resultado de la misma suite → resultado_duplicado (no se sobrescribe)', throwsReason(fn () => $run->record(new SuiteResult('unit', 5, 5, 0, 30)), RunStateConflict::class, 'resultado_duplicado') && $run->result('unit')->failed === 0);
check('U20', 'Iniciar dos veces → estado_invalido', throwsReason(fn () => $run->start(), RunStateConflict::class, 'estado_invalido'));
$run->complete('2026-10-21T15:10:00Z');
check('U21', 'Completar fija estado y hora de cierre', $run->status() === RunStatus::Completed && $run->finishedAt() === '2026-10-21T15:10:00Z');
check('U22', 'Abortar una ejecución completada → ejecucion_finalizada', throwsReason(fn () => $run->abort('x', 'now'), RunStateConflict::class, 'ejecucion_finalizada'));
$other = TestRun::plan('run-2', H1, $plan(), TestEnvironment::Ci, LIDER, 'now');
check('U23', 'Abortar sin motivo → motivo_invalido', throwsReason(fn () => $other->abort('   ', 'now'), InvalidQaInput::class, 'motivo_invalido'));
$other->abort('Runner sin espacio en disco', 'now');
check('U24', 'Abortar desde planned guarda el motivo', $other->status() === RunStatus::Aborted && $other->abortReason() === 'Runner sin espacio en disco');

section('Política del gate');
check('U25', 'Umbral 1.5 → umbral_invalido', throwsReason(fn () => QualityGatePolicy::fromArray(['version' => 'x', 'min_pass_rate' => ['unit' => 1.5]]), InvalidQaInput::class, 'umbral_invalido'));
check('U26', 'Umbral para tipo inexistente → tipo_suite_invalido', throwsReason(fn () => QualityGatePolicy::fromArray(['version' => 'x', 'min_pass_rate' => ['smoke' => 1]]), InvalidQaInput::class, 'tipo_suite_invalido'));
check('U27', 'Política sin versión → politica_version_invalida', throwsReason(fn () => QualityGatePolicy::fromArray([]), InvalidQaInput::class, 'politica_version_invalida'));
check('U28', 'Tipo sin umbral explícito usa 1.0 (lo más estricto)', policy()->minPassRateFor(SuiteKind::Contract) === 1.0);

summary('unit');
