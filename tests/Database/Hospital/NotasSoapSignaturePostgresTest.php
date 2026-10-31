<?php

namespace Tests\Database\Hospital;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use MicroHis\NotasSoap\Application\AuditTrail;
use MicroHis\NotasSoap\Domain\Diagnosis;
use MicroHis\NotasSoap\Domain\SoapRuleViolation;
use MicroHis\NotasSoap\Domain\SoapSections;
use MicroHis\NotasSoap\Persistence\PdoAuditTrail;
use MicroHis\NotasSoap\Persistence\PdoClinicalNoteStore;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/** Firma de la nota (UC-SOAP-05) contra el esquema real de la migración hospital: restricciones y triggers de PostgreSQL, no el SQLite simplificado. */
#[Group('database-integrity')]
class NotasSoapSignaturePostgresTest extends TestCase
{
    use SeedsSoapCare;

    private const DOCTOR = '11111111-1111-4111-8111-111111111111';
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

    private function draftWithPrimaryDiagnosis(PdoClinicalNoteStore $store): string
    {
        $id = $store->add(self::EPISODE, self::DOCTOR, new SoapSections('Tos', 'Fiebre', 'Neumonía', 'Amoxicilina'));
        $store->addDiagnosis($store->find($id), new Diagnosis('J18.9', 'primary'), 'Neumonía');

        return $id;
    }

    public function test_signing_stores_the_signature_and_audits_it_without_clinical_text(): void
    {
        $id = $this->draftWithPrimaryDiagnosis($this->store);
        $this->store->sign($this->store->find($id));

        $row = DB::connection('hospital')->selectOne('SELECT status, signed_at FROM clinical_notes WHERE clinical_note_id = ?', [$id]);
        $this->assertSame('signed', $row->status);
        $this->assertNotNull($row->signed_at);
        $audit = DB::connection('hospital')->selectOne("SELECT module, actor_user_ref_id, metadata::text AS metadata FROM local_access_audits WHERE action = 'nota_soap.firmada' AND resource_id = ?", [$id]);
        $this->assertSame(['notas_soap', self::DOCTOR], [$audit->module, $audit->actor_user_ref_id]);
        $this->assertSame(['version_no' => 1, 'primary_code' => 'J18.9', 'diagnosis_codes' => ['J18.9']], json_decode($audit->metadata, true));
        $this->assertStringNotContainsString('Amoxicilina', $audit->metadata);
    }

    public function test_the_database_keeps_a_signed_note_immutable(): void
    {
        $id = $this->draftWithPrimaryDiagnosis($this->store);
        $this->store->sign($this->store->find($id));

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('clinical_notes_signed_immutable');
        DB::connection('hospital')->update("UPDATE clinical_notes SET content = '{}' WHERE clinical_note_id = ?", [$id]);
    }

    public function test_signing_twice_is_a_409_and_leaves_a_single_audit(): void
    {
        $id = $this->draftWithPrimaryDiagnosis($this->store);
        $note = $this->store->find($id);
        $this->store->sign($note);
        try {
            $this->store->sign($note);
            $this->fail('Debió rechazarse la segunda firma.');
        } catch (SoapRuleViolation $e) {
            $this->assertSame(['NOTE_SIGNED', 409], [$e->getMessage(), $e->status]);
        }
        $this->assertFalse($this->pdo->inTransaction());
        $this->assertSame(1, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM local_access_audits WHERE action = 'nota_soap.firmada'"));
    }

    /** Nunca se firma sin traza: si la bitácora falla dentro de la transacción, la nota sigue en borrador y sin signed_at. */
    public function test_a_failing_audit_rolls_the_signature_back(): void
    {
        $id = $this->draftWithPrimaryDiagnosis($this->store);
        $audit = new class implements AuditTrail {
            public function record(string $actorId, string $action, string $resourceType, string $resourceId, string $episodeId, array $metadata = []): void
            {
                throw new \RuntimeException('auditoria_no_disponible');
            }
        };
        $store = new PdoClinicalNoteStore($this->pdo, $audit);
        try {
            $store->sign($store->find($id));
            $this->fail('Debió fallar al no poder auditar.');
        } catch (\RuntimeException $e) {
            $this->assertSame('persistencia_no_disponible', $e->getMessage());
        }
        $this->assertFalse($this->pdo->inTransaction());
        $row = DB::connection('hospital')->selectOne('SELECT status, signed_at FROM clinical_notes WHERE clinical_note_id = ?', [$id]);
        $this->assertSame(['draft', null], [$row->status, $row->signed_at]);
    }

    /** Si el médico pierde la asignación activa, la base misma impide la firma (clinical_notes_author_assignment_trigger) y el módulo informa un error de persistencia, no una firma a medias. */
    public function test_losing_the_active_assignment_before_signing_leaves_the_note_in_draft(): void
    {
        $id = $this->draftWithPrimaryDiagnosis($this->store);
        $note = $this->store->find($id);
        DB::connection('hospital')->update("UPDATE care_assignments SET status = 'closed', valid_to = now() + interval '1 second' WHERE care_episode_id = ?", [self::EPISODE]);
        usleep(1_200_000);
        try {
            $this->store->sign($note);
            $this->fail('La base debió rechazar la firma sin asignación activa.');
        } catch (\RuntimeException $e) {
            $this->assertSame('persistencia_no_disponible', $e->getMessage());
        }
        $this->assertSame('draft', DB::connection('hospital')->scalar('SELECT status FROM clinical_notes WHERE clinical_note_id = ?', [$id]));
        $this->assertSame(0, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM local_access_audits WHERE action = 'nota_soap.firmada'"));
    }
}
