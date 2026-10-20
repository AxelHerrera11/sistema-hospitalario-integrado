<?php

namespace Tests\Feature\Rbac;

use App\Rbac\ConfiguredPermissions;
use MicroHis\Rbac\Application\Authorize;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleGrantsPermission;
use MicroHis\Rbac\Domain\RoleResolver;
use Tests\TestCase;

/** Verifica la matriz activa declarada en config/rbac.php. */
class RoleMatrixTest extends TestCase
{
    private const MATRIX = [
        'superadmin' => [
            'pacientes.ver', 'pacientes.registrar', 'pacientes.editar',
            'rbac.roles.ver', 'rbac.asignar', 'rbac.roles.asignar', 'rbac.autorizar',
            'rbac.permisos.ver', 'rbac.denegaciones.ver',
            'usuarios.ver', 'usuarios.desactivar', 'tenants.crear', 'sesiones.revocar_ajenas',
            'prescripciones.ver', 'prescripciones.crear', 'prescripciones.firmar',
            'muestras.recibir', 'muestras.ver', 'muestras.etiquetar',
            'laboratorio.catalogo.ver', 'laboratorio.catalogo.administrar',
            'alergias.ver', 'alergias.registrar', 'admision.crear', 'camas.ver',
            'alertas.ver', 'alertas.acusar', 'alertas.cerrar', 'ocupacion.ver',
        ],
        'admin_hospital' => [
            'rbac.roles.ver', 'rbac.asignar', 'rbac.roles.asignar', 'rbac.permisos.ver', 'rbac.denegaciones.ver',
            'usuarios.ver', 'usuarios.desactivar', 'sesiones.revocar_ajenas', 'ocupacion.ver',
        ],
        'medico' => [
            'pacientes.ver',
            'rbac.autorizar', 'prescripciones.ver', 'prescripciones.crear', 'prescripciones.firmar',
            'laboratorio.catalogo.ver', 'alergias.ver', 'alergias.registrar',
            'alertas.ver', 'alertas.acusar', 'alertas.cerrar',
        ],
        'enfermeria' => [
            'pacientes.ver',
            'rbac.autorizar', 'prescripciones.ver', 'muestras.recibir', 'muestras.ver',
            'muestras.etiquetar', 'alergias.ver', 'alergias.registrar',
        ],
        'recepcion' => [
            'pacientes.ver', 'pacientes.registrar', 'pacientes.editar',
            'rbac.autorizar', 'muestras.recibir', 'muestras.ver', 'admision.crear', 'camas.ver',
        ],
    ];

    public function test_catalog_is_unique_literal_and_keeps_both_assignment_operations(): void
    {
        $catalog = config('rbac.catalog');
        $this->assertSame(array_values(array_unique($catalog)), $catalog);
        foreach ($catalog as $permission) {
            $this->assertMatchesRegularExpression('/^[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+$/D', $permission);
            $this->assertStringNotContainsString('*', $permission);
        }
        $this->assertContains('rbac.asignar', $catalog);
        $this->assertContains('rbac.roles.asignar', $catalog);
        $this->assertSame(['superadmin', 'admin_hospital', 'medico', 'enfermeria', 'recepcion'], array_keys(config('rbac.roles')));
    }

    public function test_membership_and_role_permission_operations_are_distinct(): void
    {
        $this->assertContains('rbac.asignar', config('rbac.roles')['admin_hospital']);
        $this->assertContains('rbac.roles.asignar', config('rbac.roles')['admin_hospital']);

        config(['rbac.roles' => ['solo_membresias' => ['rbac.asignar']]]);
        $provider = new ConfiguredPermissions;
        $this->assertSame(['rbac.asignar'], $provider->permissionsFor('solo_membresias'));
        $this->assertNotContains('rbac.roles.asignar', $provider->permissionsFor('solo_membresias'));
    }

    public function test_superadmin_permissions_are_explicit_and_exclude_pending_grants(): void
    {
        $expected = array_values(array_diff(config('rbac.catalog'), ['prescripciones.excepcion_alergia']));
        $this->assertEqualsCanonicalizing($expected, config('rbac.roles')['superadmin']);
        $this->assertNotContains('*', config('rbac.roles')['superadmin']);
        $this->assertNotContains('rbac.*', config('rbac.roles')['superadmin']);
        $this->assertNotContains('prescripciones.excepcion_alergia', config('rbac.roles')['superadmin']);
    }

    public function test_role_matrix_matches_the_proposed_grants(): void
    {
        $provider = new ConfiguredPermissions;
        foreach (self::MATRIX as $role => $permissions) {
            $this->assertEqualsCanonicalizing($permissions, $provider->permissionsFor($role), $role);
            $this->assertSame([], array_values(array_diff($permissions, config('rbac.catalog'))), $role.' fuera del catálogo');
        }
    }

    public function test_roles_are_literal_case_sensitive_and_not_inherited(): void
    {
        $provider = new ConfiguredPermissions;
        foreach (['Medico', 'MEDICO', 'AdminHospital', 'Enfermeria', 'TecnicoLab', 'Bioquimico', 'medico.extra', 'medico ', '*', ''] as $role) {
            $this->assertSame([], $provider->permissionsFor($role), $role);
        }
        $this->assertNotContains('rbac.permisos.ver', $provider->permissionsFor('medico'));
        $this->assertNotContains('prescripciones.crear', $provider->permissionsFor('enfermeria'));
        $this->assertNotContains('prescripciones.ver', $provider->permissionsFor('recepcion'));
    }

    public function test_pending_grants_are_documented_outside_active_roles_and_are_denied(): void
    {
        $provider = new ConfiguredPermissions;
        $catalog = config('rbac.catalog');
        $this->assertSame([], array_values(array_diff(array_keys(config('rbac.pending')), $catalog)));

        foreach (config('rbac.pending') as $permission => $roles) {
            foreach ($roles as $role) {
                $this->assertNotContains($permission, $provider->permissionsFor($role), $role.' -> '.$permission);
                $decision = $this->authorizeFor([$role])->execute('user', 'hospital', $permission);
                $this->assertFalse($decision['allowed'], $role.' -> '.$permission);
                $this->assertSame('permiso_faltante', $decision['reason']);
            }
        }

        foreach (array_keys(config('rbac.roles')) as $role) {
            $this->assertNotContains('prescripciones.excepcion_alergia', $provider->permissionsFor($role));
        }
        $this->assertTrue($this->authorizeFor(['superadmin'])->execute('user', 'hospital', 'laboratorio.catalogo.administrar')['allowed']);
        $this->assertFalse($this->authorizeFor(['admin_hospital'])->execute('user', 'hospital', 'laboratorio.catalogo.administrar')['allowed']);
    }

    public function test_published_permissions_without_agreement_are_not_granted(): void
    {
        $provider = new ConfiguredPermissions;
        $publishedOnly = [
            'alergias.resolver', 'muestras.rechazar', 'admisiones.registrar', 'admisiones.ver',
            'camas.asignar', 'notas_soap.crear', 'notas_soap.ver', 'diagnosticos.asociar',
            'signos_vitales.ver', 'signos_vitales.registrar', 'medicos.ver_disponibilidad',
        ];
        foreach (array_keys(config('rbac.roles')) as $role) {
            foreach ($publishedOnly as $permission) {
                $this->assertNotContains($permission, $provider->permissionsFor($role), $role.' -> '.$permission);
            }
        }
        $this->assertNotContains('alertas.ver', $provider->permissionsFor('enfermeria'));
    }

    /** @param string[] $roles */
    private function authorizeFor(array $roles): Authorize
    {
        return new Authorize(
            new class($roles) implements RoleResolver
            {
                /** @param string[] $roles */
                public function __construct(private readonly array $roles) {}

                public function rolesIn(string $userId, string $hospitalId): array
                {
                    return $this->roles;
                }
            },
            new ConfiguredPermissions,
            new class implements DenialRecorder
            {
                public function record(array $denial): void {}
            },
            new RoleGrantsPermission,
        );
    }
}
