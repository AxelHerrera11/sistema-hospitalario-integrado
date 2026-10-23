<?php

declare(strict_types=1);

/** Casos de uso, auditoría e integración con Gobernanza y Auditoría (RF-05, RF-06, RNF-04). */
require __DIR__.'/harness.php';

use MicroHis\Gobernanza\Application\Clock;
use MicroHis\Gobernanza\Application\FailureReporter;
use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\Gobernanza\Domain\AuditEvent;
use MicroHis\Gobernanza\Domain\AuditFilter;
use MicroHis\Gobernanza\Domain\AuditLogger;
use MicroHis\Gobernanza\Domain\AuditStorageFailure;
use MicroHis\Gobernanza\Persistence\InMemoryAuditLog;
use MicroHis\QaCalidad\Application\AccessDenied;
use MicroHis\QaCalidad\Application\Actor;
use MicroHis\QaCalidad\Application\RunNotFound;
use MicroHis\QaCalidad\Domain\GateVerdict;
use MicroHis\QaCalidad\Domain\QaStorageFailure;
use MicroHis\QaCalidad\Integration\GobernanzaAuditTrail;
use MicroHis\QaCalidad\Integration\StaticAccessGate;
use MicroHis\QaCalidad\QaModule;

$lider = new Actor(LIDER, H1);
$obs = new Actor(OBS, H1);
$flow = static function (array $app, Actor $actor, int $failedUnit = 0): string {
    $m = $app['module'];
    $run = $m->plan->execute($actor, planBody(), 't-1');
    $m->advance->execute($actor, $run->id, 'start', null, 't-1');
    $m->record->execute($actor, $run->id, resultBody('phpunit-unit', 50, $failedUnit), 't-1');
    $m->record->execute($actor, $run->id, resultBody('e2e-auth', 8), 't-1');
    $m->advance->execute($actor, $run->id, 'complete', null, 't-1');

    return $run->id;
};

section('Flujo y auditoría (RF-05)');
$app = compose();
$id = $flow($app, $lider);
$decision = $app['module']->evaluate->execute($lider, $id, 't-1');
check('A01', 'Flujo completo verde → approved y persistido', $decision->verdict === GateVerdict::Approved && $app['repo']->latestDecision(H1, $id) !== null);
check('A02', 'Cada paso deja un evento de auditoría en orden', $app['audit']->actions() === [
    'qa.ejecucion.planificada', 'qa.ejecucion.start', 'qa.resultado.registrado',
    'qa.resultado.registrado', 'qa.ejecucion.complete', 'qa.gate.evaluado',
]);
check('A03', 'El evento del gate guarda veredicto y versión de política', end($app['audit']->events)['context'] === ['veredicto' => 'approved', 'politica' => 'qg-test', 'hallazgos' => 1]);
$app['clock']->set('2026-10-21T16:00:00Z');
$app['module']->evaluate->execute($lider, $id, 't-2');
check('A04', 'Reevaluar agrega una decisión nueva (no reescribe la anterior)', $app['repo']->latestDecision(H1, $id)->evaluatedAt === '2026-10-21T16:00:00Z');

section('Permisos (RNF-04)');
$e = thrown(fn () => $app['module']->evaluate->execute($obs, $id, 't-3'));
check('A05', 'Observador no puede evaluar el gate → AccessDenied', $e instanceof AccessDenied && $e->permission === 'qa.gates.evaluar');
$last = end($app['audit']->events);
check('A06', 'La denegación queda auditada con el permiso faltante', $last['action'] === 'qa.acceso.denegado' && $last['outcome'] === 'denied' && $last['context'] === ['permiso' => 'qa.gates.evaluar']);
$brokenAudit = compose(null, null, new RecordingAuditTrail(true));
check('A07', 'Si la auditoría falla, la denegación se mantiene (403, nunca 200)', thrown(fn () => $brokenAudit['module']->view->list($obs, 5, 't')) === null
    && thrown(fn () => $brokenAudit['module']->plan->execute($obs, planBody(), 't')) instanceof AccessDenied);
check('A08', 'Otro tenant no encuentra la ejecución → RunNotFound', thrown(fn () => $app['module']->view->get(new Actor(LIDER, H2), $id, 't')) instanceof RunNotFound);
check('A09', 'Id con caracteres no válidos → RunNotFound sin consultar', thrown(fn () => $app['module']->view->get($lider, "x' OR '1'='1", 't')) instanceof RunNotFound);
check('A10', 'Límite del listado acotado a 50', count($app['module']->view->list($lider, 999, 't')) === 1);

section('Errores de persistencia y auditoría');
$broken = compose(new BrokenRepository);
check('A11', 'Almacén caído al planificar → QaStorageFailure y sin auditoría de éxito', thrown(fn () => $broken['module']->plan->execute($lider, planBody(), 't')) instanceof QaStorageFailure && $broken['audit']->events === []);
$failing = compose(null, null, new RecordingAuditTrail(true));
check('A12', 'Auditoría caída después de guardar → la operación informa QaStorageFailure', thrown(fn () => $failing['module']->plan->execute($lider, planBody(), 't')) instanceof QaStorageFailure);

section('Integración real con Gobernanza (RecordAuditEvent)');
$log = new InMemoryAuditLog;
$clock = new class implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-21T15:00:00Z');
    }
};
$reports = [];
$reporter = new class($reports) implements FailureReporter
{
    public function __construct(private array &$reports) {}

    public function report(string $code, array $context = []): void
    {
        $this->reports[] = $code;
    }
};
$gov = compose(null, null, null);
$real = QaModule::compose($gov['repo'], new StaticAccessGate(grants()), new GobernanzaAuditTrail(new RecordAuditEvent($log, $clock, $reporter)), $gov['clock'], policy());
$id2 = $flow(['module' => $real], $lider);
$real->evaluate->execute($lider, $id2, 'trace-qa-0001');
$page = $log->search(H1, new AuditFilter(module: 'qa_calidad', perPage: 50));
check('A13', 'Los 6 eventos llegan a Gobernanza con módulo qa_calidad, sin rechazos', $log->count() === 6 && $page->total === 6 && $reports === []);
check('A14', 'Gobernanza no expone eventos QA de H1 a H2', $log->search(H2, new AuditFilter(module: 'qa_calidad', perPage: 50))->total === 0);
$failingLog = new class implements AuditLogger
{
    public function append(AuditEvent $event): void
    {
        throw new AuditStorageFailure('caído');
    }
};
$trail = new GobernanzaAuditTrail(new RecordAuditEvent($failingLog, $clock, $reporter));
check('A15', 'Evento Security (gate) con Gobernanza caída → QaStorageFailure', thrown(fn () => $trail->record($lider, 'qa.gate.evaluado', 'success', $id2, 'trace-x')) instanceof QaStorageFailure);
check('A16', 'Evento Informational con Gobernanza caída → se reporta y no detiene', thrown(fn () => $trail->record($lider, 'qa.resultado.registrado', 'success', $id2, 'trace-x')) === null && in_array('audit_record_failed', $reports, true));

summary('application');
