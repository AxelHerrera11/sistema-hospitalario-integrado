<?php

declare(strict_types=1);

use MicroHis\Rbac\Domain\AssignerPrivilegeRule;
use MicroHis\Rbac\Domain\AuthorizationRule;
use MicroHis\Rbac\Domain\Authorizer;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\PermissionProvider;
use MicroHis\Rbac\Domain\RoleGrantsPermission;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/harness.php';

/** Doble de prueba local: verifica el motor sin depender del adaptador InMemory de la Semana 4. */
final class FakeStore implements PermissionProvider, DenialRecorder
{
    public array $denials = [];

    public function __construct(private array $permissions = [])
    {
    }

    public function permissionsFor(string $roleCode): array
    {
        return $this->permissions[$roleCode] ?? [];
    }

    public function record(array $denial): void
    {
        $this->denials[] = $denial;
    }
}

/** Espía: registra cada contexto que el motor le entrega (para probar el cortocircuito). */
final class SpyRule implements AuthorizationRule
{
    public array $seen = [];

    public function __construct(private ?string $verdict = null)
    {
    }

    public function decide(array $context): ?string
    {
        $this->seen[] = $context;

        return $this->verdict;
    }
}

function ctx(array $roles, string $permission = 'rbac.asignar', array $assign = []): array
{
    return ['user_id' => 'u1', 'hospital_id' => 'H1', 'permission' => $permission, 'roles' => $roles] + $assign;
}

$permissions = ['AdminHospital' => ['rbac.asignar'], 'Medico' => ['prescripciones.crear']];
$store = new FakeStore($permissions);

// Bifurcación por defecto: sin reglas, el motor niega (fallo seguro por diseño).
$closed = new Authorizer($store, $store);
$trace = $closed->authorize(ctx(['Medico'], 'prescripciones.crear'));
check('sin reglas -> deniega con trace_id', $trace !== null && str_starts_with($trace, 'trace-'));
check('sin reglas -> motivo sin_reglas', $store->denials[0]['reason'] === 'sin_reglas');
check('denegación trazable -> registra usuario, hospital y trace_id', $store->denials[0]['user_id'] === 'u1' && $store->denials[0]['hospital_id'] === 'H1' && $store->denials[0]['trace_id'] === $trace);
check('trace_id: distinto en cada denegación', $closed->authorize(ctx(['Medico'])) !== $trace);

// Bifurcación permitida y cortocircuito entre reglas.
$allow = new Authorizer($store, $store);
$spy = new SpyRule(null);
$allow->addRule($spy);
$store->denials = [];
check('regla permite -> autoriza sin denegación', $allow->authorize(ctx(['AdminHospital'])) === null && $store->denials === []);
check('el motor inyecta permissions[rol] en el contexto de la regla', $spy->seen[0]['permissions']['AdminHospital'] === ['rbac.asignar']);

$ordered = new Authorizer($store, $store);
$first = new SpyRule('corta_aqui');
$second = new SpyRule(null);
$ordered->addRule($first);
$ordered->addRule($second);
$trace = $ordered->authorize(ctx(['AdminHospital']));
check('varias reglas: la primera denegación detiene la evaluación', $trace !== null && $second->seen === []);

// Regla RoleGrantsPermission (rama permitida, denegada, multirrol y rol desconocido).
$grant = new RoleGrantsPermission();
check('permiso: un rol lo otorga -> null', $grant->decide(['permission' => 'prescripciones.crear', 'roles' => ['Medico'], 'permissions' => $permissions]) === null);
check('permiso: ningún rol lo otorga -> sin_permiso_de_rol', $grant->decide(['permission' => 'rbac.asignar', 'roles' => ['Medico'], 'permissions' => $permissions]) === 'sin_permiso_de_rol');
check('permiso: se acumula entre varios roles -> null', $grant->decide(['permission' => 'rbac.asignar', 'roles' => ['Medico', 'AdminHospital'], 'permissions' => $permissions]) === null);
check('permiso: rol desconocido -> deniega sin excepción', $grant->decide(['permission' => 'rbac.asignar', 'roles' => ['Fantasma'], 'permissions' => ['Fantasma' => []]]) === 'sin_permiso_de_rol');

// Regla AssignerPrivilegeRule (abstención, permitir, denegar por nivel y sin asignador).
$rule = new AssignerPrivilegeRule();
check('privilegio: sin role_to_assign -> se abstiene (null)', $rule->decide(ctx(['AdminHospital'])) === null);
check('privilegio: nivel igual o menor -> null', $rule->decide(ctx(['AdminHospital'], 'rbac.asignar', ['role_to_assign' => 'Medico'])) === null);
check('privilegio: nivel mayor -> rol_supera_privilegio', $rule->decide(ctx(['AdminHospital'], 'rbac.asignar', ['role_to_assign' => 'SuperAdmin'])) === 'rol_supera_privilegio');
check('privilegio: asignador sin roles -> deniega cualquier nivel', $rule->decide(ctx([], 'rbac.asignar', ['role_to_assign' => 'Recepcionista'])) === 'rol_supera_privilegio');

// Falla segura: si la bitácora lanza, la operación sigue denegada.
$failing = new class implements PermissionProvider, DenialRecorder {
    public function permissionsFor(string $roleCode): array
    {
        return [];
    }

    public function record(array $denial): void
    {
        throw new RuntimeException('bitacora caida');
    }
};
$failSafe = new Authorizer($failing, $failing);
$failSafe->addRule(new RoleGrantsPermission());
$trace = $failSafe->authorize(ctx(['Medico'], 'prescripciones.crear'));
check('falla segura: bitácora caída -> sigue denegado con trace_id', is_string($trace) && str_starts_with($trace, 'trace-'));

summary('caja blanca del motor');
