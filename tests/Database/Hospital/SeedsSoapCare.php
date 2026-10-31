<?php

namespace Tests\Database\Hospital;

use Illuminate\Support\Facades\DB;

/** Un médico tratante con su expediente abierto, una enfermera sin asignación y un episodio ambulatorio, sobre el esquema real de la migración hospital. */
trait SeedsSoapCare
{
    private function seedCare(): void
    {
        $db = DB::connection('hospital');
        $db->unprepared(<<<'SQL'
            INSERT INTO local_user_refs (local_user_ref_id, central_user_id, membership_id, display_name, role_code, synced_at) VALUES
              ('11111111-1111-4111-8111-111111111111', '99999999-9999-4999-8999-999999999999', gen_random_uuid(), 'Dra. López', 'medico', now()),
              ('22222222-2222-4222-8222-222222222222', gen_random_uuid(), gen_random_uuid(), 'Enf. Ruiz', 'enfermeria', now());
            INSERT INTO doctor_profiles (doctor_profile_id, local_user_ref_id, license_number, valid_from) VALUES ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '11111111-1111-4111-8111-111111111111', 'LIC-1', '2020-01-01');
            INSERT INTO specialties (specialty_id, code, name) VALUES ('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'MG', 'Medicina general');
            INSERT INTO doctor_specialties (doctor_profile_id, specialty_id, valid_from) VALUES ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '2020-01-01');
            INSERT INTO local_patients (local_patient_id, global_id, code, given_name, family_name, birth_date, sex) VALUES ('cccccccc-cccc-4ccc-8ccc-cccccccccccc', gen_random_uuid(), 'P-1', 'Ana', 'Gómez', '1990-01-01', 'female');
            INSERT INTO care_episodes (care_episode_id, local_patient_id, type, started_at, reason) VALUES ('dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'outpatient', now() - interval '1 hour', 'Consulta');
            INSERT INTO care_assignments (care_episode_id, doctor_profile_id, specialty_id, assigned_by_user_ref_id, scope, reason, source, valid_from) VALUES ('dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '11111111-1111-4111-8111-111111111111', '{"clinical":true}', 'Tratante', 'episode', now() - interval '1 hour');
            INSERT INTO medical_records (medical_record_id, local_patient_id, record_number) VALUES ('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'HC-1');
        SQL);
    }
}
