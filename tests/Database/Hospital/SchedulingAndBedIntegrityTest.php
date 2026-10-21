<?php

namespace Tests\Database\Hospital;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class SchedulingAndBedIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
    }

    public function test_h3_inventory_is_local_and_has_no_admissions_table(): void
    {
        foreach (['appointments', 'wards', 'beds', 'bed_stays'] as $table) {
            $this->assertTrue($this->tableExists($table));
        }
        $this->assertFalse($this->tableExists('admissions'));
        $this->assertSame('uuid', $this->columnType('appointments', 'appointment_id'));
        $this->assertSame('timestamp with time zone', $this->columnType('appointments', 'starts_at'));
        $this->assertSame('uuid', $this->columnType('bed_stays', 'care_episode_id'));
        $this->assertSame(0, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM information_schema.table_constraints WHERE table_name IN ('appointments', 'wards', 'beds', 'bed_stays') AND constraint_type = 'FOREIGN KEY' AND constraint_name ILIKE '%central%'"));
    }

    public function test_appointments_require_specialty_and_only_scheduled_or_confirmed_exclude_overlaps(): void
    {
        $db = DB::connection('hospital');
        $this->insertGraph();
        $db->insert($this->appointmentSql('020', '10:00', '11:00'));
        $db->insert($this->appointmentSql('021', '11:00', '12:00', 'confirmed'));
        $this->assertRejected('appointments_doctor_active_period_exclusion', fn () => $db->insert($this->appointmentSql('022', '10:30', '11:30', 'confirmed')));
        $db->insert($this->appointmentSql('023', '10:30', '11:30', 'cancelled'));
        $this->assertRejected('appointments_specialty_trigger', fn () => $db->insert($this->appointmentSql('024', '13:00', '14:00', 'scheduled', '00000000-0000-0000-0000-000000000008')));
        $this->assertRejected('appointments_status_check', fn () => $db->insert($this->appointmentSql('025', '13:00', '14:00', 'checked_in')));
        $this->assertRejected('appointments_status_time_check', fn () => $db->insert($this->appointmentSql('026', '14:00', '13:00')));
    }

    public function test_ward_and_bed_catalogs_enforce_codes_and_status_but_not_occupancy(): void
    {
        $db = DB::connection('hospital');
        $this->insertGraph();
        $this->assertRejected('wards_code_unique', fn () => $db->insert("INSERT INTO wards (code, name, location) VALUES ('WARD-H3', 'Duplicate', 'Level 2')"));
        $this->assertRejected('beds_ward_code_unique', fn () => $db->insert("INSERT INTO beds (ward_id, code, type) VALUES ('00000000-0000-0000-0000-000000000014', 'BED-H3A', 'standard')"));
        $this->assertRejected('beds_status_check', fn () => $db->insert("INSERT INTO beds (ward_id, code, type, status) VALUES ('00000000-0000-0000-0000-000000000014', 'BED-H3C', 'standard', 'unknown')"));
        $db->insert($this->bedStaySql('030', '00000000-0000-0000-0000-000000000016', '10:00', null));
        $db->update("UPDATE beds SET status = 'maintenance' WHERE bed_id = '00000000-0000-0000-0000-000000000016'");
        $db->insert("INSERT INTO wards (ward_id, code, name, location) VALUES ('00000000-0000-0000-0000-000000000019', 'WARD-H3-EMPTY', 'Empty', 'Level 2')");
        $db->insert("INSERT INTO beds (bed_id, ward_id, code, type) VALUES ('00000000-0000-0000-0000-000000000027', '00000000-0000-0000-0000-000000000019', 'BED-H3-EMPTY', 'standard')");
        $db->delete("DELETE FROM beds WHERE bed_id = '00000000-0000-0000-0000-000000000027'");
        $db->delete("DELETE FROM wards WHERE ward_id = '00000000-0000-0000-0000-000000000019'");
    }

    public function test_bed_stays_enforce_bounds_open_episode_uniqueness_and_bed_exclusion(): void
    {
        $db = DB::connection('hospital');
        $this->insertGraph();
        $db->insert($this->bedStaySql('030', '00000000-0000-0000-0000-000000000016', '10:00', '11:00'));
        $db->insert($this->bedStaySql('031', '00000000-0000-0000-0000-000000000016', '11:00', '12:00'));
        $this->assertRejected('bed_stays_bed_active_period_exclusion', fn () => $db->insert($this->bedStaySql('032', '00000000-0000-0000-0000-000000000016', '10:30', '11:30')));
        $db->insert($this->bedStaySql('033', '00000000-0000-0000-0000-000000000016', '13:00', null));
        $this->assertRejected('bed_stays_open_care_episode_unique', fn () => $db->insert($this->bedStaySql('034', '00000000-0000-0000-0000-000000000018', '13:00', null)));
        $this->assertRejected('bed_stays_episode_period_trigger', fn () => $db->insert($this->bedStaySql('035', '00000000-0000-0000-0000-000000000018', '08:00', '09:30')));
        $db->update("UPDATE care_episodes SET status = 'closed', ended_at = '2026-08-11 12:00+00' WHERE care_episode_id = '00000000-0000-0000-0000-000000000017'");
        $this->assertRejected('bed_stays_episode_period_trigger', fn () => $db->insert($this->bedStaySql('036', '00000000-0000-0000-0000-000000000018', '13:00', null, '00000000-0000-0000-0000-000000000017')));
    }

    public function test_h3_rollback_removes_and_recreates_constraints(): void
    {
        Artisan::call('migrate:rollback', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--step' => 6, '--force' => true]);
        $this->assertFalse($this->tableExists('appointments'));
        $this->assertFalse($this->tableExists('bed_stays'));
        Artisan::call('migrate', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->assertSame(1, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM pg_constraint WHERE conname = 'appointments_doctor_active_period_exclusion'"));
        $this->assertSame(1, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM pg_constraint WHERE conname = 'bed_stays_bed_active_period_exclusion'"));
    }

    private function insertGraph(): void
    {
        $db = DB::connection('hospital');
        $db->insert("INSERT INTO local_user_refs (local_user_ref_id, central_user_id, membership_id, display_name, role_code, synced_at) VALUES ('00000000-0000-0000-0000-000000000001', gen_random_uuid(), gen_random_uuid(), 'Dr. Ada', 'physician', now())");
        $db->insert("INSERT INTO doctor_profiles (doctor_profile_id, local_user_ref_id, license_number, valid_from) VALUES ('00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000001', 'MED-H3', current_date - 1)");
        $db->insert("INSERT INTO specialties (specialty_id, code, name) VALUES ('00000000-0000-0000-0000-000000000003', 'CARD-H3', 'Cardiology'), ('00000000-0000-0000-0000-000000000008', 'ORTH-H3', 'Orthopedics')");
        $db->insert("INSERT INTO doctor_specialties (doctor_specialty_id, doctor_profile_id, specialty_id, valid_from) VALUES ('00000000-0000-0000-0000-000000000004', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000003', '2026-08-01')");
        $db->insert("INSERT INTO local_patients (local_patient_id, global_id, code, given_name, family_name, birth_date, sex) VALUES ('00000000-0000-0000-0000-000000000010', gen_random_uuid(), 'P-H3', 'Pat', 'Patient', '2000-01-01', 'female')");
        $db->insert("INSERT INTO care_episodes (care_episode_id, local_patient_id, type, started_at, reason) VALUES ('00000000-0000-0000-0000-000000000015', '00000000-0000-0000-0000-000000000010', 'inpatient', '2026-08-11 09:00+00', 'Admission'), ('00000000-0000-0000-0000-000000000017', '00000000-0000-0000-0000-000000000010', 'inpatient', '2026-08-11 09:00+00', 'Second episode')");
        $db->insert("INSERT INTO wards (ward_id, code, name, location) VALUES ('00000000-0000-0000-0000-000000000014', 'WARD-H3', 'Ward H3', 'Level 1')");
        $db->insert("INSERT INTO beds (bed_id, ward_id, code, type) VALUES ('00000000-0000-0000-0000-000000000016', '00000000-0000-0000-0000-000000000014', 'BED-H3A', 'standard'), ('00000000-0000-0000-0000-000000000018', '00000000-0000-0000-0000-000000000014', 'BED-H3B', 'standard')");
    }

    private function appointmentSql(string $id, string $start, string $end, string $status = 'scheduled', string $specialty = '00000000-0000-0000-0000-000000000003'): string
    {
        return "INSERT INTO appointments (appointment_id, local_patient_id, doctor_profile_id, specialty_id, starts_at, ends_at, status, reason) VALUES ('00000000-0000-0000-0000-000000000{$id}', '00000000-0000-0000-0000-000000000010', '00000000-0000-0000-0000-000000000002', '{$specialty}', '2026-08-11 {$start}+00', '2026-08-11 {$end}+00', '{$status}', 'Consultation')";
    }

    private function bedStaySql(string $id, string $bed, string $start, ?string $end, string $episode = '00000000-0000-0000-0000-000000000015'): string
    {
        return "INSERT INTO bed_stays (bed_stay_id, care_episode_id, bed_id, assigned_by_user_ref_id, started_at, ended_at, reason) VALUES ('00000000-0000-0000-0000-000000000{$id}', '{$episode}', '{$bed}', '00000000-0000-0000-0000-000000000001', '2026-08-11 {$start}+00', ".($end === null ? 'NULL' : "'2026-08-11 {$end}+00'").", 'Occupancy')";
    }

    private function tableExists(string $table): bool
    {
        return (bool) DB::connection('hospital')->scalar('SELECT to_regclass(?) IS NOT NULL', ['public.'.$table]);
    }

    private function columnType(string $table, string $column): ?string
    {
        return DB::connection('hospital')->scalar('SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = ?', [$table, $column]);
    }

    private function assertRejected(string $expected, Closure $statement): void
    {
        try {
            $statement();
            $this->fail('Expected PostgreSQL to reject the invalid write.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString($expected, $exception->getMessage());
        }
    }
}
