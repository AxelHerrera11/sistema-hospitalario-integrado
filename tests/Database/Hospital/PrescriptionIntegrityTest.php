<?php

namespace Tests\Database\Hospital;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $this->assertSame(1, (int) $db->scalar("SELECT count(*) FROM pg_constraint WHERE conrelid = 'prescriptions'::regclass AND conname = 'prescriptions_status_check' AND contype = 'c'"));
        $this->assertSame(1, (int) $db->scalar("SELECT count(*) FROM pg_constraint WHERE conrelid = 'prescriptions'::regclass AND conname = 'prescriptions_signing_consistency_check' AND contype = 'c'"));
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

        $this->assertRejected('prescriptions_signing_consistency_check', fn () => $db->update("UPDATE prescriptions SET status = 'cancelled' WHERE prescription_id = ?", [$id]));
        $this->assertRejected('prescriptions_allergy_check_result_check', fn () => $db->update("UPDATE prescriptions SET allergy_check_result = 'blocked' WHERE prescription_id = ?", [$id]));
        $db->update("UPDATE prescriptions SET status = 'signed', signed_at = now(), signed_by_doctor_profile_id = ?, allergy_check_result = 'warning' WHERE prescription_id = ?", [
            '00000000-0000-0000-0000-000000000102',
            $id,
        ]);
        $this->assertSame('signed', $db->scalar('SELECT status FROM prescriptions WHERE prescription_id = ?', [$id]));
        $this->assertSame('warning', $db->scalar('SELECT allergy_check_result FROM prescriptions WHERE prescription_id = ?', [$id]));
    }

    public function test_signing_fields_are_consistent_with_prescription_status(): void
    {
        $this->insertGraph();
        $id = $this->insertPrescription();
        $db = DB::connection('hospital');
        $doctorId = '00000000-0000-0000-0000-000000000102';

        $this->assertRejected('prescriptions_signing_consistency_check', fn () => $db->update("UPDATE prescriptions SET status = 'signed' WHERE prescription_id = ?", [$id]));
        $this->assertRejected('prescriptions_signing_consistency_check', fn () => $db->update("UPDATE prescriptions SET status = 'signed', signed_at = now() WHERE prescription_id = ?", [$id]));
        $this->assertRejected('prescriptions_signing_consistency_check', fn () => $db->update("UPDATE prescriptions SET status = 'signed', signed_by_doctor_profile_id = ? WHERE prescription_id = ?", [$doctorId, $id]));
        $this->assertRejected('prescriptions_signing_consistency_check', fn () => $db->update('UPDATE prescriptions SET signed_at = now() WHERE prescription_id = ?', [$id]));
        $this->assertRejected('prescriptions_signing_consistency_check', fn () => $db->update('UPDATE prescriptions SET signed_by_doctor_profile_id = ? WHERE prescription_id = ?', [$doctorId, $id]));

        $db->update("UPDATE prescriptions SET status = 'signed', signed_at = now(), signed_by_doctor_profile_id = ? WHERE prescription_id = ?", [$doctorId, $id]);
        $this->assertSame('signed', $db->scalar('SELECT status FROM prescriptions WHERE prescription_id = ?', [$id]));
    }

    public function test_signed_prescriptions_cannot_be_updated_or_deleted(): void
    {
        $this->insertGraph();
        $id = $this->insertPrescription();
        $db = DB::connection('hospital');
        $db->update("UPDATE prescriptions SET status = 'signed', signed_at = now(), signed_by_doctor_profile_id = ? WHERE prescription_id = ?", [
            '00000000-0000-0000-0000-000000000102',
            $id,
        ]);

        $this->assertRejected('prescriptions_signed_immutable', fn () => $db->update('UPDATE prescriptions SET dose_amount = 250 WHERE prescription_id = ?', [$id]));
        $this->assertRejected('prescriptions_signed_immutable', fn () => $db->delete('DELETE FROM prescriptions WHERE prescription_id = ?', [$id]));
        $this->assertSame('signed', $db->scalar('SELECT status FROM prescriptions WHERE prescription_id = ?', [$id]));
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
        Artisan::call('migrate:rollback', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--step' => $this->hospitalRollbackSteps('2026_08_11_000900'), '--force' => true]);

        $this->assertFalse($this->tableExists('prescriptions'));
        $this->assertFalse($this->tableExists('doctor_schedules'));
        $this->assertTrue($this->tableExists('medications'));
        $this->assertSame(0, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM pg_proc WHERE proname = 'prevent_signed_prescription_mutation'"));
        $this->assertSame(0, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM pg_trigger t JOIN pg_class c ON c.oid = t.tgrelid WHERE c.relname = 'prescriptions' AND t.tgname = 'prescriptions_signed_immutable'"));

        Artisan::call('migrate', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->assertTrue($this->tableExists('prescriptions'));
        $this->assertSame(1, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM pg_proc WHERE proname = 'prevent_signed_prescription_mutation'"));
        $this->assertSame(1, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM pg_trigger t JOIN pg_class c ON c.oid = t.tgrelid WHERE c.relname = 'prescriptions' AND t.tgname = 'prescriptions_signed_immutable'"));
    }

    #[DataProvider('nonPositivePrescriptionValues')]
    public function test_non_positive_values_are_rejected_in_drafts_and_when_signing(string $column, int $value): void
    {
        $this->insertGraph();
        $id = $this->insertPrescription();
        $db = DB::connection('hospital');

        foreach (['draft', 'signed'] as $status) {
            try {
                $db->table('prescriptions')->where('prescription_id', $id)->update([
                    $column => $value,
                    'status' => $status,
                    'signed_at' => $status === 'signed' ? now() : null,
                    'signed_by_doctor_profile_id' => $status === 'signed' ? '00000000-0000-0000-0000-000000000102' : null,
                ]);
                $this->fail('Se esperaba CHECK para '.$column.' en '.$status);
            } catch (QueryException $error) {
                $this->assertSame('23514', $error->errorInfo[0]);
                $this->assertStringContainsString('prescriptions_'.$column.'_positive_check', $error->getMessage());
            }
            $this->assertSame('draft', $db->scalar('SELECT status FROM prescriptions WHERE prescription_id = ?', [$id]));
        }
    }

    public static function nonPositivePrescriptionValues(): array
    {
        return [
            'zero dose' => ['dose_amount', 0],
            'negative dose' => ['dose_amount', -5],
            'zero frequency' => ['frequency_times', 0],
            'negative frequency' => ['frequency_times', -1],
            'zero duration' => ['duration_value', 0],
            'negative duration' => ['duration_value', -1],
        ];
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
