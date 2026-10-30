<?php

declare(strict_types=1);

use MicroHis\ExpedienteMedico\Application\AccessAuthorizer;
use MicroHis\ExpedienteMedico\Application\AccessDecision;
use MicroHis\ExpedienteMedico\Application\AccessGuard;
use MicroHis\ExpedienteMedico\Application\ConsultLongitudinalRecord;
use MicroHis\ExpedienteMedico\Application\ExpedientePermissions;
use MicroHis\ExpedienteMedico\Application\HospitalStores;
use MicroHis\ExpedienteMedico\Application\OpenMedicalRecord;
use MicroHis\ExpedienteMedico\Application\PersistenceFailure;
use MicroHis\ExpedienteMedico\Application\RequestContext;
use MicroHis\ExpedienteMedico\Application\AuditUnavailable;
use MicroHis\ExpedienteMedico\Domain\ExpedienteRuleViolation;
use MicroHis\ExpedienteMedico\Domain\MedicalRecord;
use MicroHis\ExpedienteMedico\Persistence\HospitalRegistry;

/**
 * Pruebas de CAJA BLANCA: una por rama/condición de ConsultLongitudinalRecord (C1..C7) y OpenMedicalRecord (A1..A7).
 * El autorizador es un doble que permite/deniega a voluntad para aislar cada rama del motor RBAC.
 */
echo PHP_EOL . '== Caja blanca (ramas de los casos de uso)' . PHP_EOL;

final class StubAuthorizer implements AccessAuthorizer
{
    public array $asked = [];

    public function __construct(private readonly bool $allow)
    {
    }

    public function decide(string $userId, string $hospitalId, string $permissionName): AccessDecision
    {
        $this->asked[] = $permissionName;

        return new AccessDecision($this->allow, $this->allow ? null : 'permiso_faltante', 'trace-stub');
    }
}

/** Ejecuta el caso de uso y devuelve «estado CÓDIGO», «OK» o el nombre de la excepción técnica. */
function outcome(callable $action): string
{
    try {
        $action();

        return 'OK';
    } catch (ExpedienteRuleViolation $e) {
        return $e->status . ' ' . $e->getMessage();
    } catch (AuditUnavailable|PersistenceFailure $e) {
        return (new \ReflectionClass($e))->getShortName();
    }
}

/** @return array{0: ConsultLongitudinalRecord, 1: OpenMedicalRecord, 2: RecordingAuditLogger, 3: StubAuthorizer, 4: HospitalStores} */
function cases(bool $allow = true, bool $auditDown = false, bool $hospital = true, bool $storeDown = false, ?MedicalRecord $racer = null): array
{
    $authorizer = new StubAuthorizer($allow);
    $logger = new RecordingAuditLogger($auditDown);
    $audit = expediente_audit($logger, new SilentReporter());
    $stores = memory_stores($storeDown, $racer);
    $registry = new HospitalRegistry($hospital ? [HOSPITAL => static fn (): HospitalStores => $stores] : []);
    $guard = new AccessGuard($authorizer, $audit);

    return [new ConsultLongitudinalRecord($guard, $registry, $audit), new OpenMedicalRecord($guard, $registry, $audit, new FixedClock()), $logger, $authorizer, $stores];
}

$ctx = static fn (string $actor = DRA_LOPEZ): RequestContext => new RequestContext($actor, HOSPITAL, 'corr-1');

// --- Consulta: cada condición del flujo «consulta autorizada» ---
[$consult, , $log, $auth] = cases(allow: false);
check('C1 RBAC niega → 403 ACCESO_DENEGADO y se audita la denegación', outcome(fn () => $consult->execute($ctx(), 'P-0001')) === '403 ACCESO_DENEGADO'
    && $log->names() === ['expediente_medico.acceso_denegado:denied'] && $auth->asked === [ExpedientePermissions::CONSULTA]
    && $log->events[0]->context === ['permiso' => ExpedientePermissions::CONSULTA, 'rbac_trace' => 'trace-stub']);

[$consult] = cases(hospital: false);
check('C2 tenant sin almacenes configurados → 503 HOSPITAL_NO_DISPONIBLE', outcome(fn () => $consult->execute($ctx(), 'P-0001')) === '503 HOSPITAL_NO_DISPONIBLE');

[$consult, , $log] = cases();
check('C3 paciente inexistente → 403 SIN_VINCULO_ASISTENCIAL (no revela existencia)', outcome(fn () => $consult->execute($ctx(), 'P-9999')) === '403 SIN_VINCULO_ASISTENCIAL'
    && $log->names() === ['expediente_medico.acceso_denegado:denied']);

[$consult] = cases();
check('C4 paciente existente sin vínculo del usuario → 403 SIN_VINCULO_ASISTENCIAL', outcome(fn () => $consult->execute($ctx(DR_PEREZ), 'P-0001')) === '403 SIN_VINCULO_ASISTENCIAL');

[$consult] = cases();
check('C5 vínculo sí, expediente no abierto → 404 EXPEDIENTE_NO_ABIERTO', outcome(fn () => $consult->execute($ctx(), 'P-0002')) === '404 EXPEDIENTE_NO_ABIERTO');

[$consult, , $log] = cases(auditDown: true);
check('C6 bitácora caída en acceso exitoso → AuditUnavailable (no se entregan datos)', outcome(fn () => $consult->execute($ctx(), 'P-0001')) === 'AuditUnavailable');

[$consult] = cases(storeDown: true);
check('C6b almacén del hospital caído → PersistenceFailure', outcome(fn () => $consult->execute($ctx(), 'P-0001')) === 'PersistenceFailure');

[$consult, , $log] = cases();
$view = $consult->execute($ctx(), 'P-0001');
$event = $log->events[0] ?? null;
check('C7 camino feliz: 3 episodios, evento «consultado» Security con el id del expediente y sin datos clínicos',
    count($view->episodes) === 3 && $log->names() === ['expediente_medico.consultado:success'] && $event?->resourceId === $view->record->id
    && $event?->criticality?->value === 'security' && $event?->context === ['episodios' => 3] && $event?->correlationId === 'corr-1');

[$consult, , $log] = cases();
$central = new RequestContext('c1111111-1111-4111-8111-111111111111', HOSPITAL, null, DRA_LOPEZ);
$view = $consult->execute($central, 'P-0001');
check('C8 identidad CENTRAL distinta de la referencia local: el vínculo usa la local y la auditoría la CENTRAL',
    count($view->episodes) === 3 && $log->events[0]->actorId === 'c1111111-1111-4111-8111-111111111111'
    && outcome(fn () => $consult->execute(new RequestContext(DRA_LOPEZ, HOSPITAL, null, DR_PEREZ), 'P-0001')) === '403 SIN_VINCULO_ASISTENCIAL');

// --- Apertura: cada condición del flujo «apertura» ---
[, $open, , $auth] = cases(allow: false);
check('A1 RBAC niega el permiso de apertura → 403', outcome(fn () => $open->execute($ctx(RECEP_MORA), 'P-0002')) === '403 ACCESO_DENEGADO'
    && $auth->asked === [ExpedientePermissions::APERTURA_PROPUESTA]);

[, $open] = cases();
check('A2 paciente inexistente → 404 PACIENTE_NO_ENCONTRADO', outcome(fn () => $open->execute($ctx(RECEP_MORA), 'P-9999')) === '404 PACIENTE_NO_ENCONTRADO');

[, $open, $log] = cases();
$again = $open->execute($ctx(RECEP_MORA), 'P-0001');
check('A3 expediente ya existente → created=false, mismo expediente y sin evento nuevo', $again['created'] === false
    && $again['record']->recordNumber === 'EXP-P-0001' && $log->events === []);

[, $open] = cases();
check('A4 paciente fallecido → 409 PACIENTE_NO_ACTIVO', outcome(fn () => $open->execute($ctx(RECEP_MORA), 'P-0003')) === '409 PACIENTE_NO_ACTIVO');

[, $open, $log, , $stores] = cases(auditDown: true);
check('A5 bitácora caída al abrir → AuditUnavailable y el expediente NO queda guardado', outcome(fn () => $open->execute($ctx(RECEP_MORA), 'P-0002')) === 'AuditUnavailable'
    && $stores->records->findByPatient(P2) === null);

$racer = new MedicalRecord('rec-otro-proceso', P2, 'EXP-P-0002', date_utc('2026-10-23 11:59:59+00:00'));
[, $open, $log] = cases(racer: $racer);
$race = $open->execute($ctx(RECEP_MORA), 'P-0002');
check('A6 carrera: otro proceso abrió primero → se devuelve ese expediente con created=false', $race['created'] === false && $race['record']->id === 'rec-otro-proceso' && $log->events === []);

[, $open, $log, , $stores] = cases();
$new = $open->execute($ctx(RECEP_MORA), 'P-0002');
check('A7 camino feliz: created=true, EXP-P-0002, fecha del reloj del servidor y evento «abierto»', $new['created'] === true
    && $new['record']->recordNumber === 'EXP-P-0002' && $new['record']->openedAt == new \DateTimeImmutable('2026-10-23 12:00:00+00:00')
    && $log->names() === ['expediente_medico.abierto:success'] && $stores->records->findByPatient(P2)?->id === $new['record']->id);
