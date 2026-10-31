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

/** API v1 de admisión sobre PostgreSQL real (care_episodes, bed_stays, beds, local_access_audits H8, local_user_refs); solo se sustituye la identidad. */
#[Group('database-integrity')]
class AdmisionPostgresTest extends TestCase
{
    private const USUARIO = '00000000-0000-4000-8000-00000000c0a1';

    private string $actor;

    private string $paciente;

    private string $cama;

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
        $h = DB::connection('hospital');
        $this->actor = $h->scalar("INSERT INTO local_user_refs (central_user_id, membership_id, display_name, role_code, synced_at)
            VALUES (?, gen_random_uuid(), 'Recepción de prueba', 'recepcion', now()) RETURNING local_user_ref_id", [self::USUARIO]);
        $sala = $h->scalar("INSERT INTO wards (code, name, location) VALUES ('S1', 'Sala', 'Piso 1') RETURNING ward_id");
        $this->cama = $h->scalar("INSERT INTO beds (ward_id, code, type) VALUES (?, 'A-101', 'general') RETURNING bed_id", [$sala]);
        $this->paciente = $h->scalar("INSERT INTO local_patients (global_id, code, given_name, family_name, birth_date, sex)
            VALUES (gen_random_uuid(), 'P1', 'Paciente', 'Ficticio', '1990-01-01', 'unknown') RETURNING local_patient_id");
        TrasladosTestIdentity::$hospital = 'hospital-a';
        config()->set('tenants.hospital_database_tenant_id', 'hospital-a');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
        TrasladosTestIdentity::como('recepcion', self::USUARIO);
    }

    private function admitir(string $paciente): TestResponse
    {
        return $this->postJson('/api/v1/admissions', ['patient_id' => $paciente, 'bed_id' => $this->cama, 'reason' => 'Neumonía']);
    }

    public function test_episode_stay_bed_and_h8_audit_persist_in_one_transaction(): void
    {
        $stay = $this->admitir($this->paciente)->assertStatus(201)->json('data.bed_stay_id');
        $h = DB::connection('hospital');
        $this->assertTrue((bool) $h->scalar("SELECT e.type = 'inpatient' AND e.status = 'open' AND e.reason = 'Neumonía' AND s.assigned_by_user_ref_id = ?
            FROM bed_stays s JOIN care_episodes e USING (care_episode_id) WHERE s.bed_stay_id = ?", [$this->actor, $stay]));
        $this->assertSame('occupied', $h->scalar('SELECT status FROM beds WHERE bed_id = ?', [$this->cama]));
        $audit = $h->selectOne("SELECT actor_user_ref_id, action, resource_id FROM local_access_audits WHERE module = 'admision'");
        $this->assertSame([$this->actor, 'admision.cama_asignada', $stay], array_values((array) $audit));
        $this->getJson('/api/v1/beds/available')->assertOk()->assertJsonCount(0, 'data.beds');
    }

    public function test_occupied_bed_is_rejected_and_nothing_is_left_behind(): void
    {
        $this->admitir($this->paciente)->assertStatus(201);
        $otro = DB::connection('hospital')->scalar("INSERT INTO local_patients (global_id, code, given_name, family_name, birth_date, sex)
            VALUES (gen_random_uuid(), 'P2', 'Otro', 'Ficticio', '1980-01-01', 'unknown') RETURNING local_patient_id");
        $this->admitir($otro)->assertStatus(409)->assertJsonPath('error.code', 'BED_NOT_AVAILABLE');
        $h = DB::connection('hospital');
        $this->assertSame(1, (int) $h->scalar('SELECT count(*) FROM care_episodes'));
        $this->assertSame(1, (int) $h->scalar("SELECT count(*) FROM local_access_audits WHERE module = 'admision'"));
    }

    public function test_user_without_local_reference_is_rejected_by_the_real_resolver(): void
    {
        TrasladosTestIdentity::como('recepcion', '00000000-0000-4000-8000-00000000c0ff');
        $this->admitir($this->paciente)->assertStatus(403)->assertJsonPath('error.code', 'ACTOR_WITHOUT_LOCAL_REFERENCE');
        $this->assertSame('available', DB::connection('hospital')->scalar('SELECT status FROM beds WHERE bed_id = ?', [$this->cama]));
    }

    public function test_unreachable_hospital_database_answers_503_and_not_500(): void
    {
        config()->set('database.connections.hospital.database', 'base_inexistente_asii07');
        DB::purge('hospital');
        $this->getJson('/api/v1/beds/available')->assertStatus(503)->assertJsonPath('error.code', 'ADMISSIONS_UNAVAILABLE');
        $this->admitir($this->paciente)->assertStatus(503)->assertJsonPath('error.code', 'ADMISSIONS_UNAVAILABLE');
    }
}
