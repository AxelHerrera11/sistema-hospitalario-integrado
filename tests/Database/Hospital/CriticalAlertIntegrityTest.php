<?php

namespace Tests\Database\Hospital;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/** Slice H7: tabla `critical_alerts` de alertas críticas (ASII-21) sobre PostgreSQL real. */
#[Group('database-integrity')]
class CriticalAlertIntegrityTest extends TestCase
{
    private string $medico;

    private string $prueba;

    private string $paciente;

    private string $episodio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $h = DB::connection('hospital');
        $this->medico = $h->scalar("INSERT INTO local_user_refs (central_user_id, membership_id, display_name, role_code, synced_at)
            VALUES (gen_random_uuid(), gen_random_uuid(), 'Médica de prueba', 'medico', now()) RETURNING local_user_ref_id");
        $this->prueba = $h->scalar("INSERT INTO lab_tests (code, name, specimen_type, min_volume_ml, result_type, unit, reference_low, reference_high,
            critical_low, critical_high, version_no, valid_from, created_by_user_ref_id) VALUES ('GLU', 'Glucosa', 'serum', 0.5, 'numeric', 'mg/dL',
            70, 100, 40, 400, 1, '2026-01-01', ?) RETURNING lab_test_id", [$this->medico]);
        [$this->paciente, $this->episodio] = $this->pacienteConEpisodio('P1');
    }

    public function test_alert_for_the_attending_starts_open_and_one_per_result(): void
    {
        $id = $this->insertAlert(['lab_result_version_id' => '00000000-0000-4000-8000-0000000000e1']);

        $this->assertSame('open', DB::connection('hospital')->scalar('SELECT status FROM critical_alerts WHERE critical_alert_id = ?', [$id]));
        $this->assertRejected('critical_alerts_result_unique', fn () => $this->insertAlert(['lab_result_version_id' => '00000000-0000-4000-8000-0000000000e1']));
    }

    public function test_without_recipient_it_goes_to_the_service_head_from_level_one(): void
    {
        $this->insertAlert(['current_recipient_user_ref_id' => null, 'escalation_level' => 1]);
        $this->assertRejected('critical_alerts_service_head_check', fn () => $this->insertAlert(['current_recipient_user_ref_id' => null, 'escalation_level' => 0]));
        $this->assertRejected('critical_alerts_direction_check', fn () => $this->insertAlert(['direction' => 'normal']));
        $this->assertRejected('critical_alerts_level_check', fn () => $this->insertAlert(['escalation_level' => 3]));
    }

    public function test_episode_must_belong_to_the_patient(): void
    {
        [, $otroEpisodio] = $this->pacienteConEpisodio('P2');
        $this->assertRejected('critical_alerts_episode_patient_trigger', fn () => $this->insertAlert(['care_episode_id' => $otroEpisodio]));
    }

    public function test_acknowledgement_records_who_and_when_and_freezes_the_alert(): void
    {
        $id = $this->insertAlert();
        $h = DB::connection('hospital');
        $this->assertRejected('critical_alerts_acknowledgement_check', fn () => $h->update("UPDATE critical_alerts SET status = 'acknowledged' WHERE critical_alert_id = ?", [$id]));
        $this->assertRejected('critical_alerts_detection_immutable', fn () => $h->update('UPDATE critical_alerts SET measured_value = 35 WHERE critical_alert_id = ?', [$id]));
        $h->update("UPDATE critical_alerts SET status = 'acknowledged', acknowledged_by_user_ref_id = ?, acknowledged_at = now() WHERE critical_alert_id = ?", [$this->medico, $id]);
        $this->assertRejected('critical_alerts_acknowledged_immutable', fn () => $h->update('UPDATE critical_alerts SET escalation_level = 2 WHERE critical_alert_id = ?', [$id]));
        $this->assertRejected('critical_alerts_delete_protection', fn () => $h->delete('DELETE FROM critical_alerts WHERE critical_alert_id = ?', [$id]));
    }

    public function test_rollback_removes_only_the_alerts(): void
    {
        $this->insertAlert();
        Artisan::call('migrate:rollback', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--step' => $this->hospitalRollbackSteps('2026_08_11_000660'), '--force' => true]);

        $tables = array_column(DB::connection('hospital')->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'"), 'tablename');
        $this->assertNotContains('critical_alerts', $tables);
        $this->assertContains('lab_tests', $tables);
        $this->assertContains('care_episodes', $tables);
    }

    /** @return array{0: string, 1: string} */
    private function pacienteConEpisodio(string $codigo): array
    {
        $h = DB::connection('hospital');
        $paciente = $h->scalar("INSERT INTO local_patients (global_id, code, given_name, family_name, birth_date, sex)
            VALUES (gen_random_uuid(), ?, 'Paciente', 'Ficticio', '1990-01-01', 'unknown') RETURNING local_patient_id", [$codigo]);

        return [$paciente, $h->scalar("INSERT INTO care_episodes (local_patient_id, type, started_at, reason) VALUES (?, 'inpatient', now(), 'Ingreso')
            RETURNING care_episode_id", [$paciente])];
    }

    private function insertAlert(array $overrides = []): string
    {
        $row = $overrides + ['lab_result_version_id' => (string) Str::uuid(), 'lab_test_id' => $this->prueba, 'local_patient_id' => $this->paciente,
            'care_episode_id' => $this->episodio, 'measured_value' => 32, 'direction' => 'low', 'current_recipient_user_ref_id' => $this->medico,
            'escalation_level' => 0, 'due_at' => now()->addMinutes(15)];

        return DB::connection('hospital')->scalar('INSERT INTO critical_alerts ('.implode(', ', array_keys($row)).') VALUES (?'.str_repeat(', ?', count($row) - 1).')
            RETURNING critical_alert_id', array_values($row));
    }

    private function assertRejected(string $constraint, callable $statement): void
    {
        try {
            DB::connection('hospital')->transaction(fn () => $statement());
            $this->fail("Se esperaba el rechazo de {$constraint}.");
        } catch (QueryException $error) {
            $this->assertStringContainsString($constraint, $error->getMessage());
        }
    }
}
