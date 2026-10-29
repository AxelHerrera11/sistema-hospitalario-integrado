<?php

namespace Tests\Database\Hospital;

use App\Modules\Tenants\Infrastructure\Persistence\MembershipRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Alergias\HospitalTenantIdentity;
use Tests\Feature\TrasladosAltas\TrasladosTestIdentity;
use Tests\TestCase;

/** Validación bioquímica (ASII-20) del resultado capturado (ASII-19) sobre PostgreSQL real; la membresía CENTRAL usa SQLite en memoria. */
#[Group('database-integrity')]
class LabValidationPostgresTest extends TestCase
{
    private const HOSPITAL = '11111111-1111-4111-8111-111111111111';
    private const TECNICO = '00000000-0000-4000-8000-0000000000c1';
    private const BIOQUIMICO = '00000000-0000-4000-8000-0000000000c2';
    private const RESULTADO = '22222222-2222-4222-8222-222222222222';

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
        config()->set('database.connections.lab_central', ['driver' => 'sqlite', 'database' => ':memory:']);
        $central = Schema::connection('lab_central');
        $central->create('central_users', function (Blueprint $t) { $t->string('user_id'); $t->string('status'); });
        $central->create('hospitals', function (Blueprint $t) { $t->string('hospital_id'); $t->string('status'); });
        $central->create('hospital_memberships', function (Blueprint $t) {
            $t->string('user_id'); $t->string('hospital_id'); $t->string('role_code'); $t->string('status');
            $t->timestamp('valid_from'); $t->timestamp('valid_to')->nullable();
        });
        DB::connection('lab_central')->table('central_users')->insert(['user_id' => self::BIOQUIMICO, 'status' => 'active']);
        DB::connection('lab_central')->table('hospitals')->insert(['hospital_id' => self::HOSPITAL, 'status' => 'active']);
        DB::connection('lab_central')->table('hospital_memberships')->insert(['user_id' => self::BIOQUIMICO, 'hospital_id' => self::HOSPITAL,
            'role_code' => 'bioquimico', 'status' => 'active', 'valid_from' => '2020-01-01 00:00:00']);
        $this->app->instance(MembershipRepository::class, new MembershipRepository('lab_central'));
        $h = DB::connection('hospital');
        foreach ([self::TECNICO => 'tecnico_laboratorio', self::BIOQUIMICO => 'bioquimico'] as $usuario => $rol) {
            $h->insert("INSERT INTO local_user_refs (central_user_id, membership_id, display_name, role_code, synced_at)
                VALUES (?, gen_random_uuid(), 'Usuario de prueba', ?, now())", [$usuario, $rol]);
        }
        $h->table('lab_pending_results')->insert(['id' => self::RESULTADO, 'tenant_id' => self::HOSPITAL, 'patient_code' => 'PAC-QA',
            'test_code' => 'GLU', 'expected_unit' => 'mg/dL', 'status' => 'pendiente', 'version' => 1]);
        $this->withHeaders(['X-Tenant-ID' => self::HOSPITAL]);
    }

    protected function tearDown(): void
    {
        // Los dobles de identidad son estáticos: se restauran para no afectar a otras pruebas.
        TrasladosTestIdentity::$hospital = 'hospital-a';
        TrasladosTestIdentity::como(null);
        parent::tearDown();
    }

    public function test_the_captured_result_is_validated_once_by_the_biochemist(): void
    {
        $url = '/api/v1/lab-validation/results/'.self::RESULTADO;
        TrasladosTestIdentity::como('tecnico_laboratorio', self::TECNICO);
        $this->postJson('/api/v1/lab-results/'.self::RESULTADO.'/capture', ['value' => '110', 'unit' => 'mg/dL'])->assertOk();
        $this->postJson($url.'/validate')->assertStatus(403);

        TrasladosTestIdentity::como('bioquimico', self::BIOQUIMICO);
        $this->getJson('/api/v1/lab-validation/results')->assertOk()
            ->assertJsonPath('data.id', self::RESULTADO)->assertJsonPath('data.value', '110')->assertJsonPath('data.status', 'pendiente_revision');
        $this->postJson($url.'/validate')->assertOk();
        $this->postJson($url.'/validate')->assertStatus(422);
        $this->postJson($url.'/reject', ['reason' => 'Muestra hemolizada'])->assertStatus(422);

        $this->assertSame('validado', DB::connection('hospital')->table('lab_results_for_review')->value('status'));
        $this->assertSame(2, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM local_access_audits'));
    }
}
