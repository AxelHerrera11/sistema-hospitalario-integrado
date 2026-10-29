<?php

namespace Tests\Database\Hospital;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use Tests\Feature\Alergias\HospitalTenantIdentity;
use Tests\Feature\TrasladosAltas\TrasladosTestIdentity;
use Tests\TestCase;

/** Base de las matrices de permisos de laboratorio (semana 17): roles de config/rbac.php, guard de hospital y PostgreSQL reales. */
abstract class LabPermissionMatrixCase extends TestCase
{
    protected const HOSPITAL = '11111111-1111-4111-8111-111111111111';
    protected const USUARIO = '00000000-0000-4000-8000-0000000000c1';
    protected const RESULTADO = '22222222-2222-4222-8222-222222222222';

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', TrasladosTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', HospitalTenantIdentity::class);
        $this->app->instance(RoleResolver::class, new TrasladosTestIdentity());
        $this->app->instance(DenialRecorder::class, new TrasladosTestIdentity());
        TrasladosTestIdentity::$hospital = self::HOSPITAL;
        config()->set('tenants.hospital_database_tenant_id', self::HOSPITAL);
        DB::connection('hospital')->insert("INSERT INTO local_user_refs (central_user_id, membership_id, display_name, role_code, synced_at)
            VALUES (?, gen_random_uuid(), 'Usuario de prueba', 'tecnico_laboratorio', now())", [self::USUARIO]);
        DB::connection('hospital')->table('lab_pending_results')->insert(['id' => self::RESULTADO, 'tenant_id' => self::HOSPITAL,
            'patient_code' => 'PAC-QA', 'test_code' => 'GLU', 'expected_unit' => 'mg/dL', 'status' => 'pendiente', 'version' => 1]);
        $this->withHeaders(['X-Tenant-ID' => self::HOSPITAL]);
    }

    protected function tearDown(): void
    {
        // Los dobles de identidad son estáticos: se restauran para no afectar a otras pruebas.
        TrasladosTestIdentity::$hospital = 'hospital-a';
        TrasladosTestIdentity::como(null);
        parent::tearDown();
    }

    /** @param list<array{string, string, array<string, mixed>, array<string, int>}> $matrix [método, ruta, cuerpo, rol => estado] */
    protected function assertMatrix(array $matrix, array $tables): void
    {
        foreach ($matrix as [$method, $uri, $body, $expected]) {
            foreach ($expected as $role => $status) {
                TrasladosTestIdentity::como($role, self::USUARIO);
                $this->json($method, $uri, $body)->assertStatus($status);
            }
        }
        foreach ($tables as $table => $rows) {
            $this->assertSame($rows, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM {$table}"), $table);
        }
    }

    protected function assertIdentityAndHospitalFailClosed(string $uri): void
    {
        TrasladosTestIdentity::como(null);
        $this->getJson($uri)->assertStatus(401);
        TrasladosTestIdentity::como('tecnico_laboratorio', self::USUARIO);
        TrasladosTestIdentity::$hospital = '44444444-4444-4444-8444-444444444444';
        $this->withHeaders(['X-Tenant-ID' => '44444444-4444-4444-8444-444444444444'])->getJson($uri)->assertStatus(403);
        TrasladosTestIdentity::$hospital = self::HOSPITAL;
        config()->set('tenants.hospital_database_tenant_id', null);
        $this->withHeaders(['X-Tenant-ID' => self::HOSPITAL])->getJson($uri)->assertStatus(503);
        config()->set('tenants.hospital_database_tenant_id', self::HOSPITAL);
    }

    protected function assertInternalErrorIsGeneric(string $uri, string $table): void
    {
        TrasladosTestIdentity::como('tecnico_laboratorio', self::USUARIO);
        DB::connection('hospital')->statement("DROP TABLE {$table}");
        $body = $this->getJson($uri)->assertStatus(500)->getContent();
        $this->assertStringNotContainsString('SQLSTATE', $body);
        $this->assertStringNotContainsString($table, $body);
    }
}
