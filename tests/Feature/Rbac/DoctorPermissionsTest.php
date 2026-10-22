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

class DoctorPermissionsTest extends TestCase
{
    public function test_doctor_operations_enforce_role_and_hospital_permissions(): void
    {
        $this->assertSame(RequirePermission::class, $this->app['router']->getMiddleware()['permission']);
        $this->assertSame(RequirePermission::class, $this->app['router']->getMiddleware()['rbac']);
        $operations = [
            ['POST', '/api/v1/doctors', 'medicos.registrar'],
            ['POST', '/api/v1/doctors/D001/specialties', 'medicos.asociar_especialidad'],
            ['GET', '/api/v1/availability', 'medicos.ver_disponibilidad'],
        ];
        $grants = [
            'superadmin' => [true, true, true],
            'recepcion' => [false, false, true],
            'medico' => [false, false, true],
            'enfermeria' => [false, false, false],
            'admin_hospital' => [true, true, true],
            'desconocido' => [false, false, false],
        ];
        foreach ($operations as [, , $permission]) {
            $this->assertContains($permission, config('rbac.catalog'));
        }
        foreach ($grants as $role => $expected) {
            $store = new InMemoryRbacStore([]);
            $store->grant('doctor-user', 'hospital-a', $role);
            $middleware = new RequirePermission(new Authorize(
                $store, new ConfiguredPermissions, $store, new RoleGrantsPermission,
            ));
            foreach (['hospital-a', 'hospital-b'] as $hospital) {
                foreach ($operations as $index => [$method, $path, $permission]) {
                    $request = Request::create($path, $method);
                    $request->headers->set('X-Tenant-ID', $hospital);
                    $request->setUserResolver(fn () => new GenericUser(['id' => 'doctor-user']));
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
