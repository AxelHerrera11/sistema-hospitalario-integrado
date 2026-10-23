<?php

namespace Tests\Database\Hospital;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class PrescriptionIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
    }

    public function test_prescription_schema_is_local_and_uses_required_types_and_checks(): void
    {
        $db = DB::connection('hospital');

        $this->assertTrue($this->tableExists('prescriptions'));
        $this->assertSame('uuid', $this->columnType('prescription_id'));
        $this->assertSame('numeric', $this->columnType('dose_amount'));
        $this->assertSame(10, (int) $db->scalar("SELECT numeric_precision FROM information_schema.columns WHERE table_name = 'prescriptions' AND column_name = 'dose_amount'"));
        $this->assertSame(3, (int) $db->scalar("SELECT numeric_scale FROM information_schema.columns WHERE table_name = 'prescriptions' AND column_name = 'dose_amount'"));
        $this->assertSame(0, (int) $db->scalar("SELECT count(*) FROM information_schema.columns WHERE table_name = 'prescriptions' AND column_name = 'tenant_id'"));
        $this->assertSame(1, (int) $db->scalar("SELECT count(*) FROM pg_constraint WHERE conrelid = 'prescriptions'::regclass AND contype = 'c' AND pg_get_constraintdef(oid) ILIKE '%status%'"));
        $this->assertSame(1, (int) $db->scalar("SELECT count(*) FROM pg_constraint WHERE conrelid = 'prescriptions'::regclass AND conname = 'prescriptions_allergy_check_result_check' AND contype = 'c'"));
    }

    public function test_prescription_foreign_keys_are_restrictive_and_indexed(): void
    {
        $expected = [
            'prescriptions_local_patient_id_fk' => 'local_patient_id',
            'prescriptions_care_episode_id_fk' => 'care_episode_id',
            'prescriptions_medication_id_fk' => 'medication_id',
            'prescriptions_prescribed_by_doctor_profile_id_fk' => 'prescribed_by_doctor_profile_id',
            'prescriptions_signed_by_doctor_profile_id_fk' => 'signed_by_doctor_profile_id',
        ];
        $db = DB::connection('hospital');

        foreach ($expected as $constraint => $column) {
            $this->assertSame('r', $db->scalar('SELECT confdeltype FROM pg_constraint WHERE conname = ?', [$constraint]));
            $this->assertSame(1, (int) $db->scalar(
                'SELECT count(*) FROM pg_index i JOIN pg_class t ON t.oid = i.indrelid JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(i.indkey) WHERE t.relname = ? AND a.attname = ?',
                ['prescriptions', $column],
            ));
        }
    }

    public function test_defaults_and_checks_accept_only_supported_values(): void
    {
        $this->insertGraph();
        $id = $this->insertPrescription();
        $db = DB::connection('hospital');

        $row = $db->selectOne('SELECT status, allergy_check_result FROM prescriptions WHERE prescription_id = ?', [$id]);
        $this->assertSame('draft', $row->status);
        $this->assertSame('clear', $row->allergy_check_result);

        $db->update("UPDATE prescriptions SET status = 'signed', allergy_check_result = 'warning' WHERE prescription_id = ?", [$id]);
        $this->assertSame('signed', $db->scalar('SELECT status FROM prescriptions WHERE prescription_id = ?', [$id]));
        $this->assertSame('warning', $db->scalar('SELECT allergy_check_result FROM prescriptions WHERE prescription_id = ?', [$id]));
        $this->assertRejected('prescriptions_status_check', fn () => $db->update("UPDATE prescriptions SET status = 'cancelled' WHERE prescription_id = ?", [$id]));
        $this->assertRejected('prescriptions_allergy_check_result_check', fn () => $db->update("UPDATE prescriptions SET allergy_check_result = 'blocked' WHERE prescription_id = ?", [$id]));
    }

    public function test_prescription_restricts_deletion_of_referenced_records(): void
    {
        $ids = $this->insertGraph();
        $this->insertPrescription();
        $db = DB::connection('hospital');

        $this->assertRejected('prescriptions_care_episode_id_fk', fn () => $db->delete('DELETE FROM care_episodes WHERE care_episode_id = ?', [$ids['care_episode']]));
        $this->assertRejected('prescriptions_medication_id_fk', fn () => $db->delete('DELETE FROM medications WHERE medication_id = ?', [$ids['medication']]));
        $this->assertRejected('prescriptions_prescribed_by_doctor_profile_id_fk', fn () => $db->delete('DELETE FROM doctor_profiles WHERE doctor_profile_id = ?', [$ids['doctor']]));
    }

    public function test_rollback_removes_prescriptions_and_reinstalls_the_slice(): void
    {
        $this->insertGraph();
        $this->insertPrescription();
        Artisan::call('migrate:rollback', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--step' => 2, '--force' => true]);

        $this->assertFalse($this->tableExists('prescriptions'));
        $this->assertFalse($this->tableExists('doctor_schedules'));
        $this->assertTrue($this->tableExists('medications'));

        Artisan::call('migrate', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->assertTrue($this->tableExists('prescriptions'));
    }

    private function insertGraph(): array
    {
        $db = DB::connection('hospital');
        $db->insert("INSERT INTO local_user_refs (local_user_ref_id, central_user_id, membership_id, display_name, role_code, synced_at) VALUES ('00000000-0000-0000-0000-000000000101', gen_random_uuid(), gen_random_uuid(), 'Prescriber', 'physician', now())");
        $db->insert("INSERT INTO doctor_profiles (doctor_profile_id, local_user_ref_id, license_number, valid_from) VALUES ('00000000-0000-0000-0000-000000000102', '00000000-0000-0000-0000-000000000101', 'PRESCRIPTION-TEST', current_date)");
        $db->insert("INSERT INTO local_patients (local_patient_id, global_id, code, given_name, family_name, birth_date, sex) VALUES ('00000000-0000-0000-0000-000000000103', gen_random_uuid(), 'PRESCRIPTION-TEST', 'Test', 'Patient', '2000-01-01', 'unknown')");
        $db->insert("INSERT INTO care_episodes (care_episode_id, local_patient_id, type, started_at, reason) VALUES ('00000000-0000-0000-0000-000000000104', '00000000-0000-0000-0000-000000000103', 'outpatient', now(), 'Prescription integrity test')");
        $db->insert("INSERT INTO medications (medication_id, code, name, active_ingredient, pharmacological_group) VALUES ('00000000-0000-0000-0000-000000000105', 'PRESCRIPTION-TEST', 'Test medication', 'Test ingredient', 'Test group')");

        return [
            'patient' => '00000000-0000-0000-0000-000000000103',
            'care_episode' => '00000000-0000-0000-0000-000000000104',
            'medication' => '00000000-0000-0000-0000-000000000105',
            'doctor' => '00000000-0000-0000-0000-000000000102',
        ];
    }

    private function insertPrescription(): string
    {
        return (string) DB::connection('hospital')->scalar(
            'INSERT INTO prescriptions (local_patient_id, care_episode_id, medication_id, prescribed_by_doctor_profile_id, dose_amount, dose_unit, route, frequency_times, duration_value, duration_unit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING prescription_id',
            [
                '00000000-0000-0000-0000-000000000103',
                '00000000-0000-0000-0000-000000000104',
                '00000000-0000-0000-0000-000000000105',
                '00000000-0000-0000-0000-000000000102',
                500,
                'mg',
                'oral',
                2,
                7,
                'days',
            ],
        );
    }

    private function assertRejected(string $constraint, callable $work): void
    {
        try {
            $work();
            $this->fail('Se esperaba el rechazo por '.$constraint);
        } catch (QueryException $error) {
            $this->assertStringContainsString($constraint, $error->getMessage());
        }
    }

    private function tableExists(string $table): bool
    {
        return (bool) DB::connection('hospital')->scalar('SELECT to_regclass(?) IS NOT NULL', ['public.'.$table]);
    }

    private function columnType(string $column): string
    {
        return (string) DB::connection('hospital')->scalar('SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = ?', ['prescriptions', $column]);
    }
}
