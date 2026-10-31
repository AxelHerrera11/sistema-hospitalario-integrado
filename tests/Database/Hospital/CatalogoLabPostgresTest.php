<?php

namespace Tests\Database\Hospital;

use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\TrasladosAltas\TrasladosTestIdentity;
use Tests\TestCase;

/** API v1 del catálogo de pruebas sobre PostgreSQL real (lab_tests 000600, local_access_audits H8, local_user_refs); solo se sustituye la identidad. */
#[Group('database-integrity')]
class CatalogoLabPostgresTest extends TestCase
{
    private const USUARIO = '00000000-0000-4000-8000-00000000c0a1';

    private string $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->app->make(Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', TrasladosTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', 'test.hospital-tenant');
        $this->app->instance('test.hospital-tenant', new class
        {
            public function handle($request, $next)
            {
                $request->attributes->set('tenant', new Hospital(['hospital_id' => TrasladosTestIdentity::$hospital]));

                return $next($request);
            }
        });
        $this->app->instance(RoleResolver::class, new TrasladosTestIdentity);
        $this->app->instance(DenialRecorder::class, new TrasladosTestIdentity);
        $this->actor = DB::connection('hospital')->scalar("INSERT INTO local_user_refs (central_user_id, membership_id, display_name, role_code, synced_at)
            VALUES (?, gen_random_uuid(), 'Superadmin de prueba', 'superadmin', now()) RETURNING local_user_ref_id", [self::USUARIO]);
        TrasladosTestIdentity::$hospital = 'hospital-a';
        config()->set('tenants.hospital_database_tenant_id', 'hospital-a');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
        TrasladosTestIdentity::como('superadmin', self::USUARIO);
    }

    private function registrar(string $codigo): TestResponse
    {
        return $this->postJson('/api/v1/lab-tests', ['code' => $codigo, 'name' => 'Glucosa', 'specimen_type' => 'serum', 'min_volume_ml' => 0.5,
            'result_type' => 'numeric', 'unit' => 'mg/dL', 'reference_low' => 70, 'reference_high' => 100, 'critical_low' => 40, 'critical_high' => 400,
            'valid_from' => now()->toDateString()]);
    }

    public function test_version_author_and_h8_audit_persist_through_the_api(): void
    {
        $id = $this->registrar('GLU')->assertStatus(201)->json('data.lab_test_id');
        $h = DB::connection('hospital');
        $this->assertSame($this->actor, $h->scalar('SELECT created_by_user_ref_id FROM lab_tests WHERE lab_test_id = ?', [$id]));
        $audit = $h->selectOne("SELECT actor_user_ref_id, action, resource_id FROM local_access_audits WHERE module = 'catalogo_laboratorio'");
        $this->assertSame([$this->actor, 'prueba_lab.registrada', $id], array_values((array) $audit));
        TrasladosTestIdentity::como('medico', self::USUARIO);
        $this->getJson('/api/v1/lab-tests?search=GLU')->assertOk()->assertJsonPath('data.lab_tests.0.version_no', 1);
    }

    public function test_second_active_version_is_rejected_and_leaves_no_audit(): void
    {
        $this->registrar('GLU')->assertStatus(201);
        $this->registrar('glu')->assertStatus(409)->assertJsonPath('error.code', 'LAB_TEST_VALIDITY_OVERLAP');
        $h = DB::connection('hospital');
        $this->assertSame(1, (int) $h->scalar('SELECT count(*) FROM lab_tests'));
        $this->assertSame(1, (int) $h->scalar("SELECT count(*) FROM local_access_audits WHERE module = 'catalogo_laboratorio'"));
    }

    public function test_user_without_local_reference_is_rejected_by_the_real_resolver(): void
    {
        TrasladosTestIdentity::como('superadmin', '00000000-0000-4000-8000-00000000c0ff');
        $this->registrar('GLU')->assertStatus(403)->assertJsonPath('error.code', 'ACTOR_WITHOUT_LOCAL_REFERENCE');
        $this->assertSame(0, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM lab_tests'));
    }

    public function test_unreachable_hospital_database_answers_503_and_not_500(): void
    {
        config()->set('database.connections.hospital.database', 'base_inexistente_asii17');
        DB::purge('hospital');
        $this->getJson('/api/v1/lab-tests')->assertStatus(503)->assertJsonPath('error.code', 'LAB_CATALOG_UNAVAILABLE');
        $this->registrar('GLU')->assertStatus(503)->assertJsonPath('error.code', 'LAB_CATALOG_UNAVAILABLE');
    }
}
