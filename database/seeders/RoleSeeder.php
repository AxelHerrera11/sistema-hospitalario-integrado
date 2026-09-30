<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles y permisos base del HIS (guard "api").
 *
 * Convención de nombres: <modulo>.<accion>. Cada área puede agregar permisos
 * nuevos aquí mediante PR; no crear permisos "sueltos" en otros seeders.
 * Uso en rutas: ->middleware('permission:pacientes.ver')
 */
class RoleSeeder extends Seeder
{
    private const GUARD = 'api';

    /** @var array<string, list<string>> */
    private const PERMISSIONS = [
        'usuarios' => ['ver', 'crear', 'editar', 'desactivar'],
        'auditoria' => ['ver'],
        'pacientes' => ['ver', 'crear', 'editar'],
        'expediente' => ['ver', 'editar'],
        'medicos' => ['ver', 'gestionar'],
        'citas' => ['ver', 'crear', 'editar', 'cancelar'],
        'camas' => ['ver', 'gestionar'],
        'admisiones' => ['ver', 'crear', 'trasladar', 'dar_alta'],
        'soap' => ['ver', 'crear', 'firmar'],
        'signos_vitales' => ['ver', 'registrar'],
        'alergias' => ['ver', 'registrar', 'editar'],
        'medicamentos' => ['ver', 'gestionar'],
        'prescripciones' => ['ver', 'crear', 'firmar'],
        'laboratorio' => ['ver', 'ordenar', 'recibir_muestra', 'ingresar_resultado', 'validar_resultado', 'gestionar_catalogo'],
        'alertas' => ['ver', 'confirmar'],
        'reportes' => ['ver', 'exportar'],
    ];

    /** @var array<string, list<string>> '*' = todos los permisos */
    private const ROLE_PERMISSIONS = [
        'Admin' => ['*'],
        'Médico' => [
            'pacientes.ver', 'expediente.ver', 'expediente.editar',
            'medicos.ver', 'citas.ver',
            'camas.ver', 'admisiones.ver', 'admisiones.crear', 'admisiones.trasladar', 'admisiones.dar_alta',
            'soap.ver', 'soap.crear', 'soap.firmar',
            'signos_vitales.ver',
            'alergias.ver', 'alergias.registrar', 'alergias.editar',
            'medicamentos.ver', 'prescripciones.ver', 'prescripciones.crear', 'prescripciones.firmar',
            'laboratorio.ver', 'laboratorio.ordenar',
            'alertas.ver', 'alertas.confirmar', 'reportes.ver',
        ],
        'Enfermera' => [
            'pacientes.ver', 'expediente.ver', 'camas.ver', 'admisiones.ver',
            'soap.ver', 'signos_vitales.ver', 'signos_vitales.registrar',
            'alergias.ver', 'alergias.registrar',
            'medicamentos.ver', 'prescripciones.ver', 'laboratorio.ver', 'alertas.ver',
        ],
        'TecnicoLab' => [
            'pacientes.ver', 'laboratorio.ver', 'laboratorio.recibir_muestra', 'laboratorio.ingresar_resultado',
        ],
        'Bioquimico' => [
            'pacientes.ver', 'laboratorio.ver', 'laboratorio.validar_resultado', 'laboratorio.gestionar_catalogo',
            'alertas.ver',
        ],
        'Recepcionista' => [
            'pacientes.ver', 'pacientes.crear', 'pacientes.editar',
            'medicos.ver', 'citas.ver', 'citas.crear', 'citas.editar', 'citas.cancelar',
            'camas.ver', 'admisiones.ver', 'admisiones.crear',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = [];
        foreach (self::PERMISSIONS as $module => $actions) {
            foreach ($actions as $action) {
                $name = "{$module}.{$action}";
                Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]);
                $all[] = $name;
            }
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => self::GUARD]);
            $role->syncPermissions($permissions === ['*'] ? $all : $permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
