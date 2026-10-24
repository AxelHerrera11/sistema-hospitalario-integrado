<?php

namespace Tests\Database\Hospital;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\TrasladosAltas\TrasladosTestIdentity;
use Tests\TestCase;

/** API v1 de alergias sobre PostgreSQL real (allergies, local_access_audits H8, local_user_refs); solo se sustituye la identidad. */
#[Group('database-integrity')]
class AlergiasPostgresTest extends TestCase
{
    private const USUARIO = '00000000-0000-4000-8000-00000000c0a1';

    private string $actor;

    private string $paciente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->app->make(Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', TrasladosTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', TrasladosTestIdentity::class);
        $this->app->instance(RoleResolver::class, new TrasladosTestIdentity);
        $this->app->instance(DenialRecorder::class, new TrasladosTestIdentity);
        $h = DB::connection('hospital');
        $this->actor = $h->scalar("INSERT INTO local_user_refs (central_user_id, membership_id, display_name, role_code, synced_at)
            VALUES (?, gen_random_uuid(), 'Médica de prueba', 'medico', now()) RETURNING local_user_ref_id", [self::USUARIO]);
        $this->paciente = $h->scalar("INSERT INTO local_patients (global_id, code, given_name, family_name, birth_date, sex)
            VALUES (gen_random_uuid(), 'P1', 'Paciente', 'Ficticio', '1990-01-01', 'unknown') RETURNING local_patient_id");
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
        TrasladosTestIdentity::como('medico', self::USUARIO);
    }

    private function registrar(string $nombre): TestResponse
    {
        return $this->postJson("/api/v1/patients/{$this->paciente}/allergies", ['allergen_name' => $nombre, 'reaction' => 'Urticaria',
            'allergy_type' => 'allergy', 'severity' => 'severe']);
    }

    public function test_allergy_and_h8_audit_persist_through_the_api(): void
    {
        $id = $this->registrar('Penicilina')->assertStatus(201)->json('data.allergy_id');
        $h = DB::connection('hospital');
        $this->assertSame($this->actor, $h->scalar('SELECT verified_by_user_ref_id FROM allergies WHERE allergy_id = ?', [$id]));
        $audit = $h->selectOne("SELECT actor_user_ref_id, action, resource_id, local_patient_id FROM local_access_audits WHERE module = 'alergias'");
        $this->assertSame([$this->actor, 'alergia.registrada', $id, $this->paciente], array_values((array) $audit));
        $this->getJson("/api/v1/patients/{$this->paciente}/allergy-alert")->assertOk()->assertJsonPath('data.level', 'critical');
    }

    public function test_real_unique_index_rejects_an_equivalent_active_allergy(): void
    {
        $this->registrar('Penicilina')->assertStatus(201);
        $this->registrar(' penicilina ')->assertStatus(409)->assertJsonPath('error.code', 'ALLERGY_ALREADY_ACTIVE');
        $h = DB::connection('hospital');
        $this->assertSame(1, (int) $h->scalar('SELECT count(*) FROM allergies'));
        // La transacción revierte también la auditoría del intento rechazado.
        $this->assertSame(1, (int) $h->scalar("SELECT count(*) FROM local_access_audits WHERE module = 'alergias'"));
    }

    public function test_user_without_local_reference_is_rejected_by_the_real_resolver(): void
    {
        TrasladosTestIdentity::como('medico', '00000000-0000-4000-8000-00000000c0ff');
        $this->registrar('Látex')->assertStatus(403)->assertJsonPath('error.code', 'ACTOR_WITHOUT_LOCAL_REFERENCE');
        $this->assertSame(0, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM allergies'));
    }

    public function test_unreachable_hospital_database_answers_503_and_not_500(): void
    {
        config()->set('database.connections.hospital.database', 'base_inexistente_asii12');
        DB::purge('hospital');
        $this->getJson("/api/v1/patients/{$this->paciente}/allergy-alert")->assertStatus(503)->assertJsonPath('error.code', 'ALLERGIES_UNAVAILABLE');
        $this->registrar('Látex')->assertStatus(503)->assertJsonPath('error.code', 'ALLERGIES_UNAVAILABLE');
    }
}
