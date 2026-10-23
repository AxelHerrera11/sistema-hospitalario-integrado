<?php

namespace Tests\Database\Hospital;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\NotasSoap\PdoLocalActorResolver;
use Illuminate\Auth\GenericUser;
use MicroHis\NotasSoap\Domain\Diagnosis;
use MicroHis\NotasSoap\Domain\SoapRuleViolation;
use MicroHis\NotasSoap\Domain\SoapSections;
use MicroHis\NotasSoap\Persistence\PdoAuditTrail;
use MicroHis\NotasSoap\Persistence\PdoCareAssignmentReader;
use MicroHis\NotasSoap\Persistence\PdoClinicalNoteStore;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\MedicationCatalog\CatalogTestIdentity;
use Tests\TestCase;

/** Adaptadores PDO de Notas SOAP contra el esquema real de la migración hospital (PostgreSQL), no contra el SQLite simplificado. */
#[Group('database-integrity')]
class NotasSoapPostgresTest extends TestCase
{
    private const DOCTOR = '11111111-1111-4111-8111-111111111111';
    private const DOCTOR_CENTRAL = '99999999-9999-4999-8999-999999999999';
    private const NURSE = '22222222-2222-4222-8222-222222222222';
    private const EPISODE = 'dddddddd-dddd-4ddd-8ddd-dddddddddddd';

    private PDO $pdo;
    private PdoClinicalNoteStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertTrue(extension_loaded('pdo_pgsql'));
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->seedCare();
        $this->pdo = DB::connection('hospital')->getPdo();
        $this->store = new PdoClinicalNoteStore($this->pdo, new PdoAuditTrail($this->pdo));
    }

    public function test_only_the_active_assigned_doctor_is_treating_through_doctor_profiles(): void
    {
        $reader = new PdoCareAssignmentReader($this->pdo);
        $this->assertTrue($reader->isTreating(self::DOCTOR, self::EPISODE));
        $this->assertFalse($reader->isTreating(self::NURSE, self::EPISODE));
        DB::connection('hospital')->update("UPDATE care_assignments SET status = 'closed', valid_to = now() + interval '1 second' WHERE care_episode_id = ?", [self::EPISODE]);
        usleep(1_200_000);
        $this->assertFalse($reader->isTreating(self::DOCTOR, self::EPISODE));
    }

    public function test_a_note_is_stored_in_the_patient_record_and_audited_without_clinical_text(): void
    {
        $id = $this->store->add(self::EPISODE, self::DOCTOR, new SoapSections('Tos', 'Fiebre', 'Neumonía', 'Amoxicilina'));
        $note = $this->store->find($id);
        $this->assertSame('draft', $note->status);
        $this->assertSame('Amoxicilina', $note->sections->plan);
        $this->assertSame('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', DB::connection('hospital')->scalar('SELECT medical_record_id FROM clinical_notes WHERE clinical_note_id = ?', [$id]));
        $audit = DB::connection('hospital')->selectOne("SELECT module, action, metadata::text AS metadata FROM local_access_audits WHERE resource_id = ?", [$id]);
        $this->assertSame(['notas_soap', 'nota_soap.registrada'], [$audit->module, $audit->action]);
        $this->assertStringNotContainsString('Amoxicilina', $audit->metadata);
    }

    public function test_a_diagnosis_is_stored_and_audited(): void
    {
        $id = $this->store->add(self::EPISODE, self::DOCTOR, new SoapSections('s', 'o', 'a', 'p'));
        $this->store->addDiagnosis($this->store->find($id), new Diagnosis('J18.9', 'primary'), 'Neumonía');
        $this->assertSame([['J18.9', 'primary']], array_map(static fn ($d) => [$d->code, $d->type], $this->store->find($id)->diagnoses));
        $this->assertSame(1, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM local_access_audits WHERE action = 'diagnostico.asociado'"));
    }

    public function test_without_an_open_medical_record_the_note_is_refused_and_nothing_is_saved(): void
    {
        DB::connection('hospital')->update("UPDATE medical_records SET status = 'closed', closed_at = now()");
        try {
            $this->store->add(self::EPISODE, self::DOCTOR, new SoapSections('s', 'o', 'a', 'p'));
            $this->fail('Debió rechazarse sin expediente abierto.');
        } catch (SoapRuleViolation $e) {
            $this->assertSame(['MEDICAL_RECORD_NOT_FOUND', 409], [$e->getMessage(), $e->status]);
        }
        $this->assertSame(0, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM clinical_notes'));
    }

    /** LÍMITE CONOCIDO R-06: la restricción única (medical_record_id, version_no) con version_no = 1 permite una sola nota SOAP por expediente. Cuando Javier la resuelva, esta prueba deberá invertirse. */
    public function test_known_limit_r06_a_second_note_in_the_same_record_is_rejected_by_the_database(): void
    {
        $this->store->add(self::EPISODE, self::DOCTOR, new SoapSections('s', 'o', 'a', 'p'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('persistencia_no_disponible');
        $this->store->add(self::EPISODE, self::DOCTOR, new SoapSections('s2', 'o', 'a', 'p'));
    }

    /** D-02 mitigado en el módulo: otra sesión firma la nota entre la lectura y la escritura y el adaptador lo detecta, revierte y no deja auditoría. */
    public function test_a_note_signed_between_read_and_write_is_refused_with_note_signed_and_nothing_is_saved(): void
    {
        $id = $this->store->add(self::EPISODE, self::DOCTOR, new SoapSections('s', 'o', 'a', 'p'));
        $note = $this->store->find($id);
        DB::connection('hospital')->update("UPDATE clinical_notes SET status = 'signed', signed_at = now() WHERE clinical_note_id = ?", [$id]);
        try {
            $this->store->addDiagnosis($note, new Diagnosis('J18.9', 'primary'), 'Neumonía');
            $this->fail('Debió rechazarse una nota firmada.');
        } catch (SoapRuleViolation $e) {
            $this->assertSame(['NOTE_SIGNED', 409], [$e->getMessage(), $e->status]);
        }
        $this->assertFalse($this->pdo->inTransaction());
        $this->assertSame(0, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM diagnoses'));
        $this->assertSame(0, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM local_access_audits WHERE action = 'diagnostico.asociado'"));
    }

    /** DEFECTO D-02 pendiente en la base (ASII-09): quien escriba SQL directo en diagnoses se salta la mitigación del módulo, porque el trigger no mira el estado de la nota. Cuando se corrija, el INSERT debe fallar. */
    public function test_known_defect_d02_the_database_still_accepts_a_direct_diagnosis_on_a_signed_note(): void
    {
        $id = $this->store->add(self::EPISODE, self::DOCTOR, new SoapSections('s', 'o', 'a', 'p'));
        DB::connection('hospital')->update("UPDATE clinical_notes SET status = 'signed', signed_at = now() WHERE clinical_note_id = ?", [$id]);
        DB::connection('hospital')->insert("INSERT INTO diagnoses (clinical_note_id, care_episode_id, author_user_ref_id, code, description, type, onset_date) VALUES (?, ?, ?, 'J18.9', 'Neumonía', 'primary', current_date)", [$id, self::EPISODE, self::DOCTOR]);
        $this->assertSame(1, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM diagnoses WHERE clinical_note_id = ?', [$id]));
    }

    public function test_the_central_user_resolves_to_the_active_local_reference_only(): void
    {
        $resolver = new PdoLocalActorResolver(fn () => $this->pdo);
        $this->assertSame(self::DOCTOR, $resolver->reference(self::DOCTOR_CENTRAL));
        $this->assertNull($resolver->reference('00000000-0000-4000-8000-000000000000'));
        $this->assertNull($resolver->reference("' OR 1=1 --"));
        DB::connection('hospital')->update("UPDATE local_user_refs SET status = 'inactive' WHERE local_user_ref_id = ?", [self::DOCTOR]);
        $this->assertNull($resolver->reference(self::DOCTOR_CENTRAL));
    }

    /** Recorrido real: token (identidad simulada) → usuario local → permisos RBAC reales → casos de uso → adaptadores PDO → PostgreSQL. */
    public function test_end_to_end_the_api_registers_and_reads_a_note_in_postgresql(): void
    {
        CatalogTestIdentity::install($this->app);
        CatalogTestIdentity::as('medico');
        CatalogTestIdentity::$user = new GenericUser(['id' => self::DOCTOR_CENTRAL]);
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
        $soap = ['subjective' => 'Tos', 'objective' => 'Fiebre 38', 'assessment' => 'Neumonía', 'plan' => 'Amoxicilina'];

        $id = $this->postJson('/api/v1/care-episodes/' . self::EPISODE . '/soap-notes', $soap)->assertCreated()->assertJsonPath('status', 'draft')->json('clinical_note_id');
        $this->getJson("/api/v1/soap-notes/$id")->assertOk()->assertJsonPath('sections.plan', 'Amoxicilina')->assertJsonPath('author_user_ref_id', self::DOCTOR);
        $this->assertSame(1, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM local_access_audits WHERE resource_id = ? AND actor_user_ref_id = ?", [$id, self::DOCTOR]));
        // Falta la tabla icd10_codes (entidad de ASII-09): asociar un diagnóstico falla seguro con 503, sin 500.
        $this->postJson("/api/v1/soap-notes/$id/diagnoses", ['code' => 'J18.9', 'type' => 'primary'])->assertStatus(503)->assertJsonPath('code', 'ICD10_UNAVAILABLE');
    }

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
