<?php

namespace Tests\Feature\Rbac;

use App\Rbac\ConfiguredPermissions;
use App\Rbac\RequirePermission;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use MicroHis\Rbac\Application\Authorize;
use MicroHis\Rbac\Domain\RoleGrantsPermission;
use MicroHis\Rbac\Persistence\InMemoryRbacStore;
use Tests\TestCase;

class PublishedContractsTest extends TestCase
{
    public function test_documented_matrix_matches_every_catalog_permission_and_active_role(): void
    {
        $document = file_get_contents(base_path('docs/rbac/catalogo-modulos.md'));
        $roles = array_keys(config('rbac.roles'));
        preg_match_all('/^\| `([^`]+)` \| (.+) \|$/m', $document, $rows, PREG_SET_ORDER);
        $seen = [];
        foreach ($rows as $row) {
            $permission = $row[1];
            $cells = array_map('trim', explode('|', $row[2]));
            $this->assertCount(count($roles), $cells, $permission);
            $this->assertNotContains($permission, $seen);
            foreach ($roles as $index => $role) {
                $expected = in_array($permission, config('rbac.roles')[$role], true) ? 'Sí' : '—';
                $this->assertSame($expected, $cells[$index], $role.' -> '.$permission);
            }
            $seen[] = $permission;
        }
        $this->assertEqualsCanonicalizing(config('rbac.catalog'), $seen);
    }

    public function test_all_active_and_pending_grants_are_consistent_with_the_catalog(): void
    {
        $catalog = config('rbac.catalog');
        $roles = config('rbac.roles');
        $provider = new ConfiguredPermissions;
        $referenced = [];
        foreach ($roles as $role => $permissions) {
            $this->assertSame(array_values(array_unique($permissions)), $permissions, $role);
            foreach ($permissions as $permission) {
                $this->assertContains($permission, $catalog, $role);
                $referenced[] = $permission;
            }
        }
        foreach (config('rbac.pending') as $permission => $proposedRoles) {
            $this->assertContains($permission, $catalog);
            $this->assertNotEmpty($proposedRoles, $permission);
            foreach ($proposedRoles as $role) {
                $this->assertArrayHasKey($role, $roles, $permission);
                $this->assertNotContains($permission, $provider->permissionsFor($role), $role);
            }
            $referenced[] = $permission;
        }
        $this->assertEqualsCanonicalizing(array_values(array_unique($referenced)), $catalog);
    }

    public function test_medication_write_contracts_are_active_only_for_hospital_administration(): void
    {
        $provider = new ConfiguredPermissions;
        foreach (['medicamentos.registrar', 'medicamentos.cambiar_vigencia'] as $permission) {
            $this->assertContains($permission, config('rbac.catalog'));
            $this->assertArrayNotHasKey($permission, config('rbac.pending'));
            foreach (array_keys(config('rbac.roles')) as $role) {
                $this->assertSame($role === 'admin_hospital', in_array($permission, $provider->permissionsFor($role), true), $role);
            }
        }
    }

    public function test_medication_writes_require_membership_in_the_target_hospital(): void
    {
        $store = new InMemoryRbacStore([]);
        $store->grant('actor', 'hospital-a', 'admin_hospital');
        $middleware = new RequirePermission(new Authorize($store, new ConfiguredPermissions, $store, new RoleGrantsPermission));
        foreach (['medicamentos.registrar', 'medicamentos.cambiar_vigencia'] as $permission) {
            foreach (['hospital-a', 'hospital-b'] as $hospital) {
                $request = Request::create('/api/v1/medications', 'POST');
                $request->setUserResolver(fn () => new GenericUser(['id' => 'actor']));
                $request->headers->set('X-Tenant-ID', $hospital);
                $request->attributes->set('tenant', new class($hospital)
                {
                    public function __construct(private string $id) {}

                    public function getKey(): string
                    {
                        return $this->id;
                    }
                });
                $executed = false;
                $response = $middleware->handle($request, function () use (&$executed) {
                    $executed = true;

                    return response()->json(['ok' => true]);
                }, $permission);
                $this->assertSame($hospital === 'hospital-a' ? 200 : 403, $response->getStatusCode());
                $this->assertSame($hospital === 'hospital-a', $executed);
            }
        }
    }

    public function test_route_and_openapi_permission_declarations_use_catalogued_names(): void
    {
        foreach (['app', 'routes', 'resources', 'micro-his'] as $directory) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($directory)));
            foreach ($files as $file) {
                $path = str_replace('\\', '/', $file->getPathname());
                if (str_contains($path, '/tests/') || ! in_array($file->getExtension(), ['php', 'js', 'ts', 'vue', 'yaml', 'yml'], true)) {
                    continue;
                }
                preg_match_all('/(?:rbac:|permission:|x-required-permission:\s*[\'"]?)([a-z][a-z0-9_-]*(?:\.[a-z][a-z0-9_-]*)+)/', file_get_contents($path), $matches);
                foreach (array_unique($matches[1]) as $permission) {
                    $this->assertContains($permission, config('rbac.catalog'), $path);
                }
            }
        }
    }

    public function test_published_module_permissions_are_catalogued_or_explicitly_superseded(): void
    {
        // Referencias de archivos: no son declaraciones de permisos.
        $exceptions = ['prescripciones.test.mjs', 'muestras.yml', 'diagnosticos.png', 'diagnosticos.asociar', 'prescripciones.anular', 'laboratorio.catalogo', 'laboratorio.ordenes', 'admision.registrada', 'wards.ward_id', 'wards.status', 'ocupacion.js', 'ocupacion.test.mjs', 'traslados.test.mjs', 'reportes.js', 'reportes.test.mjs', 'reportes.a11y.test.mjs', 'alergias.test.mjs', 'admision.mjs', 'admision.sh', 'admision.txt', 'alergias.mjs', 'alergias.sh', 'alertas.sh', 'alertas.txt', 'alertas.mjs', 'alergias.txt'];
        $prefixes = 'pacientes|medicos|admisiones|admision|camas|alergias|prescripciones|muestras|notas_soap|diagnosticos|signos_vitales|laboratorio|lab-results|ocupacion|alertas|usuarios|sesiones|citas|reportes|documentacion|expediente|medicamentos|auditoria|salas|wards|traslados|altas|analytics';
        $seen = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('docs')));
        foreach ($files as $file) {
            if (! in_array($file->getExtension(), ['md', 'yaml', 'yml'], true)) {
                continue;
            }
            preg_match_all('/\b(?:'.$prefixes.')\.[a-z][a-z0-9_-]*(?:\.[a-z][a-z0-9_-]*)*/', file_get_contents($file->getPathname()), $matches);
            foreach (array_unique($matches[0]) as $permission) {
                if (! in_array($permission, $exceptions, true)) {
                    $this->assertContains($permission, config('rbac.catalog'), $file->getPathname());
                    $seen[$permission] = true;
                }
            }
        }
        $this->assertGreaterThanOrEqual(40, count($seen));
    }

    public function test_pending_pr_contract_snapshot_is_also_covered(): void
    {
        $contracts = json_decode(file_get_contents(base_path('tests/Fixtures/Rbac/pending-contracts.json')), true, flags: JSON_THROW_ON_ERROR);
        foreach ($contracts as $contract) {
            $this->assertNotEmpty($contract['permissions']);
            foreach ($contract['permissions'] as $permission) {
                $this->assertContains($permission, config('rbac.catalog'), 'PR #'.$contract['pr'].' '.$contract['path']);
            }
        }
        $this->assertNotContains('alertas.supervisar', (new ConfiguredPermissions)->permissionsFor('medico'));
        $this->assertContains('alertas.supervisar', (new ConfiguredPermissions)->permissionsFor('jefatura_medica'));
        $this->assertNotContains('notas_soap.ver', (new ConfiguredPermissions)->permissionsFor('admin_hospital'));
    }

    public function test_result_permissions_with_hyphens_use_literal_policy_and_hospital_scope(): void
    {
        $store = new InMemoryRbacStore([]);
        $store->grant('actor', 'hospital-a', 'superadmin');
        $middleware = new RequirePermission(new Authorize($store, new ConfiguredPermissions, $store, new RoleGrantsPermission));
        foreach (['read', 'create', 'correct', 'validate'] as $operation) {
            foreach (['hospital-a', 'hospital-b'] as $hospital) {
                $request = Request::create('/api/v1/lab-results');
                $request->setUserResolver(fn () => new GenericUser(['id' => 'actor']));
                $request->headers->set('X-Tenant-ID', $hospital);
                $request->attributes->set('tenant', new class($hospital)
                {
                    public function __construct(private string $id) {}

                    public function getKey(): string
                    {
                        return $this->id;
                    }
                });
                $executed = false;
                $response = $middleware->handle($request, function () use (&$executed) {
                    $executed = true;

                    return response()->json(['ok' => true]);
                }, 'lab-results.'.$operation);
                $this->assertSame($hospital === 'hospital-a' ? 200 : 403, $response->getStatusCode());
                $this->assertSame($hospital === 'hospital-a', $executed);
            }
        }
        foreach (['medico', 'enfermeria', 'recepcion', 'admin_hospital', 'TecnicoLab', 'Bioquimico'] as $role) {
            foreach (['read', 'create', 'correct', 'validate'] as $operation) {
                $this->assertNotContains('lab-results.'.$operation, (new ConfiguredPermissions)->permissionsFor($role));
            }
        }
        $provider = new ConfiguredPermissions;
        $this->assertContains('lab-results.create', $provider->permissionsFor('tecnico_laboratorio'));
        $this->assertContains('lab-results.correct', $provider->permissionsFor('tecnico_laboratorio'));
        $this->assertNotContains('lab-results.validate', $provider->permissionsFor('tecnico_laboratorio'));
        $this->assertContains('lab-results.validate', $provider->permissionsFor('bioquimico'));
        $this->assertNotContains('lab-results.create', $provider->permissionsFor('bioquimico'));
        $this->assertNotContains('lab-results.correct', $provider->permissionsFor('bioquimico'));
    }
}
