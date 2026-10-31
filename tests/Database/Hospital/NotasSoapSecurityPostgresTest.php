<?php

namespace Tests\Database\Hospital;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\MedicationCatalog\CatalogTestIdentity;
use Tests\TestCase;

/** Semana 17: amenazas de la matriz de Notas SOAP probadas por HTTP contra los adaptadores PDO reales y PostgreSQL. */
#[Group('database-integrity')]
class NotasSoapSecurityPostgresTest extends TestCase
{
    use SeedsSoapCare;

    private const DOCTOR = '11111111-1111-4111-8111-111111111111';
    private const DOCTOR_CENTRAL = '99999999-9999-4999-8999-999999999999';
    private const EPISODE = 'dddddddd-dddd-4ddd-8ddd-dddddddddddd';
    private const SOAP = ['subjective' => 'Tos seca', 'objective' => 'Fiebre 38', 'assessment' => 'Neumonía', 'plan' => 'Amoxicilina'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertTrue(extension_loaded('pdo_pgsql'));
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->seedCare();
        CatalogTestIdentity::install($this->app);
        $this->actAs('medico', self::DOCTOR_CENTRAL);
    }

    public function test_t1_sql_injection_in_ids_and_body_changes_nothing_and_does_not_leak_the_database(): void
    {
        $id = $this->postJson('/api/v1/care-episodes/' . self::EPISODE . '/soap-notes', self::SOAP)->json('clinical_note_id');
        foreach (["' OR '1'='1", '1; DROP TABLE clinical_notes; --', "{$id}' OR 1=1 --"] as $payload) {
            $this->getJson('/api/v1/soap-notes/' . rawurlencode($payload))->assertNotFound()->assertJsonMissingPath('message');
        }
        $this->postJson("/api/v1/soap-notes/$id/diagnoses", ['code' => "J18.9'; DELETE FROM diagnoses; --", 'type' => 'primary'])->assertStatus(422);
        $this->postJson('/api/v1/care-episodes/' . rawurlencode("x' OR '1'='1") . '/soap-notes', self::SOAP)->assertNotFound()->assertJsonPath('code', 'ROUTE_NOT_FOUND');
        $this->assertSame(1, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM clinical_notes'));
    }

    public function test_t2_mass_assignment_cannot_forge_author_status_record_or_hospital(): void
    {
        $forged = self::SOAP + ['author_user_ref_id' => '22222222-2222-4222-8222-222222222222', 'status' => 'signed', 'medical_record_id' => '00000000-0000-4000-8000-000000000000',
            'hospital_id' => 'hospital-b', 'clinical_note_id' => 'ffffffff-ffff-4fff-8fff-ffffffffffff', 'version_no' => 9];
        $id = $this->postJson('/api/v1/care-episodes/' . self::EPISODE . '/soap-notes', $forged)->assertCreated()->json('clinical_note_id');
        $row = DB::connection('hospital')->selectOne('SELECT clinical_note_id, author_user_ref_id, status, medical_record_id, version_no FROM clinical_notes');
        $this->assertSame([$id, self::DOCTOR, 'draft', 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 1], [$row->clinical_note_id, $row->author_user_ref_id, $row->status, $row->medical_record_id, $row->version_no]);
    }

    public function test_t3_the_tenant_comes_from_the_authenticated_context_not_from_the_body(): void
    {
        $this->postJson('/api/v1/care-episodes/' . self::EPISODE . '/soap-notes', self::SOAP + ['tenant_id' => 'hospital-b'])->assertCreated();
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])->postJson('/api/v1/care-episodes/' . self::EPISODE . '/soap-notes', self::SOAP)
            ->assertForbidden()->assertJsonPath('code', 'TENANT_MISMATCH');
        $this->assertSame(1, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM clinical_notes'));
    }

    public function test_t4_enumeration_a_user_without_assignment_cannot_tell_an_existing_note_from_a_missing_one(): void
    {
        $id = $this->postJson('/api/v1/care-episodes/' . self::EPISODE . '/soap-notes', self::SOAP)->json('clinical_note_id');
        $this->actAs('enfermeria', '88888888-8888-4888-8888-888888888888');
        DB::connection('hospital')->update("UPDATE local_user_refs SET central_user_id = '88888888-8888-4888-8888-888888888888' WHERE local_user_ref_id = '22222222-2222-4222-8222-222222222222'");
        $existing = $this->getJson("/api/v1/soap-notes/$id");
        $missing = $this->getJson('/api/v1/soap-notes/eeeeeeee-0000-4000-8000-000000000000');
        $this->assertSame([404, 'NOTE_NOT_FOUND'], [$existing->status(), $existing->json('code')]);
        $this->assertSame([$existing->status(), $existing->json('code')], [$missing->status(), $missing->json('code')]);
        $this->assertStringNotContainsString('Amoxicilina', $existing->getContent());
    }

    public function test_t5_an_id_that_is_not_a_uuid_is_a_404_not_a_database_error(): void
    {
        $this->getJson('/api/v1/soap-notes/no-es-un-uuid')->assertNotFound()->assertJsonPath('code', 'NOTE_NOT_FOUND');
        $this->postJson('/api/v1/care-episodes/ep1/soap-notes', self::SOAP)->assertForbidden()->assertJsonPath('code', 'NOT_TREATING_PHYSICIAN');
    }

    public function test_t6_errors_do_not_leak_internals_and_are_never_cached(): void
    {
        $responses = [
            $this->postJson('/api/v1/care-episodes/' . self::EPISODE . '/soap-notes', ['plan' => ''] + self::SOAP),
            $this->getJson('/api/v1/soap-notes/no-es-un-uuid'),
            $this->postJson('/api/v1/care-episodes/' . self::EPISODE . '/soap-notes', self::SOAP),
        ];
        foreach ($responses as $response) {
            $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
            $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', (string) $response->headers->get('X-Trace-Id'));
            $this->assertDoesNotMatchRegularExpression('/SQLSTATE|PDOException|pgsql|stack|vendor\\\\|\.php/i', $response->getContent());
        }
    }

    public function test_t7_no_clinical_text_reaches_the_audit_and_the_audit_is_append_only(): void
    {
        $id = $this->postJson('/api/v1/care-episodes/' . self::EPISODE . '/soap-notes', self::SOAP)->json('clinical_note_id');
        $this->assertSame(0, (int) DB::connection('hospital')->scalar("SELECT count(*) FROM local_access_audits WHERE metadata::text ILIKE '%Amoxicilina%' OR metadata::text ILIKE '%Tos seca%' OR reason ILIKE '%Neumon%'"));
        $this->assertSame(1, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM local_access_audits WHERE resource_id = ?', [$id]));
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::connection('hospital')->delete('DELETE FROM local_access_audits');
    }

    public function test_t8_oversized_and_blank_sections_are_rejected_before_the_database(): void
    {
        foreach ([str_repeat('a', 4001), "\u{00A0}\u{200B}\u{3000}", '   '] as $bad) {
            $this->postJson('/api/v1/care-episodes/' . self::EPISODE . '/soap-notes', ['subjective' => $bad] + self::SOAP)->assertStatus(422)->assertJsonPath('code', 'SOAP_SECTION_INVALID');
        }
        $this->assertSame(0, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM clinical_notes'));
    }

    private function actAs(string $role, string $centralUserId): void
    {
        CatalogTestIdentity::as($role);
        CatalogTestIdentity::$user = new GenericUser(['id' => $centralUserId]);
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }
}
