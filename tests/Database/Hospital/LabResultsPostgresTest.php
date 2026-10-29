<?php

namespace Tests\Database\Hospital;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Alergias\HospitalTenantIdentity;
use Tests\Feature\TrasladosAltas\TrasladosTestIdentity;
use Tests\TestCase;

/** API v1 de ingreso de resultados (ASII-19) sobre PostgreSQL real; solo se sustituye la identidad (tenant y jwt.auth). */
#[Group('database-integrity')]
class LabResultsPostgresTest extends TestCase
{
    private const HOSPITAL = '11111111-1111-4111-8111-111111111111';
    private const TECNICO = '00000000-0000-4000-8000-0000000000c1';
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
        $h = DB::connection('hospital');
        $h->insert("INSERT INTO local_user_refs (central_user_id, membership_id, display_name, role_code, synced_at)
            VALUES (?, gen_random_uuid(), 'Técnico de prueba', 'tecnico_laboratorio', now())", [self::TECNICO]);
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

    private function rows(string $table): int
    {
        return (int) DB::connection('hospital')->scalar("SELECT count(*) FROM {$table}");
    }

    public function test_capture_and_correction_persist_with_audit_and_feed_the_review_queue(): void
    {
        TrasladosTestIdentity::como('tecnico_laboratorio', self::TECNICO);
        $this->getJson('/api/v1/lab-results')->assertOk()->assertJsonPath('data.id', self::RESULTADO);
        $this->assertSame([0, 0], [$this->rows('lab_results_for_review'), $this->rows('local_access_audits')]);

        $this->postJson('/api/v1/lab-results/'.self::RESULTADO.'/capture', ['value' => '110', 'unit' => 'mg/dL'])->assertOk();
        $this->postJson('/api/v1/lab-results/'.self::RESULTADO.'/correct', ['value' => '112', 'unit' => 'mg/dL', 'reason' => 'Transcripción'])->assertOk();

        $review = DB::connection('hospital')->table('lab_results_for_review')->where('id', self::RESULTADO)->first();
        $this->assertSame(['112', 'pendiente_revision'], [$review->value, $review->status]);
        $this->assertSame(2, $this->rows('local_access_audits'));
    }

    public function test_missing_local_actor_hospital_mismatch_and_unknown_result_write_nothing(): void
    {
        TrasladosTestIdentity::como('tecnico_laboratorio', '00000000-0000-4000-8000-0000000000c9');
        $this->postJson('/api/v1/lab-results/'.self::RESULTADO.'/capture', ['value' => '110', 'unit' => 'mg/dL'])->assertStatus(403);
        TrasladosTestIdentity::como('tecnico_laboratorio', self::TECNICO);
        $this->postJson('/api/v1/lab-results/33333333-3333-4333-8333-333333333333/capture', ['value' => '1', 'unit' => 'mg/dL'])->assertStatus(404);
        TrasladosTestIdentity::$hospital = '44444444-4444-4444-8444-444444444444';
        $this->withHeaders(['X-Tenant-ID' => '44444444-4444-4444-8444-444444444444'])
            ->postJson('/api/v1/lab-results/'.self::RESULTADO.'/capture', ['value' => '110', 'unit' => 'mg/dL'])
            ->assertStatus(403)->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_MISMATCH');

        $this->assertSame('pendiente', DB::connection('hospital')->table('lab_pending_results')->value('status'));
        $this->assertSame([0, 0], [$this->rows('lab_results_for_review'), $this->rows('local_access_audits')]);
    }
}
