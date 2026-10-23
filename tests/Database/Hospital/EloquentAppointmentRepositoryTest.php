<?php

namespace Tests\Database\Hospital;

use App\Exceptions\AppointmentWriteException;
use App\Models\Appointment;
use App\Repositories\EloquentAppointmentRepository;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class EloquentAppointmentRepositoryTest extends TestCase
{
    private EloquentAppointmentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->insertGraph();
        $this->repository = new EloquentAppointmentRepository();
    }

    public function test_create_loads_the_generated_uuid_and_default_status(): void
    {
        $appointment = $this->repository->create($this->attributes('10:00', '11:00'));

        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertNotNull($appointment->getKey());
        $this->assertSame('appointment_id', $appointment->getKeyName());
        $this->assertSame(Appointment::STATUS_SCHEDULED, $appointment->status);
        $this->assertNotNull($appointment->created_at);

        $persisted = DB::connection('hospital')->scalar(
            'SELECT status FROM appointments WHERE appointment_id = ?',
            [$appointment->getKey()],
        );
        $this->assertSame(Appointment::STATUS_SCHEDULED, $persisted);
    }

    public function test_created_appointment_can_be_confirmed(): void
    {
        $appointment = $this->repository->create($this->attributes('10:00', '11:00'));

        $confirmed = $this->repository->changeStatus($appointment, Appointment::STATUS_CONFIRMED);

        $this->assertSame(Appointment::STATUS_CONFIRMED, $confirmed->status);
        $this->assertSame($appointment->getKey(), $confirmed->getKey());
    }

    public function test_overlapping_active_appointment_is_rejected_with_409(): void
    {
        $this->repository->create($this->attributes('10:00', '11:00'));

        try {
            $this->repository->create($this->attributes('10:30', '11:30'));
            $this->fail('Se esperaba que PostgreSQL rechazara el solape.');
        } catch (AppointmentWriteException $exception) {
            $this->assertSame(409, $exception->status);
            $this->assertSame('appointments_overlap', $exception->errorCode);
        }

        $this->assertSame(1, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM appointments'));
    }

    public function test_finalized_appointment_is_not_mutable(): void
    {
        $appointment = $this->repository->create($this->attributes('10:00', '11:00'));
        $appointment = $this->repository->changeStatus($appointment, Appointment::STATUS_CANCELLED);

        $this->expectException(AppointmentWriteException::class);

        $this->repository->changeStatus($appointment, Appointment::STATUS_CONFIRMED);
    }

    public function test_invalid_status_transition_is_rejected_with_422_and_keeps_data(): void
    {
        $appointment = $this->repository->create($this->attributes('10:00', '11:00'));
        $before = $this->repository->find($appointment->getKey());

        try {
            $this->repository->changeStatus($appointment, Appointment::STATUS_COMPLETED);
            $this->fail('Se esperaba que la transicion invalida fuera rechazada.');
        } catch (AppointmentWriteException $exception) {
            $this->assertSame(422, $exception->status);
            $this->assertSame('appointments_status_time_check', $exception->errorCode);
        }

        $after = $this->repository->find($appointment->getKey());
        $this->assertSame($before->getKey(), $after->getKey());
        $this->assertSame(Appointment::STATUS_SCHEDULED, $after->status);
        $persisted = DB::connection('hospital')->scalar(
            'SELECT status FROM appointments WHERE appointment_id = ?',
            [$appointment->getKey()],
        );
        $this->assertSame(Appointment::STATUS_SCHEDULED, $persisted);
    }

    public function test_update_preserves_appointment_id_and_status(): void
    {
        $appointment = $this->repository->create($this->attributes('10:00', '11:00'));
        $originalId = $appointment->getKey();
        $originalStatus = $appointment->status;

        $updated = $this->repository->find($originalId);
        $updated->reason = 'Reconsult';
        $result = $this->repository->update($appointment, ['reason' => 'Reconsult']);

        $this->assertSame($originalId, $result->getKey());
        $this->assertSame($originalStatus, $result->status);
        $persisted = DB::connection('hospital')->table('appointments')
            ->where('appointment_id', $originalId)
            ->first();
        $this->assertSame('Reconsult', (string) $persisted->reason);
        $this->assertSame($originalStatus, $persisted->status);
    }

    /**
     * @return array<string, string>
     */
    private function attributes(string $start, string $end): array
    {
        return [
            'local_patient_id' => '00000000-0000-0000-0000-000000000010',
            'doctor_profile_id' => '00000000-0000-0000-0000-000000000002',
            'specialty_id' => '00000000-0000-0000-0000-000000000003',
            'starts_at' => '2026-08-11 '.$start.':00+00',
            'ends_at' => '2026-08-11 '.$end.':00+00',
            'reason' => 'Consultation',
        ];
    }

    private function insertGraph(): void
    {
        $db = DB::connection('hospital');
        $db->insert("INSERT INTO local_user_refs (local_user_ref_id, central_user_id, membership_id, display_name, role_code, synced_at) VALUES ('00000000-0000-0000-0000-000000000001', gen_random_uuid(), gen_random_uuid(), 'Dr. Ada', 'physician', now())");
        $db->insert("INSERT INTO doctor_profiles (doctor_profile_id, local_user_ref_id, license_number, valid_from) VALUES ('00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000001', 'MED-H3', current_date - 1)");
        $db->insert("INSERT INTO specialties (specialty_id, code, name) VALUES ('00000000-0000-0000-0000-000000000003', 'CARD-H3', 'Cardiology')");
        $db->insert("INSERT INTO doctor_specialties (doctor_specialty_id, doctor_profile_id, specialty_id, valid_from) VALUES ('00000000-0000-0000-0000-000000000004', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000003', '2026-08-01')");
        $db->insert("INSERT INTO local_patients (local_patient_id, global_id, code, given_name, family_name, birth_date, sex) VALUES ('00000000-0000-0000-0000-000000000010', gen_random_uuid(), 'P-H3', 'Pat', 'Patient', '2000-01-01', 'female')");
    }
}
