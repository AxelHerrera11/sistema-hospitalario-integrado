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

/**
 * API v1 de alertas críticas sobre PostgreSQL real: critical_alerts (000660), lab_tests (000600), la relación de cuidado real
 * (care_assignments → doctor_profiles) y local_access_audits H8. Solo se sustituye la identidad.
 */
#[Group('database-integrity')]
class AlertasPostgresTest extends TestCase
{
    private const MEDICA = '00000000-0000-4000-8000-00000000c0a1';

    private const BIOQUIMICO = '00000000-0000-4000-8000-00000000c0b2';

    private array $ids = [];

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
        $ref = fn (string $usuario, string $rol) => $h->scalar('INSERT INTO local_user_refs (central_user_id, membership_id, display_name, role_code, synced_at)
            VALUES (?, gen_random_uuid(), ?, ?, now()) RETURNING local_user_ref_id', [$usuario, "{$rol} de prueba", $rol]);
        $this->ids['medica'] = $ref(self::MEDICA, 'medico');
        $ref(self::BIOQUIMICO, 'bioquimico');
        $perfil = $h->scalar("INSERT INTO doctor_profiles (local_user_ref_id, license_number, valid_from) VALUES (?, 'MED-21', current_date - 1) RETURNING doctor_profile_id", [$this->ids['medica']]);
        $especialidad = $h->scalar("INSERT INTO specialties (code, name) VALUES ('MI-21', 'Medicina interna') RETURNING specialty_id");
        $h->insert('INSERT INTO doctor_specialties (doctor_profile_id, specialty_id, is_primary, valid_from) VALUES (?, ?, true, current_date - 1)', [$perfil, $especialidad]);
        $this->ids['paciente'] = $h->scalar("INSERT INTO local_patients (global_id, code, given_name, family_name, birth_date, sex)
            VALUES (gen_random_uuid(), 'P1', 'Paciente', 'Ficticio', '1990-01-01', 'unknown') RETURNING local_patient_id");
        $this->ids['episodio'] = $h->scalar("INSERT INTO care_episodes (local_patient_id, type, started_at, reason) VALUES (?, 'inpatient', now(), 'Ingreso')
            RETURNING care_episode_id", [$this->ids['paciente']]);
        $h->insert("INSERT INTO care_assignments (care_episode_id, doctor_profile_id, specialty_id, assigned_by_user_ref_id, scope, reason, source, valid_from)
            VALUES (?, ?, ?, ?, '{\"labs\":true}'::jsonb, 'Médica tratante', 'episode', now())", [$this->ids['episodio'], $perfil, $especialidad, $this->ids['medica']]);
        $this->ids['prueba'] = $h->scalar("INSERT INTO lab_tests (code, name, specimen_type, min_volume_ml, result_type, unit, reference_low, reference_high,
            critical_low, critical_high, version_no, valid_from, created_by_user_ref_id) VALUES ('GLU', 'Glucosa', 'serum', 0.5, 'numeric', 'mg/dL',
            70, 100, 40, 400, 1, current_date, ?) RETURNING lab_test_id", [$this->ids['medica']]);
        TrasladosTestIdentity::$hospital = 'hospital-a';
        config()->set('tenants.hospital_database_tenant_id', 'hospital-a');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    private function detectar(array $campos = []): TestResponse
    {
        TrasladosTestIdentity::como('bioquimico', self::BIOQUIMICO);

        return $this->postJson('/api/v1/critical-alerts/result-validated', $campos + ['result_version_id' => '00000000-0000-4000-8000-0000000000e1',
            'lab_test_id' => $this->ids['prueba'], 'patient_id' => $this->ids['paciente'], 'episode_id' => $this->ids['episodio'], 'value' => 32]);
    }

    public function test_the_attending_from_the_real_care_assignment_receives_and_acknowledges(): void
    {
        $id = $this->detectar()->assertStatus(201)->assertJsonPath('data.alert.recipient_user_ref_id', $this->ids['medica'])->json('data.alert.critical_alert_id');
        $this->detectar()->assertOk()->assertJsonPath('data.outcome', 'duplicate');
        TrasladosTestIdentity::como('medico', self::MEDICA);
        $this->getJson('/api/v1/critical-alerts')->assertOk()->assertJsonPath('data.alerts.0.critical_alert_id', $id);
        $this->postJson("/api/v1/critical-alerts/{$id}/acknowledge", ['read_back' => ['patient_id' => $this->ids['paciente'], 'lab_test_id' => $this->ids['prueba'], 'value' => 32]])
            ->assertOk()->assertJsonPath('data.alert.status', 'acknowledged');
        $h = DB::connection('hospital');
        $this->assertTrue((bool) $h->scalar('SELECT acknowledged_by_user_ref_id = ? AND acknowledged_at IS NOT NULL FROM critical_alerts WHERE critical_alert_id = ?', [$this->ids['medica'], $id]));
        $this->assertSame(['alerta.generada', 'alerta.acusada'], array_column($h->select("SELECT action FROM local_access_audits WHERE module = 'alertas_criticas'
            AND care_episode_id = ? ORDER BY occurred_at, action DESC", [$this->ids['episodio']]), 'action'));
    }

    public function test_episode_of_another_patient_is_an_invalid_event_not_an_outage(): void
    {
        $otro = DB::connection('hospital')->scalar("INSERT INTO local_patients (global_id, code, given_name, family_name, birth_date, sex)
            VALUES (gen_random_uuid(), 'P2', 'Otro', 'Ficticio', '1980-01-01', 'unknown') RETURNING local_patient_id");
        $this->detectar(['patient_id' => $otro])->assertStatus(422)->assertJsonPath('error.code', 'EVENT_INVALID');
        $this->assertSame(0, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM critical_alerts'));
    }

    public function test_unreachable_hospital_database_answers_503_and_not_500(): void
    {
        config()->set('database.connections.hospital.database', 'base_inexistente_asii21');
        DB::purge('hospital');
        $this->detectar()->assertStatus(503)->assertJsonPath('error.code', 'CRITICAL_ALERTS_UNAVAILABLE');
    }
}
