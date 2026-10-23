<?php

declare(strict_types=1);

namespace Tests\Feature\NotasSoap;

use App\NotasSoap\LocalActorResolver;
use MicroHis\NotasSoap\Application\CareAssignmentReader;
use MicroHis\NotasSoap\Application\ClinicalNoteRepository;
use MicroHis\NotasSoap\Application\Icd10Catalog;
use MicroHis\NotasSoap\Persistence\InMemoryAuditTrail;
use MicroHis\NotasSoap\Persistence\InMemoryClinicalNoteRepository;
use MicroHis\NotasSoap\Persistence\InMemoryIcd10Catalog;
use Illuminate\Testing\TestResponse;
use Tests\Feature\MedicationCatalog\CatalogTestIdentity;
use Tests\TestCase;

/** API v1 de Notas SOAP con los middlewares y la matriz RBAC reales; adaptadores InMemory para Episodios y CIE-10. */
final class SoapNoteApiTest extends TestCase
{
    private const SOAP = ['subjective' => 'Tos', 'objective' => 'Fiebre 38', 'assessment' => 'Neumonía', 'plan' => 'Amoxicilina'];

    /** @var array<string, list<string>> */
    private array $assigned = [];

    protected function setUp(): void
    {
        parent::setUp();
        CatalogTestIdentity::install($this->app);
        $this->app->instance(ClinicalNoteRepository::class, new InMemoryClinicalNoteRepository(new InMemoryAuditTrail()));
        // Laravel reutiliza el controlador entre peticiones de una misma prueba: las asignaciones se leen por referencia.
        $this->assigned = ['ep1' => ['local-medico']];
        $assigned = &$this->assigned;
        $this->app->instance(CareAssignmentReader::class, new class($assigned) implements CareAssignmentReader {
            public function __construct(private array &$active)
            {
            }

            public function isTreating(string $userId, string $episodeId): bool
            {
                return in_array($userId, $this->active[$episodeId] ?? [], true);
            }
        });
        $this->app->instance(Icd10Catalog::class, new InMemoryIcd10Catalog(['J18.9' => ['Neumonía no especificada', true]]));
        // El sub del token (user-<rol>) se traduce a su referencia local (local-<rol>); un usuario sin referencia local no pasa.
        $this->app->instance(LocalActorResolver::class, new class implements LocalActorResolver {
            public function reference(string $centralUserId): ?string
            {
                return str_starts_with($centralUserId, 'user-') && $centralUserId !== 'user-sin-referencia' ? 'local-' . substr($centralUserId, 5) : null;
            }
        });
        CatalogTestIdentity::as('medico');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    public function test_identity_and_tenant_are_enforced_before_the_module(): void
    {
        CatalogTestIdentity::as(null);
        $this->postJson('/api/v1/care-episodes/ep1/soap-notes', self::SOAP)->assertStatus(401)->assertJsonPath('code', 'AUTH_REQUIRED');
        CatalogTestIdentity::as('medico');
        $this->withHeaders(['X-Tenant-ID' => ''])->postJson('/api/v1/care-episodes/ep1/soap-notes', self::SOAP)->assertStatus(400)->assertJsonPath('code', 'TENANT_REQUIRED');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])->postJson('/api/v1/care-episodes/ep1/soap-notes', self::SOAP)->assertStatus(403)->assertJsonPath('code', 'TENANT_MISMATCH');
    }

    public function test_the_treating_physician_registers_associates_and_reads_with_a_trace_id(): void
    {
        $created = $this->postJson('/api/v1/care-episodes/ep1/soap-notes', self::SOAP)->assertCreated()->assertJsonPath('status', 'draft')->assertHeader('X-Trace-Id');
        $id = $created->json('clinical_note_id');
        $this->postJson("/api/v1/soap-notes/$id/diagnoses", ['code' => 'J18.9', 'type' => 'primary'])->assertCreated();
        $this->getJson("/api/v1/soap-notes/$id")->assertOk()->assertJsonPath('sections.plan', 'Amoxicilina')->assertJsonPath('diagnoses.0.code', 'J18.9')
            ->assertJsonPath('author_user_ref_id', 'local-medico')->assertJsonStructure(['trace_id']);
    }

    /** La firma no lleva cuerpo: se envía vacío, como lo haría el cliente. */
    private function sign(string $id): TestResponse
    {
        return $this->post("/api/v1/soap-notes/$id/sign", [], ['Accept' => 'application/json']);
    }

    public function test_the_author_signs_a_complete_note_and_it_becomes_immutable(): void
    {
        $id = $this->postJson('/api/v1/care-episodes/ep1/soap-notes', self::SOAP)->json('clinical_note_id');
        $this->sign($id)->assertStatus(422)->assertJsonPath('code', 'SOAP_INCOMPLETE');
        $this->postJson("/api/v1/soap-notes/$id/diagnoses", ['code' => 'J18.9', 'type' => 'primary'])->assertCreated();
        $this->sign($id)->assertOk()->assertJsonPath('status', 'signed')->assertHeader('X-Trace-Id');
        $this->sign($id)->assertStatus(409)->assertJsonPath('code', 'NOTE_SIGNED');
        $this->postJson("/api/v1/soap-notes/$id/diagnoses", ['code' => 'J18.9', 'type' => 'secondary'])->assertStatus(409)->assertJsonPath('code', 'NOTE_SIGNED');
        $this->getJson("/api/v1/soap-notes/$id")->assertOk()->assertJsonPath('status', 'signed');
    }

    public function test_signing_needs_its_own_permission_and_the_authoring_physician(): void
    {
        $id = $this->postJson('/api/v1/care-episodes/ep1/soap-notes', self::SOAP)->json('clinical_note_id');
        $this->postJson("/api/v1/soap-notes/$id/diagnoses", ['code' => 'J18.9', 'type' => 'primary'])->assertCreated();
        CatalogTestIdentity::as('enfermeria');
        $this->sign($id)->assertForbidden()->assertJsonPath('code', 'RBAC_DENIED');
        CatalogTestIdentity::as('medico');
        $this->assigned = [];
        $this->sign($id)->assertForbidden()->assertJsonPath('code', 'NOT_TREATING_PHYSICIAN');
        $this->assigned = ['ep1' => ['local-medico']];
        $this->sign('no-existe')->assertNotFound()->assertJsonPath('code', 'NOTE_NOT_FOUND');
    }

    public function test_permissions_follow_the_real_rbac_matrix(): void
    {
        $id = $this->postJson('/api/v1/care-episodes/ep1/soap-notes', self::SOAP)->json('clinical_note_id');
        CatalogTestIdentity::as('enfermeria');
        $this->postJson('/api/v1/care-episodes/ep1/soap-notes', self::SOAP)->assertForbidden()->assertJsonPath('code', 'RBAC_DENIED');
        $this->postJson("/api/v1/soap-notes/$id/diagnoses", ['code' => 'J18.9', 'type' => 'primary'])->assertForbidden();
        CatalogTestIdentity::as('recepcion');
        $this->getJson("/api/v1/soap-notes/$id")->assertForbidden();
        $this->assertCount(3, CatalogTestIdentity::$denials);
    }

    public function test_permission_without_an_active_assignment_does_not_reveal_the_note(): void
    {
        $id = $this->postJson('/api/v1/care-episodes/ep1/soap-notes', self::SOAP)->json('clinical_note_id');
        CatalogTestIdentity::as('enfermeria');
        $this->getJson("/api/v1/soap-notes/$id")->assertNotFound()->assertJsonPath('code', 'NOTE_NOT_FOUND');
        $this->getJson('/api/v1/soap-notes/no-existe')->assertNotFound()->assertJsonPath('code', 'NOTE_NOT_FOUND');
        $this->assigned = ['ep1' => ['local-medico', 'local-enfermeria']];
        $this->getJson("/api/v1/soap-notes/$id")->assertOk();
        CatalogTestIdentity::as('medico');
        $this->assigned = [];
        $this->postJson('/api/v1/care-episodes/ep1/soap-notes', self::SOAP)->assertForbidden()->assertJsonPath('code', 'NOT_TREATING_PHYSICIAN');
    }

    public function test_invalid_payloads_are_rejected_without_saving(): void
    {
        $this->postJson('/api/v1/care-episodes/ep1/soap-notes', ['plan' => ''] + self::SOAP)->assertStatus(422)->assertJsonPath('code', 'SOAP_SECTION_INVALID');
        $this->postJson('/api/v1/care-episodes/ep1/soap-notes', ['subjective' => ['x']] + self::SOAP)->assertStatus(422)->assertJsonPath('code', 'PAYLOAD_INVALID');
        $this->call('POST', '/api/v1/care-episodes/ep1/soap-notes', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_TENANT_ID' => 'hospital-a'], '{no es json')
            ->assertStatus(422)->assertJsonPath('code', 'PAYLOAD_INVALID')->assertJsonStructure(['trace_id']);
    }

    public function test_actor_comes_from_the_token_and_not_from_a_header(): void
    {
        $this->withHeaders(['X-User-ID' => 'user-ajeno'])->postJson('/api/v1/care-episodes/ep1/soap-notes', self::SOAP)->assertCreated();
        $this->getJson('/api/v1/soap-notes/' . $this->postJson('/api/v1/care-episodes/ep1/soap-notes', self::SOAP)->json('clinical_note_id'))->assertJsonPath('author_user_ref_id', 'local-medico');
    }

    public function test_unavailable_catalog_fails_closed_with_503(): void
    {
        $id = $this->postJson('/api/v1/care-episodes/ep1/soap-notes', self::SOAP)->json('clinical_note_id');
        $this->app->instance(Icd10Catalog::class, new InMemoryIcd10Catalog([], true));
        $this->postJson("/api/v1/soap-notes/$id/diagnoses", ['code' => 'J18.9', 'type' => 'primary'])->assertStatus(503)->assertJsonPath('code', 'ICD10_UNAVAILABLE');
    }
    public function test_a_central_user_without_a_local_reference_is_refused_before_touching_notes(): void
    {
        CatalogTestIdentity::as('medico');
        CatalogTestIdentity::$user = new \Illuminate\Auth\GenericUser(['id' => 'user-sin-referencia']);
        $this->postJson('/api/v1/care-episodes/ep1/soap-notes', self::SOAP)->assertForbidden()->assertJsonPath('code', 'LOCAL_USER_NOT_FOUND')->assertJsonStructure(['trace_id']);
    }

    public function test_hospital_database_down_answers_503_without_a_stack_trace(): void
    {
        $this->app->instance(LocalActorResolver::class, new class implements LocalActorResolver {
            public function reference(string $centralUserId): ?string
            {
                throw new \PDOException('SQLSTATE[08006] connection refused');
            }
        });
        $this->postJson('/api/v1/care-episodes/ep1/soap-notes', self::SOAP)->assertStatus(503)->assertJsonPath('code', 'HOSPITAL_UNAVAILABLE')
            ->assertJsonMissingPath('message')->assertHeader('X-Trace-Id');
    }
}
