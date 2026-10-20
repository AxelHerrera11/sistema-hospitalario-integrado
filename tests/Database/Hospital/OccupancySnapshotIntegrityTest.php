<?php

namespace Tests\Database\Hospital;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class OccupancySnapshotIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->insertGraph();
    }

    public function test_bed_is_occupied_only_inside_the_stay_interval(): void
    {
        $db = DB::connection('hospital');
        $db->insert("INSERT INTO bed_stays (bed_stay_id, bed_id, care_episode_id, assigned_by_user_ref_id, started_at, ended_at, reason) VALUES ('00000000-0000-0000-0000-0000000000a1', '00000000-0000-0000-0000-000000000014', '00000000-0000-0000-0000-000000000007', '00000000-0000-0000-0000-000000000001', '2026-10-10 08:00:00+00', '2026-10-12 08:00:00+00', 'Estancia')");

        $this->assertSame(1, $this->occupiedBedsAt('2026-10-10 08:00:00+00'), 'La cama esta ocupada justo al iniciar el intervalo.');
        $this->assertSame(1, $this->occupiedBedsAt('2026-10-11 08:00:00+00'), 'La cama esta ocupada dentro del intervalo.');
        $this->assertSame(0, $this->occupiedBedsAt('2026-10-12 08:00:00+00'), 'Al terminar el intervalo la cama esta libre.');
        $this->assertSame(0, $this->occupiedBedsAt('2026-10-09 23:59:59+00'), 'Antes de iniciar el intervalo la cama esta libre.');
        $this->assertSame(0, $this->occupiedBedsAt('2026-10-20 08:00:00+00'), 'Despues del intervalo la cama esta libre.');
    }

    public function test_open_stay_keeps_the_bed_occupied_without_end_date(): void
    {
        $db = DB::connection('hospital');
        $db->insert("INSERT INTO bed_stays (bed_stay_id, bed_id, care_episode_id, assigned_by_user_ref_id, started_at, reason) VALUES ('00000000-0000-0000-0000-0000000000a2', '00000000-0000-0000-0000-000000000015', '00000000-0000-0000-0000-000000000008', '00000000-0000-0000-0000-000000000001', '2026-10-15 08:00:00+00', 'Estancia abierta')");

        $this->assertSame(1, $this->occupiedBedsAt('2026-10-16 08:00:00+00'));
        $this->assertSame(1, $this->occupiedBedsAt('2027-01-01 00:00:00+00'), 'Una estancia sin ended_at sigue abierta indefinidamente.');
    }

    public function test_operational_status_does_not_change_the_interval_derived_occupancy(): void
    {
        $db = DB::connection('hospital');
        $db->insert("INSERT INTO bed_stays (bed_stay_id, bed_id, care_episode_id, assigned_by_user_ref_id, started_at, ended_at, reason) VALUES ('00000000-0000-0000-0000-0000000000a3', '00000000-0000-0000-0000-000000000016', '00000000-0000-0000-0000-000000000007', '00000000-0000-0000-0000-000000000001', '2026-10-10 08:00:00+00', '2026-10-12 08:00:00+00', 'Estancia')");

        $db->update("UPDATE beds SET status = 'maintenance' WHERE bed_id = '00000000-0000-0000-0000-000000000016'");
        $this->assertSame(1, $this->occupiedBedsAt('2026-10-11 08:00:00+00'), 'El estado operativo no borra una estancia vigente.');
        $this->assertSame(1, $this->inconsistentBedsAt('2026-10-11 08:00:00+00'), 'Cama en mantenimiento con estancia vigente es inconsistencia.');

        $db->update("UPDATE beds SET status = 'occupied' WHERE bed_id = '00000000-0000-0000-0000-000000000015'");
        $this->assertSame(2, $this->inconsistentBedsAt('2026-10-11 08:00:00+00'), 'Cama ocupada sin estancia y cama en mantenimiento con estancia: dos inconsistencias.');
        $this->assertSame(1, $this->occupiedBedsAt('2026-10-11 08:00:00+00'), 'Sin estancia vigente la cama no esta ocupada.');
    }

    public function test_two_active_stays_on_the_same_bed_are_rejected(): void
    {
        $db = DB::connection('hospital');
        $db->insert("INSERT INTO bed_stays (bed_stay_id, bed_id, care_episode_id, assigned_by_user_ref_id, started_at, reason) VALUES ('00000000-0000-0000-0000-0000000000b1', '00000000-0000-0000-0000-000000000014', '00000000-0000-0000-0000-000000000007', '00000000-0000-0000-0000-000000000001', '2026-10-10 08:00:00+00', 'Estancia A')");

        $this->expectException(QueryException::class);
        $db->insert("INSERT INTO bed_stays (bed_stay_id, bed_id, care_episode_id, assigned_by_user_ref_id, started_at, reason) VALUES ('00000000-0000-0000-0000-0000000000b2', '00000000-0000-0000-0000-000000000014', '00000000-0000-0000-0000-000000000010', '00000000-0000-0000-0000-000000000001', '2026-10-11 08:00:00+00', 'Estancia B')");
    }

    public function test_stay_must_lie_inside_the_care_episode_period(): void
    {
        $db = DB::connection('hospital');
        $this->expectException(QueryException::class);
        $db->insert("INSERT INTO bed_stays (bed_stay_id, bed_id, care_episode_id, assigned_by_user_ref_id, started_at, reason) VALUES ('00000000-0000-0000-0000-0000000000c1', '00000000-0000-0000-0000-000000000015', '00000000-0000-0000-0000-000000000010', '00000000-0000-0000-0000-000000000001', '2020-01-01 08:00:00+00', 'Estancia fuera del episodio')");
    }

    public function test_occupancy_counters_ignore_beds_out_of_service(): void
    {
        $db = DB::connection('hospital');
        $db->insert("INSERT INTO beds (bed_id, ward_id, code, type, status) VALUES ('00000000-0000-0000-0000-0000000000d1', '00000000-0000-0000-0000-000000000013', 'BED-H3-D1', 'standard', 'maintenance')");
        $db->insert("INSERT INTO beds (bed_id, ward_id, code, type, status) VALUES ('00000000-0000-0000-0000-0000000000d2', '00000000-0000-0000-0000-000000000013', 'BED-H3-D2', 'standard', 'inactive')");

        $counters = $this->countersAt('2026-10-11 08:00:00+00');
        $this->assertSame(5, $counters['total_beds']);
        $this->assertSame(2, $counters['out_of_service']);
        $this->assertSame(3, $counters['operational_beds']);
        $this->assertSame(0, $counters['occupied']);
    }

    private function occupiedBedsAt(string $cutoff): int
    {
        return (int) DB::connection('hospital')->scalar(
            'SELECT count(DISTINCT b.bed_id) FROM beds b
             WHERE EXISTS (SELECT 1 FROM bed_stays s WHERE s.bed_id = b.bed_id
                           AND s.started_at <= :cutoff AND (s.ended_at IS NULL OR s.ended_at > :cutoff))',
            ['cutoff' => $cutoff],
        );
    }

    private function inconsistentBedsAt(string $cutoff): int
    {
        return (int) DB::connection('hospital')->scalar(
            "SELECT count(*) FROM beds b
             WHERE (b.status = 'occupied') <> EXISTS (SELECT 1 FROM bed_stays s WHERE s.bed_id = b.bed_id
                           AND s.started_at <= :cutoff AND (s.ended_at IS NULL OR s.ended_at > :cutoff))",
            ['cutoff' => $cutoff],
        );
    }

    private function countersAt(string $cutoff): array
    {
        $row = DB::connection('hospital')->selectOne(
            "SELECT count(*) AS total_beds,
                    count(*) FILTER (WHERE b.status IN ('maintenance', 'inactive')) AS out_of_service,
                    count(*) FILTER (WHERE b.status NOT IN ('maintenance', 'inactive')) AS operational_beds,
                    count(*) FILTER (WHERE b.status NOT IN ('maintenance', 'inactive') AND EXISTS (
                        SELECT 1 FROM bed_stays s WHERE s.bed_id = b.bed_id
                          AND s.started_at <= :cutoff AND (s.ended_at IS NULL OR s.ended_at > :cutoff))) AS occupied
             FROM beds b",
            ['cutoff' => $cutoff],
        );

        return [
            'total_beds' => (int) $row->total_beds,
            'out_of_service' => (int) $row->out_of_service,
            'operational_beds' => (int) $row->operational_beds,
            'occupied' => (int) $row->occupied,
        ];
    }

    private function insertGraph(): void
    {
        $db = DB::connection('hospital');
        $db->insert("INSERT INTO local_user_refs (local_user_ref_id, central_user_id, membership_id, display_name, role_code, status, synced_at) VALUES ('00000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-0000000000f1', '00000000-0000-0000-0000-0000000000f2', 'Usuario de prueba', 'enfermeria', 'active', now())");
        $db->insert("INSERT INTO local_patients (local_patient_id, global_id, code, given_name, family_name, birth_date, sex) VALUES ('00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-0000000000f3', 'PAC-H3-001', 'Ana', 'Prueba', '1985-04-12', 'female')");
        $db->insert("INSERT INTO wards (ward_id, code, name, location) VALUES ('00000000-0000-0000-0000-000000000013', 'WARD-H3', 'Sala H3', 'Nivel 2')");
        $db->insert("INSERT INTO beds (bed_id, ward_id, code, type) VALUES ('00000000-0000-0000-0000-000000000014', '00000000-0000-0000-0000-000000000013', 'BED-H3A', 'standard')");
        $db->insert("INSERT INTO beds (bed_id, ward_id, code, type) VALUES ('00000000-0000-0000-0000-000000000015', '00000000-0000-0000-0000-000000000013', 'BED-H3B', 'standard')");
        $db->insert("INSERT INTO beds (bed_id, ward_id, code, type) VALUES ('00000000-0000-0000-0000-000000000016', '00000000-0000-0000-0000-000000000013', 'BED-H3C', 'standard')");

        foreach (range(7, 11) as $index) {
            $open = $index <= 8;
            $db->insert(
                "INSERT INTO care_episodes (care_episode_id, local_patient_id, type, status, started_at, ended_at, reason) VALUES (?, '00000000-0000-0000-0000-000000000002', 'inpatient', ?, '2026-10-01 00:00:00+00', ?, 'Episodio de prueba')",
                [
                    sprintf('00000000-0000-0000-0000-%012d', $index),
                    $open ? 'open' : 'closed',
                    $open ? null : '2026-10-28 00:00:00+00',
                ],
            );
        }
    }
}
