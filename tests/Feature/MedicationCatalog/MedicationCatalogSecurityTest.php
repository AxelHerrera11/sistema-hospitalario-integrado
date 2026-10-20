<?php

declare(strict_types=1);

namespace Tests\Feature\MedicationCatalog;

use MicroHis\MedicationCatalog\Application\MedicationRepository;
use MicroHis\MedicationCatalog\Domain\Medication;
use MicroHis\MedicationCatalog\Persistence\PdoMedicationCatalog;
use MicroHis\Rbac\Domain\RoleResolver;
use PDO;
use Tests\TestCase;

/** Semana 17: amenazas de la matriz STRIDE probadas por HTTP contra el adaptador PDO real (SQLite en memoria). */
final class MedicationCatalogSecurityTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', CatalogTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', CatalogTestIdentity::class);
        $this->app->instance(RoleResolver::class, new CatalogTestIdentity());
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec((string) file_get_contents(base_path('micro-his/catalogo-medicamentos/database/schema.sql')));
        $repository = new PdoMedicationCatalog($this->pdo);
        $repository->insert(Medication::register(['code' => 'amx-500', 'name' => 'Amoxicilina 500 mg', 'active_ingredient' => 'Amoxicilina',
            'pharmacological_group' => 'Antibiótico']));
        $this->app->instance(MedicationRepository::class, $repository);
        CatalogTestIdentity::as('medico');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    public function test_t1_sql_injection_in_search_returns_no_rows_and_keeps_the_table(): void
    {
        foreach (["' OR 1=1 --", "%' UNION SELECT 1,2,3,4,5,6 --", '%%'] as $payload) {
            $this->getJson('/api/v1/medications?q=' . rawurlencode($payload))->assertOk()->assertJsonPath('count', 0);
        }
        $this->assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM medications')->fetchColumn());
    }

    public function test_t2_mass_assignment_cannot_create_an_inactive_or_foreign_hospital_record(): void
    {
        config()->set('rbac.roles.admin_hospital', [...config('rbac.roles.admin_hospital'), 'medicamentos.registrar']);
        CatalogTestIdentity::as('admin_hospital');
        $this->postJson('/api/v1/medications', ['code' => 'ibu-400', 'name' => 'Ibuprofeno', 'active_ingredient' => 'Ibuprofeno',
            'pharmacological_group' => 'AINE', 'validity' => 'inactive', 'medication_id' => '999', 'hospital_id' => 'hospital-b'])
            ->assertStatus(201)->assertJsonPath('medication.validity', 'active')->assertJsonPath('medication.medication_id', '2');
    }

    public function test_t3_tenant_comes_from_the_context_not_from_headers_or_body(): void
    {
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])->getJson('/api/v1/medications?q=amox&hospital_id=hospital-b')
            ->assertStatus(403)->assertJsonPath('code', 'TENANT_MISMATCH');
    }

    public function test_t4_errors_do_not_leak_internals_and_are_not_cached(): void
    {
        $this->pdo->exec('DROP TABLE medications');
        $response = $this->getJson('/api/v1/medications?q=amox')->assertStatus(503)->assertHeader('Cache-Control', 'no-store, private');
        $this->assertDoesNotMatchRegularExpression('/SQLSTATE|no such table|PDO|\.php|#\d/i', (string) $response->getContent());
        CatalogTestIdentity::as('recepcion');
        $this->getJson('/api/v1/medications?q=amox')->assertStatus(403)->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_t5_trace_id_from_client_is_accepted_only_when_safe(): void
    {
        $this->withHeaders(['X-Request-Id' => 'abc12345'])->getJson('/api/v1/medications?q=amox')->assertJsonPath('trace_id', 'abc12345');
        $trace = $this->withHeaders(['X-Request-Id' => "x\r\nSet-Cookie: a=b"])->getJson('/api/v1/medications?q=amox')->json('trace_id');
        $this->assertMatchesRegularExpression('/^trace-[0-9a-f]{12}$/', $trace);
    }

    public function test_t6_the_catalog_never_exposes_patient_data(): void
    {
        $body = $this->getJson('/api/v1/medications?q=amox')->assertOk()->json('results.0');
        $this->assertSame(['medication_id', 'code', 'name', 'active_ingredient', 'pharmacological_group', 'validity'], array_keys($body));
    }
}
