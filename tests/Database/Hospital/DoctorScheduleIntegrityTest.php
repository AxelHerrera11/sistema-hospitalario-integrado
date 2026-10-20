<?php

namespace Tests\Database\Hospital;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class DoctorScheduleIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->insertGraph();
    }

    public function test_valid_schedule_is_accepted(): void
    {
        $this->insertSchedule('00000000-0000-0000-0000-0000000000c1', '00000000-0000-0000-0000-000000000002', 1, 480, 720, 30);
        $this->assertSame(1, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM doctor_schedules'));
        $this->assertSame('2026-10-01', DB::connection('hospital')->scalar("SELECT valid_from::text FROM doctor_schedules"));
    }

    public function test_overlapping_block_for_the_same_doctor_and_weekday_is_rejected(): void
    {
        $this->insertSchedule('00000000-0000-0000-0000-0000000000c2', '00000000-0000-0000-0000-000000000002', 1, 480, 720, 30);
        $this->assertRejected(
            'doctor_schedules_doctor_block_exclusion',
            fn () => $this->insertSchedule('00000000-0000-0000-0000-0000000000c3', '00000000-0000-0000-0000-000000000002', 1, 600, 840, 30),
        );
    }

    public function test_non_overlapping_blocks_are_accepted(): void
    {
        $this->insertSchedule('00000000-0000-0000-0000-0000000000c4', '00000000-0000-0000-0000-000000000002', 1, 480, 720, 30);
        $this->insertSchedule('00000000-0000-0000-0000-0000000000c5', '00000000-0000-0000-0000-000000000002', 1, 720, 960, 30);
        $this->assertSame(2, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM doctor_schedules'));
    }

    public function test_same_block_on_another_weekday_is_accepted(): void
    {
        $this->insertSchedule('00000000-0000-0000-0000-0000000000c6', '00000000-0000-0000-0000-000000000002', 1, 480, 720, 30);
        $this->insertSchedule('00000000-0000-0000-0000-0000000000c7', '00000000-0000-0000-0000-000000000002', 2, 480, 720, 30);
        $this->assertSame(2, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM doctor_schedules'));
    }

    public function test_same_block_for_another_doctor_is_accepted(): void
    {
        $this->insertSchedule('00000000-0000-0000-0000-0000000000c8', '00000000-0000-0000-0000-000000000002', 1, 480, 720, 30);
        $this->insertSchedule('00000000-0000-0000-0000-0000000000c9', '00000000-0000-0000-0000-000000000003', 1, 480, 720, 30);
        $this->assertSame(2, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM doctor_schedules'));
    }

    public function test_crossing_validity_periods_are_rejected(): void
    {
        $this->insertSchedule('00000000-0000-0000-0000-0000000000d1', '00000000-0000-0000-0000-000000000002', 1, 480, 720, 30, '2026-10-01', '2026-10-31');
        $this->assertRejected(
            'doctor_schedules_doctor_block_exclusion',
            fn () => $this->insertSchedule('00000000-0000-0000-0000-0000000000d2', '00000000-0000-0000-0000-000000000002', 1, 480, 720, 30, '2026-10-15', '2026-11-15'),
        );
    }

    public function test_inactive_blocks_do_not_block_each_other(): void
    {
        $this->insertSchedule('00000000-0000-0000-0000-0000000000d3', '00000000-0000-0000-0000-000000000002', 1, 480, 720, 30);
        $this->insertSchedule('00000000-0000-0000-0000-0000000000d4', '00000000-0000-0000-0000-000000000002', 1, 480, 720, 30, '2026-10-01', null, 'inactive');
        $this->assertSame(2, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM doctor_schedules'));
    }

    public function test_invalid_foreign_keys_are_rejected(): void
    {
        $this->assertRejected(
            'doctor_schedules_doctor_profile_id_fkey',
            fn () => $this->insertSchedule('00000000-0000-0000-0000-0000000000d5', '00000000-0000-0000-0000-0000000000ff', 1, 480, 720, 30),
        );
        $this->assertRejected(
            'doctor_schedules_specialty_id_fkey',
            fn () => $this->insertSchedule('00000000-0000-0000-0000-0000000000d6', '00000000-0000-0000-0000-000000000002', 1, 480, 720, 30, '2026-10-01', null, 'active', '00000000-0000-0000-0000-0000000000fe'),
        );
    }

    public function test_block_end_must_be_after_start(): void
    {
        $this->assertRejected(
            'doctor_schedules_block_check',
            fn () => $this->insertSchedule('00000000-0000-0000-0000-0000000000d7', '00000000-0000-0000-0000-000000000002', 1, 720, 480, 30),
        );
    }

    public function test_block_length_must_be_a_multiple_of_slot_minutes(): void
    {
        $this->assertRejected(
            'doctor_schedules_block_check',
            fn () => $this->insertSchedule('00000000-0000-0000-0000-0000000000d8', '00000000-0000-0000-0000-000000000002', 1, 480, 700, 30),
        );
    }

    public function test_invalid_validity_period_is_rejected(): void
    {
        $this->assertRejected(
            'doctor_schedules_valid_period',
            fn () => $this->insertSchedule('00000000-0000-0000-0000-0000000000d9', '00000000-0000-0000-0000-000000000002', 1, 480, 720, 30, '2026-10-31', '2026-10-01'),
        );
    }

    public function test_invalid_weekday_and_slot_are_rejected(): void
    {
        $this->assertRejected(
            'doctor_schedules_weekday_check',
            fn () => $this->insertSchedule('00000000-0000-0000-0000-0000000000da', '00000000-0000-0000-0000-000000000002', 8, 480, 720, 30),
        );
        $this->assertRejected(
            'doctor_schedules_slot_minutes_check',
            fn () => $this->insertSchedule('00000000-0000-0000-0000-0000000000db', '00000000-0000-0000-0000-000000000002', 1, 480, 720, 3),
        );
    }

    public function test_rollback_removes_the_table_and_keeps_the_previous_slices(): void
    {
        $this->assertTrue($this->tableExists('doctor_schedules'));
        $this->assertTrue($this->tableExists('bed_stays'));

        Artisan::call('migrate:rollback', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--step' => 1, '--force' => true]);

        $this->assertFalse($this->tableExists('doctor_schedules'));
        $this->assertTrue($this->tableExists('bed_stays'));
        $this->assertTrue($this->tableExists('wards'));
    }

    private function insertSchedule(
        string $id,
        string $doctorProfileId,
        int $weekday,
        int $startMinute,
        int $endMinute,
        int $slotMinutes,
        string $validFrom = '2026-10-01',
        ?string $validTo = null,
        string $status = 'active',
        string $specialtyId = '00000000-0000-0000-0000-000000000001',
    ): void {
        DB::connection('hospital')->insert(
            'INSERT INTO doctor_schedules (doctor_schedule_id, doctor_profile_id, specialty_id, weekday, start_minute, end_minute, slot_minutes, valid_from, valid_to, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$id, $doctorProfileId, $specialtyId, $weekday, $startMinute, $endMinute, $slotMinutes, $validFrom, $validTo, $status],
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
        return DB::connection('hospital')->selectOne(
            "SELECT to_regclass('public.{$table}') IS NOT NULL AS present",
        )->present === true || DB::connection('hospital')->scalar("SELECT to_regclass('public.{$table}')") !== null;
    }

    private function insertGraph(): void
    {
        $db = DB::connection('hospital');
        $db->insert("INSERT INTO local_user_refs (local_user_ref_id, central_user_id, membership_id, display_name, role_code, status, synced_at) VALUES ('00000000-0000-0000-0000-0000000000f1', '00000000-0000-0000-0000-0000000000f2', '00000000-0000-0000-0000-0000000000f3', 'Medico de prueba', 'medico', 'active', now())");
        $db->insert("INSERT INTO local_user_refs (local_user_ref_id, central_user_id, membership_id, display_name, role_code, status, synced_at) VALUES ('00000000-0000-0000-0000-0000000000f4', '00000000-0000-0000-0000-0000000000f5', '00000000-0000-0000-0000-0000000000f6', 'Segundo medico de prueba', 'medico', 'active', now())");
        $db->insert("INSERT INTO specialties (specialty_id, code, name) VALUES ('00000000-0000-0000-0000-000000000001', 'MED-GEN', 'Medicina general')");
        $db->insert("INSERT INTO doctor_profiles (doctor_profile_id, local_user_ref_id, license_number, valid_from) VALUES ('00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-0000000000f1', 'LIC-0001', '2026-01-01')");
        $db->insert("INSERT INTO doctor_profiles (doctor_profile_id, local_user_ref_id, license_number, valid_from) VALUES ('00000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-0000000000f4', 'LIC-0002', '2026-01-01')");
    }
}
