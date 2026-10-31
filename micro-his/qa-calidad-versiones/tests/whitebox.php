<?php

declare(strict_types=1);

/**
 * CAJA BLANCA de QualityGate::evaluate / suiteFindings / rateFindings y
 * GateDecision::verdictFor. Cada caso nombra la rama o condición que recorre.
 */
require __DIR__.'/harness.php';

use MicroHis\QaCalidad\Domain\EvidenceRef;
use MicroHis\QaCalidad\Domain\GateDecision;
use MicroHis\QaCalidad\Domain\GateFinding;
use MicroHis\QaCalidad\Domain\GateVerdict;
use MicroHis\QaCalidad\Domain\PlannedSuite;
use MicroHis\QaCalidad\Domain\QualityGate;
use MicroHis\QaCalidad\Domain\SuiteKind;
use MicroHis\QaCalidad\Domain\SuiteResult;
use MicroHis\QaCalidad\Domain\TestEnvironment;
use MicroHis\QaCalidad\Domain\TestPlan;
use MicroHis\QaCalidad\Domain\TestRun;

$ev = [new EvidenceRef('junit', 'r.xml', str_repeat('b', 64), 10)];
$plan = new TestPlan('1.4.0', SHA, [
    new PlannedSuite('unit', SuiteKind::Unit, true),
    new PlannedSuite('e2e', SuiteKind::E2e, true),
    new PlannedSuite('lint', SuiteKind::Lint, false),
]);

/** @param array<string, SuiteResult> $results */
$evaluate = static function (array $results, array $policy = [], string $finish = 'complete') use ($plan): GateDecision {
    $run = TestRun::plan('run-w', H1, $plan, TestEnvironment::Ci, LIDER, 'now');
    if ($finish !== 'none') {
        $run->start();
        foreach ($results as $r) {
            $run->record($r);
        }
        $finish === 'complete' ? $run->complete('now') : $run->abort('cancelado por el líder', 'now');
    }

    return (new QualityGate)->evaluate($run, policy($policy), 'evaluador', 'now');
};
$rules = static fn (GateDecision $d): array => array_map(static fn (GateFinding $f): string => "{$f->rule}:{$f->severity}", $d->findings);
$ok = static fn (string $s, int $total = 10) => new SuiteResult($s, $total, 0, 0, 1000, $ev);

section('evaluate(): rama por estado de la ejecución');
$d = $evaluate([], [], 'none');
check('W01', 'Estado planned → RUN_NOT_COMPLETED blocker → blocked', $d->verdict === GateVerdict::Blocked && $rules($d) === ['RUN_NOT_COMPLETED:blocker']);
$d = $evaluate([$ok('unit')], [], 'abort');
check('W02', 'Estado aborted → RUN_ABORTED (no evalúa suites)', $rules($d) === ['RUN_ABORTED:blocker']);

section('suiteFindings(): ausente / vacía / sin evidencia');
$d = $evaluate([$ok('unit'), $ok('e2e')]);
check('W03', 'Todas las obligatorias verdes y opcional ausente → approved con warning', $d->verdict === GateVerdict::Approved && $rules($d) === ['SUITE_MISSING:warning']);
$d = $evaluate([$ok('unit')]);
check('W04', 'Obligatoria ausente → SUITE_MISSING blocker', $d->verdict === GateVerdict::Blocked && in_array('SUITE_MISSING:blocker', $rules($d), true));
$d = $evaluate([$ok('unit'), new SuiteResult('e2e', 0, 0, 0, 0, $ev)]);
check('W05', 'executed()=0 por total 0 → SUITE_EMPTY blocker (vacío no es éxito)', in_array('SUITE_EMPTY:blocker', $rules($d), true));
$d = $evaluate([$ok('unit'), new SuiteResult('e2e', 3, 0, 3, 0, $ev)]);
check('W06', 'executed()=0 por todo omitido → SUITE_EMPTY blocker', in_array('SUITE_EMPTY:blocker', $rules($d), true));
$d = $evaluate([$ok('unit'), new SuiteResult('e2e', 5, 0, 0, 10, [])]);
check('W07', 'require_evidence=true y sin evidencia → EVIDENCE_MISSING blocker', $d->verdict === GateVerdict::Blocked && in_array('EVIDENCE_MISSING:blocker', $rules($d), true));
$d = $evaluate([$ok('unit'), new SuiteResult('e2e', 5, 0, 0, 10, [])], ['require_evidence' => false]);
check('W08', 'require_evidence=false → la misma entrada no genera hallazgo de evidencia', $d->verdict === GateVerdict::Approved);

section('rateFindings(): umbral, fallos tolerados y omitidas');
$d = $evaluate([new SuiteResult('unit', 20, 1, 0, 10, $ev), $ok('e2e')]);
check('W09', 'rate 0.95 < min 1.0 en obligatoria → SUITE_BELOW_THRESHOLD rejection', $d->verdict === GateVerdict::Rejected && $rules($d)[0] === 'SUITE_BELOW_THRESHOLD:rejection');
$d = $evaluate([new SuiteResult('unit', 20, 1, 0, 10, $ev), $ok('e2e')], ['min_pass_rate' => ['unit' => 0.95, 'e2e' => 1.0]]);
check('W10', 'Límite exacto rate 0.95 = min 0.95 → FAILURES_TOLERATED warning (no rechaza)', $d->verdict === GateVerdict::Approved && in_array('FAILURES_TOLERATED:warning', $rules($d), true));
$d = $evaluate([$ok('unit'), $ok('e2e'), new SuiteResult('lint', 4, 4, 0, 10, $ev)]);
check('W11', 'Opcional por debajo del umbral → warning, la versión se aprueba', $d->verdict === GateVerdict::Approved && in_array('SUITE_BELOW_THRESHOLD:warning', $rules($d), true));
$d = $evaluate([new SuiteResult('unit', 10, 0, 1, 10, $ev), $ok('e2e')]);
check('W12', 'Omitida en obligatoria y allow_skipped=false → SKIPPED_IN_REQUIRED rejection', $rules($d) === ['SKIPPED_IN_REQUIRED:rejection', 'SUITE_MISSING:warning']);
$d = $evaluate([new SuiteResult('unit', 10, 0, 1, 10, $ev), $ok('e2e')], ['allow_skipped_in_required' => true]);
check('W13', 'allow_skipped=true → la omitida no cuenta contra la tasa (9/9) → approved', $d->verdict === GateVerdict::Approved);

section('Duración E2E y precedencia del veredicto');
$d = $evaluate([$ok('unit'), new SuiteResult('e2e', 5, 0, 0, 60001, $ev)]);
check('W14', 'E2E 60001 ms > 60000 → E2E_SLOW warning (no bloquea)', $d->verdict === GateVerdict::Approved && in_array('E2E_SLOW:warning', $rules($d), true));
$d = $evaluate([$ok('unit'), new SuiteResult('e2e', 5, 0, 0, 60000, $ev)]);
check('W15', 'E2E 60000 ms = límite → sin E2E_SLOW', ! in_array('E2E_SLOW:warning', $rules($d), true));
$d = $evaluate([new SuiteResult('unit', 10, 3, 0, 10, $ev)]);
check('W16', 'rejection (unit) + blocker (e2e ausente) → blocked: falta evidencia para decidir', $d->verdict === GateVerdict::Blocked);
check('W17', 'verdictFor([]) → approved', GateDecision::verdictFor([]) === GateVerdict::Approved);
check('W18', 'verdictFor([warning]) → approved', GateDecision::verdictFor([new GateFinding('X', 'warning', null, '')]) === GateVerdict::Approved);

summary('whitebox');
