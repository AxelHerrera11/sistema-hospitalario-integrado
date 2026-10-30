<?php

declare(strict_types=1);

use MicroHis\ExpedienteMedico\Application\AccessAuthorizer;
use MicroHis\ExpedienteMedico\Application\AccessGuard;
use MicroHis\ExpedienteMedico\Application\ClinicalAccessAudit;
use MicroHis\ExpedienteMedico\Application\Clock;
use MicroHis\ExpedienteMedico\Application\ConsultLongitudinalRecord;
use MicroHis\ExpedienteMedico\Application\ExpedientePermissions;
use MicroHis\ExpedienteMedico\Application\HospitalStores;
use MicroHis\ExpedienteMedico\Application\HospitalStoresProvider;
use MicroHis\ExpedienteMedico\Application\OpenMedicalRecord;
use MicroHis\ExpedienteMedico\Integration\GobernanzaClinicalAudit;
use MicroHis\ExpedienteMedico\Integration\PacientesDirectory;
use MicroHis\ExpedienteMedico\Integration\RbacAccessAuthorizer;
use MicroHis\ExpedienteMedico\Integration\RbacCatalog;
use MicroHis\ExpedienteMedico\Integration\SystemClock;
use MicroHis\ExpedienteMedico\Persistence\PdoCareRelationship;
use MicroHis\ExpedienteMedico\Persistence\PdoMedicalRecordRepository;
use MicroHis\ExpedienteMedico\Presentation\JsonView;
use MicroHis\ExpedienteMedico\Presentation\LongitudinalPresenter;
use MicroHis\ExpedienteMedico\Presentation\MedicalRecordController;
use MicroHis\ExpedienteMedico\Presentation\Router;
use MicroHis\Gobernanza\Application\FailureReporter;
use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\Gobernanza\Domain\AuditLogger;
use MicroHis\Gobernanza\Integration\ErrorLogFailureReporter;
use MicroHis\Gobernanza\Integration\SystemClock as GobernanzaClock;
use MicroHis\Pacientes\Persistence\PdoPatientRepository;
use MicroHis\Rbac\Application\Authorize;
use MicroHis\Rbac\Domain\RoleGrantsPermission;
use MicroHis\Rbac\Persistence\InMemoryRbacStore;

require __DIR__ . '/bootstrap.php';

/** Raíz de composición: el mismo MVC funciona con cualquier adaptador (PDO o InMemory) detrás de los puertos. */
function expediente_router(
    AccessAuthorizer $authorizer,
    HospitalStoresProvider $hospitals,
    ClinicalAccessAudit $audit,
    Clock $clock = new SystemClock(),
    string $aperturaPermission = ExpedientePermissions::APERTURA_PROPUESTA,
): Router {
    $guard = new AccessGuard($authorizer, $audit);
    $view = new JsonView();
    $controller = new MedicalRecordController(
        new ConsultLongitudinalRecord($guard, $hospitals, $audit),
        new OpenMedicalRecord($guard, $hospitals, $audit, $clock, $aperturaPermission),
        new LongitudinalPresenter(),
        $view,
    );

    return new Router($controller, $view);
}

/**
 * Motor RBAC real (Authorize + RoleGrantsPermission) con la matriz ACTIVA de config/rbac.php. Las membresías
 * usuario→hospital→rol llegan como lista porque en el micro-HIS no hay sesión JWT (PROVISIONAL, ver README).
 * $extraGrants solo existe para el prototipo: agrega permisos PROPUESTOS a un rol y debe declararse como tal.
 *
 * @param list<array{0: string, 1: string, 2: string}> $memberships [usuario, hospital, rol]
 * @param array<string, list<string>> $extraGrants rol => permisos propuestos (no activos en config/rbac.php)
 */
function expediente_rbac(array $memberships, array $extraGrants = []): RbacAccessAuthorizer
{
    $roles = RbacCatalog::activeRolePermissions(__DIR__ . '/../../config/rbac.php');
    foreach ($extraGrants as $role => $permissions) {
        $roles[$role] = array_values(array_unique(array_merge($roles[$role] ?? [], $permissions)));
    }
    $store = new InMemoryRbacStore($roles);
    foreach ($memberships as [$user, $hospital, $role]) {
        $store->grant($user, $hospital, $role);
    }

    return new RbacAccessAuthorizer(new Authorize($store, $store, $store, new RoleGrantsPermission()));
}

/**
 * PROPUESTA enviada a RBAC (pendiente, NO activa en config/rbac.php): roles que recibirían el permiso de apertura.
 * Solo el prototipo y las pruebas que lo declaran la usan; el resto del sistema sigue con la matriz activa.
 *
 * @return array<string, list<string>>
 */
function expediente_propuesta_apertura(): array
{
    return ['medico' => [ExpedientePermissions::APERTURA_PROPUESTA], 'recepcion' => [ExpedientePermissions::APERTURA_PROPUESTA]];
}

/** Bitácora real de Gobernanza: RecordAuditEvent sobre cualquier AuditLogger (PdoAuditLog o InMemoryAuditLog). */
function expediente_audit(AuditLogger $logger, ?FailureReporter $reporter = null): GobernanzaClinicalAudit
{
    return new GobernanzaClinicalAudit(new RecordAuditEvent($logger, new GobernanzaClock(), $reporter ?? new ErrorLogFailureReporter()));
}

/** Almacenes PDO de una base HOSPITAL: expediente propio, pacientes vía el repositorio del módulo Pacientes y vínculo. */
function expediente_pdo_stores(PDO $pdo): HospitalStores
{
    return new HospitalStores(new PdoMedicalRecordRepository($pdo), new PacientesDirectory(new PdoPatientRepository($pdo)), new PdoCareRelationship($pdo));
}
