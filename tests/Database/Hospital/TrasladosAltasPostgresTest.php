<?php

namespace Tests\Database\Hospital;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\TrasladosAltas\TrasladosTestIdentity;
use Tests\TestCase;

/**
 * API v1 de traslados y altas sobre PostgreSQL real (care_episodes, bed_stays, beds y local_user_refs). Los adaptadores
 * son los del provider sobre la conexión `hospital`; solo se sustituye la identidad (tenant y jwt.auth).
 */
#[Group('database-integrity')]
class TrasladosAltasPostgresTest extends TestCase
{
    private const USUARIO = '00000000-0000-4000-8000-00000000c0a1';

    private string $estancia;
    private string $episodio;
    private string $camaB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        config(['tenants.hospital_database_tenant_id' => 'hospital-a']);
        TrasladosTestIdentity::$hospital = 'hospital-a';
        $this->app['router']->aliasMiddleware('tenant', TrasladosTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', TrasladosTestIdentity::class);
        $this->app->instance(RoleResolver::class, new TrasladosTestIdentity());
        $this->app->instance(DenialRecorder::class, new TrasladosTestIdentity());
        $h = DB::connection('hospital');
        $actor = $h->scalar("INSERT INTO local_user_refs (central_user_id, membership_id, display_name, role_code, synced_at)
            VALUES (?, gen_random_uuid(), 'Enfermera de prueba', 'enfermeria', now()) RETURNING local_user_ref_id", [self::USUARIO]);
        $sala = $h->scalar("INSERT INTO wards (code, name, location) VALUES ('S1', 'Sala', 'Piso 1') RETURNING ward_id");
        [$camaA, $this->camaB] = array_map(fn (string $c) => $h->scalar("INSERT INTO beds (ward_id, code, type) VALUES (?, ?, 'general') RETURNING bed_id", [$sala, $c]), ['A', 'B']);
        $paciente = $h->scalar("INSERT INTO local_patients (global_id, code, given_name, family_name, birth_date, sex)
            VALUES (gen_random_uuid(), 'P1', 'Paciente', 'Ficticio', '1990-01-01', 'unknown') RETURNING local_patient_id");
        $this->episodio = $h->scalar("INSERT INTO care_episodes (local_patient_id, type, started_at, reason) VALUES (?, 'inpatient', now() - interval '2 days', 'Ingreso')
            RETURNING care_episode_id", [$paciente]);
        $this->estancia = $h->scalar("INSERT INTO bed_stays (care_episode_id, bed_id, assigned_by_user_ref_id, started_at, reason)
            VALUES (?, ?, ?, now() - interval '1 day', 'Ingreso') RETURNING bed_stay_id", [$this->episodio, $camaA, $actor]);
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    public function test_a_role_without_the_permission_is_denied_and_writes_nothing(): void
    {
        TrasladosTestIdentity::como('medico', self::USUARIO);
        $this->postJson('/api/v1/traslados', ['estancia_esperada_id' => $this->estancia, 'cama_destino_id' => $this->camaB, 'motivo' => 'Aislamiento'])
            ->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        $this->assertSame(1, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM bed_stays'));
    }

    public function test_transfer_and_discharge_persist_through_the_api(): void
    {
        TrasladosTestIdentity::como('enfermeria', self::USUARIO);
        $destino = $this->postJson('/api/v1/traslados', ['estancia_esperada_id' => $this->estancia, 'cama_destino_id' => $this->camaB, 'motivo' => 'Aislamiento'])
            ->assertStatus(201)->json('estancia_destino_id');
        $h = DB::connection('hospital');
        $this->assertTrue((bool) $h->scalar('SELECT o.ended_at = d.started_at AND o.care_episode_id = d.care_episode_id FROM bed_stays o, bed_stays d
            WHERE o.bed_stay_id = ? AND d.bed_stay_id = ?', [$this->estancia, $destino]));
        TrasladosTestIdentity::como('medico', self::USUARIO);
        $this->postJson('/api/v1/altas', ['estancia_esperada_id' => $destino])->assertOk();
        $this->assertTrue((bool) $h->scalar("SELECT s.ended_at = e.ended_at AND e.status = 'closed' FROM bed_stays s JOIN care_episodes e USING (care_episode_id)
            WHERE s.bed_stay_id = ?", [$destino]));
        $this->assertSame(0, (int) $h->scalar("SELECT count(*) FROM beds WHERE status <> 'available'"));
    }

    public function test_user_without_local_reference_is_rejected_by_the_real_resolver(): void
    {
        TrasladosTestIdentity::como('medico', '00000000-0000-4000-8000-00000000c0ff');
        $this->postJson('/api/v1/altas', ['estancia_esperada_id' => $this->estancia])->assertStatus(403)->assertJsonPath('codigo', 'ACTOR_SIN_REFERENCIA_LOCAL');
        $this->assertTrue((bool) DB::connection('hospital')->scalar('SELECT ended_at IS NULL FROM bed_stays WHERE bed_stay_id = ?', [$this->estancia]));
    }
}
