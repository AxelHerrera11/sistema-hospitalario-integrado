<?php

declare(strict_types=1);

namespace Tests\Feature\MedicationCatalog;

use MicroHis\MedicationCatalog\Application\MedicationRepository;
use MicroHis\MedicationCatalog\Domain\Medication;
use MicroHis\MedicationCatalog\Persistence\InMemoryMedicationRepository;
use Tests\TestCase;

/** API v1 del catálogo con los middlewares y la matriz RBAC reales; repositorio InMemory salvo en la prueba de caída. */
final class MedicationCatalogApiTest extends TestCase
{
    private string $amoxicilina;

    protected function setUp(): void
    {
        parent::setUp();
        // El kernel sincroniza sus alias al crearse; se resuelve antes para que la sustitución de identidad prevalezca.
        CatalogTestIdentity::install($this->app);
        $repository = new InMemoryMedicationRepository();
        $this->amoxicilina = (string) $repository->insert(Medication::register(['code' => 'amx-500', 'name' => 'Amoxicilina 500 mg',
            'active_ingredient' => 'Amoxicilina', 'pharmacological_group' => 'Antibiótico']))->id;
        $this->app->instance(MedicationRepository::class, $repository);
        CatalogTestIdentity::as('medico');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    public function test_identity_and_tenant_are_enforced_before_the_catalog(): void
    {
        CatalogTestIdentity::as(null);
        $this->getJson('/api/v1/medications?q=amox')->assertStatus(401)->assertJsonPath('code', 'AUTH_REQUIRED');
        CatalogTestIdentity::as('medico');
        $this->withHeaders(['X-Tenant-ID' => ''])->getJson('/api/v1/medications?q=amox')->assertStatus(400)->assertJsonPath('code', 'TENANT_REQUIRED');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])->getJson('/api/v1/medications?q=amox')->assertStatus(403)->assertJsonPath('code', 'TENANT_MISMATCH');
    }

    public function test_read_follows_the_real_rbac_matrix(): void
    {
        foreach (['medico', 'enfermeria', 'admin_hospital', 'superadmin'] as $role) {
            CatalogTestIdentity::as($role);
            $this->getJson('/api/v1/medications?q=amox')->assertOk()->assertJsonPath('count', 1)
                ->assertJsonPath('results.0.medication_id', $this->amoxicilina)->assertJsonPath('results.0.validity', 'active');
        }
        CatalogTestIdentity::as('recepcion');
        $this->getJson('/api/v1/medications?q=amox')->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
    }

    public function test_detail_returns_the_contract_shape_and_404(): void
    {
        $this->withHeaders(['X-Request-Id' => 'req-12345678'])->getJson("/api/v1/medications/{$this->amoxicilina}")
            ->assertOk()->assertJsonPath('medication.medication_id', $this->amoxicilina)->assertJsonMissingPath('medication.id')
            ->assertJsonPath('trace_id', 'req-12345678')->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson('/api/v1/medications/999')->assertStatus(404)->assertJsonPath('error', 'MEDICATION_NOT_FOUND');
        $this->getJson('/api/v1/medications?q=a')->assertStatus(422)->assertJsonPath('field', 'term');
    }

    public function test_writes_are_denied_to_every_role_except_admin_hospital(): void
    {
        // Desde la activación RBAC solo admin_hospital escribe; superadmin no hereda la concesión.
        foreach (['medico', 'superadmin'] as $role) {
            CatalogTestIdentity::as($role);
            $this->postJson('/api/v1/medications', ['code' => 'IBU-400', 'name' => 'Ibuprofeno', 'active_ingredient' => 'Ibuprofeno',
                'pharmacological_group' => 'AINE'])->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
            $this->patchJson("/api/v1/medications/{$this->amoxicilina}/validity", ['validity' => 'inactive'])->assertStatus(403);
        }
        // Cada escritura denegada queda registrada con su permiso, actor y hospital (sin registro no hay 403: falla cerrado con 503).
        $denied = array_count_values(array_column(CatalogTestIdentity::$denials, 'permission'));
        $this->assertSame(['medicamentos.registrar' => 2, 'medicamentos.cambiar_vigencia' => 2], $denied);
        $this->assertSame(['user-medico', 'hospital-a'], [CatalogTestIdentity::$denials[0]['user_id'], CatalogTestIdentity::$denials[0]['hospital_id']]);
        CatalogTestIdentity::as('medico');
        $this->getJson("/api/v1/medications/{$this->amoxicilina}")->assertJsonPath('medication.validity', 'active');
    }

    public function test_admin_hospital_writes_with_the_real_rbac_matrix(): void
    {
        // Matriz real de config/rbac.php, sin sustituir concesiones: admin_hospital registra y cambia la vigencia.
        CatalogTestIdentity::as('admin_hospital');
        $draft = ['code' => 'ibu-400', 'name' => 'Ibuprofeno 400 mg', 'active_ingredient' => 'Ibuprofeno', 'pharmacological_group' => 'AINE'];
        $id = $this->postJson('/api/v1/medications', $draft)->assertStatus(201)->assertJsonPath('medication.code', 'IBU-400')
            ->assertJsonPath('medication.validity', 'active')->json('medication.medication_id');
        $this->postJson('/api/v1/medications', ['code' => 'IBU-400'] + $draft)->assertStatus(409);
        $this->postJson('/api/v1/medications', ['name' => 7] + $draft)->assertStatus(422)->assertJsonPath('field', 'name');
        $this->patchJson("/api/v1/medications/{$id}/validity", ['validity' => 'inactive'])->assertOk()->assertJsonPath('medication.validity', 'inactive');
        $this->patchJson("/api/v1/medications/{$id}/validity", ['validity' => 'inactive'])->assertStatus(422);
        CatalogTestIdentity::as('enfermeria');
        $this->getJson('/api/v1/medications?q=ibu')->assertJsonPath('results.0.validity', 'inactive');
    }

    public function test_admin_hospital_membership_in_another_hospital_does_not_grant_writes(): void
    {
        // Contexto del hospital A con la membresía admin_hospital solo en B: el permiso no viaja entre hospitales.
        CatalogTestIdentity::as('admin_hospital', 'hospital-a', 'hospital-b');
        $this->postJson('/api/v1/medications', ['code' => 'IBU-400', 'name' => 'Ibuprofeno', 'active_ingredient' => 'Ibuprofeno',
            'pharmacological_group' => 'AINE'])->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        $this->patchJson("/api/v1/medications/{$this->amoxicilina}/validity", ['validity' => 'inactive'])->assertStatus(403);
        $this->assertSame(['hospital-a', 'hospital-a'], array_column(CatalogTestIdentity::$denials, 'hospital_id'));
    }

    public function test_registering_does_not_authorize_changing_validity(): void
    {
        // Permisos literales: sin medicamentos.cambiar_vigencia, el alta sigue permitida y el cambio de vigencia no.
        config()->set('rbac.roles.admin_hospital', array_values(array_diff(config('rbac.roles.admin_hospital'), ['medicamentos.cambiar_vigencia'])));
        CatalogTestIdentity::as('admin_hospital');
        $this->postJson('/api/v1/medications', ['code' => 'IBU-400', 'name' => 'Ibuprofeno', 'active_ingredient' => 'Ibuprofeno',
            'pharmacological_group' => 'AINE'])->assertStatus(201);
        $this->patchJson("/api/v1/medications/{$this->amoxicilina}/validity", ['validity' => 'inactive'])->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        $this->getJson("/api/v1/medications/{$this->amoxicilina}")->assertJsonPath('medication.validity', 'active');
    }

    public function test_confirmed_writes_are_audited_with_actor_hospital_and_trace(): void
    {
        CatalogTestIdentity::as('admin_hospital');
        $draft = ['code' => 'ibu-400', 'name' => 'Ibuprofeno 400 mg', 'active_ingredient' => 'Ibuprofeno', 'pharmacological_group' => 'AINE'];
        $id = $this->withHeaders(['X-Request-Id' => 'req-alta-0001'])->postJson('/api/v1/medications', $draft)->assertStatus(201)->json('medication.medication_id');
        $this->postJson('/api/v1/medications', $draft)->assertStatus(409);
        $this->withHeaders(['X-Request-Id' => 'req-vig-0001'])->patchJson("/api/v1/medications/{$id}/validity", ['validity' => 'inactive'])->assertOk();

        // Solo las escrituras confirmadas dejan evento; el duplicado rechazado no.
        $this->assertSame(['medicamentos.registrado', 'medicamentos.vigencia_cambiada'], array_column(CatalogTestIdentity::$audited, 'event'));
        [$created, $changed] = array_column(CatalogTestIdentity::$audited, 'payload');
        $this->assertSame(['user-admin_hospital', 'hospital-a', 'req-alta-0001', $id, 'IBU-400', 'active'],
            [$created['user_id'], $created['hospital_id'], $created['trace_id'], $created['medication_id'], $created['code'], $created['validity']]);
        $this->assertSame(['req-vig-0001', $id, 'inactive'], [$changed['trace_id'], $changed['medication_id'], $changed['validity']]);
    }

    public function test_writes_answer_503_when_the_audit_cannot_be_recorded(): void
    {
        CatalogTestIdentity::as('admin_hospital');
        CatalogTestIdentity::$auditDown = true;
        $this->postJson('/api/v1/medications', ['code' => 'IBU-400', 'name' => 'Ibuprofeno', 'active_ingredient' => 'Ibuprofeno',
            'pharmacological_group' => 'AINE'])->assertStatus(503)->assertJsonPath('error', 'AUDIT_UNAVAILABLE')->assertJsonMissingPath('medication');
        $this->patchJson("/api/v1/medications/{$this->amoxicilina}/validity", ['validity' => 'inactive'])->assertStatus(503);
        $this->assertSame([], CatalogTestIdentity::$audited);
    }

    public function test_requests_are_rate_limited_per_user_and_hospital(): void
    {
        $limit = \App\MedicationCatalog\CatalogRateLimit::REQUESTS_PER_MINUTE;
        for ($i = 0; $i < $limit; $i++) {
            $this->getJson('/api/v1/medications?q=amox')->assertOk();
        }
        $this->getJson('/api/v1/medications?q=amox')->assertStatus(429)->assertJsonPath('error', 'CATALOG_RATE_LIMITED')
            ->assertHeader('Retry-After')->assertHeader('Cache-Control', 'no-store, private');
        // Otro usuario del mismo hospital conserva su cupo.
        CatalogTestIdentity::as('enfermeria');
        $this->getJson('/api/v1/medications?q=amox')->assertOk();
    }

    public function test_unavailable_hospital_database_answers_503_without_details(): void
    {
        // Sin sustituir el Repository: se usa el enlace real del provider sobre la conexión 'hospital', apuntada a una base
        // inexistente para que la caída no dependa de si el PostgreSQL de pruebas está levantado o ya tiene la tabla H5.
        config()->set('database.connections.hospital.database', 'catalogo_base_inexistente');
        \Illuminate\Support\Facades\DB::purge('hospital');
        $this->app->forgetInstance(MedicationRepository::class);
        $this->app->offsetUnset(MedicationRepository::class);
        $this->app->register(\App\Providers\MedicationCatalogServiceProvider::class, true);
        $response = $this->getJson('/api/v1/medications?q=amox')->assertStatus(503)->assertJsonPath('error', 'CATALOG_UNAVAILABLE')->assertJsonMissingPath('results');
        $this->assertDoesNotMatchRegularExpression('/SQLSTATE|PDO|Stack|\.php/i', (string) $response->getContent());
    }
}
