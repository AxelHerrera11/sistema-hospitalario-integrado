<?php

declare(strict_types=1);

namespace Tests\Database\Hospital;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use MicroHis\Medicos\Persistence\PdoDoctorStore;
use MicroHis\Medicos\Persistence\PdoScheduleProvider;
use MicroHis\Medicos\Presentation\Request;
use MicroHis\Medicos\Presentation\Router;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Médicos y especialidades (ASII-04, semana 16) contra el esquema HOSPITAL real en PostgreSQL 16: ids uuid, auditoría H8,
 * atomicidad del alta, trigger de `doctor_schedules` y lectura de citas por intervalo y zona horaria.
 */
#[Group('database-integrity')]
final class DoctorsPostgresTest extends TestCase
{
    private const ACTOR = '00000000-0000-0000-0000-0000000000a1';
    private const ALTA = ['local_user_ref_id' => '00000000-0000-0000-0000-0000000000b1', 'license_number' => 'COL-1', 'specialty_code' => 'CARD', 'valid_from' => '2026-01-01'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertTrue(extension_loaded('pdo_pgsql'));
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        foreach ([self::ACTOR, self::ALTA['local_user_ref_id'], '00000000-0000-0000-0000-0000000000b2'] as $i => $ref) {
            DB::connection('hospital')->insert("INSERT INTO local_user_refs (local_user_ref_id, central_user_id, membership_id, display_name, role_code, synced_at) VALUES (?, gen_random_uuid(), gen_random_uuid(), ?, 'medico', now())", [$ref, "Usuario $i"]);
        }
        foreach (['CARD' => 'Cardiología', 'NEURO' => 'Neurología'] as $code => $name) {
            DB::connection('hospital')->insert("INSERT INTO specialties (code, name) VALUES (?, ?)", [$code, $name]);
        }
    }

    public function test_el_alta_crea_un_perfil_uuid_con_su_vinculo_y_su_auditoria(): void
    {
        $response = $this->send('POST', '/doctors', self::ALTA);

        $this->assertSame(201, $response['status']);
        $id = $response['body']['doctor_profile_id'];
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id);
        $this->assertSame('COL-1', $this->hospital()->scalar('SELECT license_number FROM doctor_profiles WHERE doctor_profile_id = ?', [$id]));
        $this->assertTrue((bool) $this->hospital()->scalar('SELECT is_primary FROM doctor_specialties WHERE doctor_profile_id = ?', [$id]));
        $audit = (array) $this->hospital()->selectOne('SELECT module, action, resource_type, resource_id, actor_user_ref_id FROM local_access_audits');
        $this->assertSame(['medicos', 'medicos.medico_registrado', 'doctor_profile', $id, self::ACTOR], array_values($audit));
    }

    public function test_una_licencia_con_espacios_y_minusculas_es_la_misma_licencia(): void
    {
        $this->send('POST', '/doctors', self::ALTA);

        $repeated = $this->send('POST', '/doctors', ['license_number' => ' col-1 ', 'local_user_ref_id' => '00000000-0000-0000-0000-0000000000b2'] + self::ALTA);

        $this->assertSame([409, 'LICENSE_ALREADY_REGISTERED'], [$repeated['status'], $repeated['body']['code']]);
        $this->assertSame(1, (int) $this->hospital()->scalar('SELECT count(*) FROM doctor_profiles'));
    }

    public function test_si_la_auditoria_falla_el_alta_se_revierte_entera(): void
    {
        $response = $this->send('POST', '/doctors', self::ALTA, actor: '00000000-0000-0000-0000-0000000000ff');

        $this->assertSame([500, 'DOCTOR_PERSISTENCE_ERROR'], [$response['status'], $response['body']['code']]);
        $this->assertSame([0, 0], [(int) $this->hospital()->scalar('SELECT count(*) FROM doctor_profiles'), (int) $this->hospital()->scalar('SELECT count(*) FROM doctor_specialties')]);
    }

    public function test_asociar_una_secundaria_y_rechazar_una_segunda_principal(): void
    {
        $id = $this->send('POST', '/doctors', self::ALTA)['body']['doctor_profile_id'];

        $secondary = $this->send('POST', "/doctors/$id/specialties", ['specialty_code' => 'NEURO', 'is_primary' => false, 'valid_from' => '2026-02-01']);
        $primary = $this->send('POST', "/doctors/$id/specialties", ['specialty_code' => 'NEURO', 'is_primary' => true, 'valid_from' => '2026-06-01']);

        $this->assertSame(201, $secondary['status']);
        $this->assertSame([409, 'PRIMARY_SPECIALTY_CONFLICT'], [$primary['status'], $primary['body']['code']]);
        $this->assertSame(2, (int) $this->hospital()->scalar('SELECT count(*) FROM doctor_specialties WHERE doctor_profile_id = ?', [$id]));
    }

    public function test_la_disponibilidad_descuenta_las_citas_por_intervalo_en_la_zona_del_hospital(): void
    {
        $id = $this->doctorWithSchedule();
        $this->appointment($id, '2026-10-19 08:15:00+00', '2026-10-19 08:45:00+00', 'confirmed');
        $this->appointment($id, '2026-10-19 09:00:00+00', '2026-10-19 09:30:00+00', 'cancelled');

        $utc = $this->send('GET', '/availability', query: ['specialty' => 'NEURO', 'date' => '2026-10-19']);
        $guatemala = $this->send('GET', '/availability', query: ['specialty' => 'NEURO', 'date' => '2026-10-19'], timezone: 'America/Guatemala');

        $this->assertSame(['09:00', '09:30'], $utc['body']['doctors'][0]['slots'], 'la cita 08:15-08:45 bloquea 08:00 y 08:30; la cancelada no cuenta');
        $this->assertSame(['08:00', '08:30', '09:00', '09:30'], $guatemala['body']['doctors'][0]['slots'], 'a las 08:15Z son las 02:15 en UTC-6: no estorba al horario local');
    }

    public function test_un_medico_sin_horario_se_informa_y_un_dia_sin_franjas_queda_vacio(): void
    {
        $withSchedule = $this->doctorWithSchedule();
        $this->send('POST', '/doctors', ['local_user_ref_id' => '00000000-0000-0000-0000-0000000000b2', 'license_number' => 'COL-2', 'specialty_code' => 'NEURO'] + self::ALTA);

        $monday = $this->send('GET', '/availability', query: ['specialty' => 'NEURO', 'date' => '2026-10-19'])['body']['doctors'];
        $tuesday = $this->send('GET', '/availability', query: ['specialty' => 'NEURO', 'date' => '2026-10-20'])['body']['doctors'];
        $byDoctor = fn (array $rows): array => array_column($rows, null, 'doctor_id');

        $this->assertSame('SCHEDULE_NOT_CONFIGURED', array_values(array_filter($monday, fn ($d) => $d['doctor_id'] !== $withSchedule))[0]['status']);
        $this->assertSame(['status' => 'OK', 'slots' => []], array_intersect_key($byDoctor($tuesday)[$withSchedule], ['status' => 1, 'slots' => 1]));
    }

    public function test_la_disponibilidad_usa_el_slot_minutes_del_horario(): void
    {
        $this->doctorWithSchedule(slotMinutes: 20);

        $slots = $this->send('GET', '/availability', query: ['specialty' => 'NEURO', 'date' => '2026-10-19'])['body']['doctors'][0]['slots'];

        $this->assertSame(['08:00', '08:20', '08:40', '09:00', '09:20', '09:40'], $slots, 'horario 08:00-10:00 con bloques de 20 minutos');
    }

    /** Médico con CARD principal y NEURO secundaria desde enero, y horario de lunes 08:00-10:00 en NEURO (el trigger exige el vínculo). */
    private function doctorWithSchedule(int $slotMinutes = 30): string
    {
        $id = $this->send('POST', '/doctors', self::ALTA)['body']['doctor_profile_id'];
        $this->send('POST', "/doctors/$id/specialties", ['specialty_code' => 'NEURO', 'is_primary' => false, 'valid_from' => '2026-01-01']);
        $this->hospital()->insert("INSERT INTO doctor_schedules (doctor_profile_id, specialty_id, weekday, start_minute, end_minute, slot_minutes, valid_from) VALUES (?, (SELECT specialty_id FROM specialties WHERE code = 'NEURO'), 1, 480, 600, ?, '2026-01-01')", [$id, $slotMinutes]);

        return $id;
    }

    private function appointment(string $doctor, string $start, string $end, string $status): void
    {
        $this->hospital()->insert("INSERT INTO local_patients (local_patient_id, global_id, code, given_name, family_name, birth_date, sex) VALUES (gen_random_uuid(), gen_random_uuid(), ?, 'Paciente', 'Ficticio', '1990-01-01', 'unknown')", [bin2hex(random_bytes(4))]);
        $this->hospital()->insert("INSERT INTO appointments (local_patient_id, doctor_profile_id, specialty_id, starts_at, ends_at, status, reason) VALUES ((SELECT local_patient_id FROM local_patients ORDER BY created_at DESC LIMIT 1), ?, (SELECT specialty_id FROM specialties WHERE code = 'NEURO'), ?, ?, ?, 'Consulta')", [$doctor, $start, $end, $status]);
    }

    /** @return array{status: int, body: array} */
    private function send(string $method, string $path, array $body = [], array $query = [], string $actor = self::ACTOR, string $timezone = 'UTC'): array
    {
        $pdo = $this->hospital()->getPdo();
        $store = new PdoDoctorStore($pdo, $timezone);
        $router = medicos_router($store, $store, new PdoScheduleProvider($pdo));
        $response = $router->dispatch(new Request($method, $path, ['X-User-ID' => $actor], $query, $body));

        return ['status' => $response['status'], 'body' => json_decode($response['body'], true)];
    }

    private function hospital(): \Illuminate\Database\Connection
    {
        return DB::connection('hospital');
    }
}
