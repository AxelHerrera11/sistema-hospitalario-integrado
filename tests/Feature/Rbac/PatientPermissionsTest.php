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

class PatientPermissionsTest extends TestCase
{
    public function test_patient_operations_enforce_role_and_hospital_permissions(): void
    {
        $operations = [
            ['GET', '/api/v1/patients/P001', 'pacientes.ver'],
            ['POST', '/api/v1/patients', 'pacientes.registrar'],
            ['PATCH', '/api/v1/patients/P001', 'pacientes.editar'],
        ];
        $grants = [
            'superadmin' => [true, true, true],
            'recepcion' => [true, true, true],
            'medico' => [true, false, false],
            'enfermeria' => [true, false, false],
            'admin_hospital' => [false, false, false],
            'desconocido' => [false, false, false],
        ];
        foreach ($operations as [, , $permission]) {
            $this->assertContains($permission, config('rbac.catalog'));
        }
        foreach ($grants as $role => $expected) {
            $store = new InMemoryRbacStore([]);
            $store->grant('patient-user', 'hospital-a', $role);
            $middleware = new RequirePermission(new Authorize(
                $store, new ConfiguredPermissions, $store, new RoleGrantsPermission,
            ));
            foreach (['hospital-a', 'hospital-b'] as $hospital) {
                foreach ($operations as $index => [$method, $path, $permission]) {
                    $request = Request::create($path, $method);
                    $request->headers->set('X-Tenant-ID', $hospital);
                    $request->setUserResolver(fn () => new GenericUser(['id' => 'patient-user']));
                    $request->attributes->set('tenant', new class($hospital) {
                        public function __construct(private string $id) {}

                        public function getKey(): string { return $this->id; }
                    });
                    $executed = false;
                    $response = $middleware->handle($request, function () use (&$executed) {
                        $executed = true;
                        return response()->json(['ok' => true]);
                    }, $permission);
                    $allowed = $hospital === 'hospital-a' && $expected[$index];
                    $this->assertSame($allowed ? 200 : 403, $response->getStatusCode(), "$role $hospital $permission");
                    $this->assertSame($allowed, $executed);
                    if (!$allowed) {
                        $this->assertSame('RBAC_DENIED', json_decode($response->getContent(), true)['code']);
                        $this->assertSame($hospital, array_slice($store->denials(), -1)[0]['hospital_id']);
                    }
                }
            }
        }
    }
}
