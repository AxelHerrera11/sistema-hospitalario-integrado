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
    public function test_medication_write_contracts_are_pending_and_denied_to_every_active_role(): void
    {
        $provider = new ConfiguredPermissions;
        foreach (['medicamentos.registrar', 'medicamentos.cambiar_vigencia'] as $permission) {
            $this->assertContains($permission, config('rbac.catalog'));
            $this->assertSame(['admin_hospital'], config('rbac.pending')[$permission]);
            foreach (array_keys(config('rbac.roles')) as $role) {
                $this->assertNotContains($permission, $provider->permissionsFor($role), $role);
            }
        }
    }

    public function test_published_module_permissions_are_catalogued_or_explicitly_superseded(): void
    {
        $exceptions = ['diagnosticos.asociar', 'prescripciones.anular', 'laboratorio.catalogo', 'laboratorio.ordenes', 'admision.registrada'];
        $prefixes = 'pacientes|medicos|admisiones|admision|camas|alergias|prescripciones|muestras|notas_soap|diagnosticos|signos_vitales|laboratorio|lab-results|ocupacion|alertas|usuarios|sesiones|citas|reportes|documentacion|expediente|medicamentos|auditoria';
        $seen = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('docs')));
        foreach ($files as $file) {
            if (!in_array($file->getExtension(), ['md', 'yaml'], true)) {
                continue;
            }
            preg_match_all('/\b(?:'.$prefixes.')\.[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)*/', file_get_contents($file->getPathname()), $matches);
            foreach (array_unique($matches[0]) as $permission) {
                if (!in_array($permission, $exceptions, true)) {
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
                $request->attributes->set('tenant', new class($hospital) {
                    public function __construct(private string $id) {}

                    public function getKey(): string { return $this->id; }
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
