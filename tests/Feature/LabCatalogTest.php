<?php

namespace Tests\Feature;

use App\Models\LabTest;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Catálogo de pruebas — Área 7, submódulo CAT (RF-CAT-01 a RF-CAT-03).
 */
class LabCatalogTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->tenant = Tenant::factory()->create();
    }

    private function userWithRole(string $role, ?Tenant $tenant = null): User
    {
        $user = User::factory()->create(['tenant_id' => ($tenant ?? $this->tenant)->id]);
        $user->assignRole($role);

        return $user;
    }

    /** @return array<string, string> */
    private function headersFor(User $user): array
    {
        return [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($user),
            'X-Tenant-ID' => $user->tenant_id,
        ];
    }

    private function makeTest(array $overrides = []): LabTest
    {
        return LabTest::factory()->create(['tenant_id' => $this->tenant->id, ...$overrides]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Potasio',
            'category' => 'Electrolitos',
            'unit' => 'mEq/L',
            'reference_min' => 3.5,
            'reference_max' => 5.1,
            'critical_min' => 2.5,
            'critical_max' => 6.5,
            'turnaround_min' => 45,
            ...$overrides,
        ];
    }

    // ── Alta y edición (RF-CAT-02) ─────────────────────────────────────────

    public function test_biochemist_can_create_a_lab_test(): void
    {
        $this->withHeaders($this->headersFor($this->userWithRole('Bioquimico')))
            ->postJson('/api/v1/lab-tests', $this->payload())
            ->assertCreated()
            ->assertJsonPath('lab_test.name', 'Potasio')
            ->assertJsonPath('lab_test.active', true)
            ->assertJsonPath('lab_test.tenant_id', $this->tenant->id);

        $this->assertDatabaseHas('lab_tests', ['tenant_id' => $this->tenant->id, 'name' => 'Potasio']);
    }

    public function test_doctor_and_lab_technician_cannot_manage_the_catalog(): void
    {
        foreach (['Médico', 'TecnicoLab'] as $role) {
            $this->withHeaders($this->headersFor($this->userWithRole($role)))
                ->postJson('/api/v1/lab-tests', $this->payload())
                ->assertForbidden();
        }

        $this->assertDatabaseCount('lab_tests', 0);
    }

    public function test_name_is_unique_per_hospital_only(): void
    {
        $this->makeTest(['name' => 'Potasio']);
        LabTest::factory()->create(['name' => 'Sodio']);

        $biochemist = $this->userWithRole('Bioquimico');

        $this->withHeaders($this->headersFor($biochemist))
            ->postJson('/api/v1/lab-tests', $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->withHeaders($this->headersFor($biochemist))
            ->postJson('/api/v1/lab-tests', $this->payload(['name' => 'Sodio']))
            ->assertCreated();
    }

    public function test_validation_messages_use_the_spanish_field_names(): void
    {
        $this->withHeaders($this->headersFor($this->userWithRole('Bioquimico')))
            ->postJson('/api/v1/lab-tests', $this->payload(['reference_min' => 'alto', 'turnaround_min' => 0]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.reference_min.0', 'El campo mínimo de referencia debe ser un número.')
            ->assertJsonValidationErrors('turnaround_min');
    }

    public function test_reference_max_cannot_be_lower_than_reference_min(): void
    {
        $this->withHeaders($this->headersFor($this->userWithRole('Bioquimico')))
            ->postJson('/api/v1/lab-tests', $this->payload(['reference_min' => 6, 'reference_max' => 5]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reference_max');
    }

    public function test_critical_limits_must_lie_outside_the_reference_range(): void
    {
        $biochemist = $this->userWithRole('Bioquimico');

        $this->withHeaders($this->headersFor($biochemist))
            ->postJson('/api/v1/lab-tests', $this->payload(['critical_min' => 3.5, 'critical_max' => 5.1]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['critical_min', 'critical_max']);

        // Sin rango de referencia, solo se exige critical_min < critical_max.
        $this->withHeaders($this->headersFor($biochemist))
            ->postJson('/api/v1/lab-tests', $this->payload([
                'reference_min' => null, 'reference_max' => null, 'critical_min' => 9, 'critical_max' => 2,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('critical_max');
    }

    public function test_a_text_only_test_needs_no_ranges(): void
    {
        $this->withHeaders($this->headersFor($this->userWithRole('Bioquimico')))
            ->postJson('/api/v1/lab-tests', ['name' => 'Urocultivo', 'category' => 'Microbiología'])
            ->assertCreated()
            ->assertJsonPath('lab_test.reference_min', null);
    }

    public function test_update_checks_ranges_against_the_stored_limits(): void
    {
        $labTest = $this->makeTest(['name' => 'Glucosa']); // referencia 70–110

        $this->withHeaders($this->headersFor($this->userWithRole('Bioquimico')))
            ->putJson("/api/v1/lab-tests/{$labTest->id}", ['name' => 'Glucosa', 'critical_max' => 100])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('critical_max');
    }

    public function test_biochemist_can_deactivate_a_test_keeping_its_name(): void
    {
        $labTest = $this->makeTest(['name' => 'Glucosa']);

        $this->withHeaders($this->headersFor($this->userWithRole('Bioquimico')))
            ->putJson("/api/v1/lab-tests/{$labTest->id}", ['name' => 'Glucosa', 'active' => false])
            ->assertOk()
            ->assertJsonPath('lab_test.active', false);

        $this->assertDatabaseHas('lab_tests', ['id' => $labTest->id, 'active' => false]);
    }

    // ── Consulta (RF-CAT-01) ───────────────────────────────────────────────

    public function test_list_returns_only_the_tests_of_the_current_hospital(): void
    {
        $this->makeTest(['name' => 'Glucosa']);
        LabTest::factory()->create(['name' => 'Ajena']);

        $this->withHeaders($this->headersFor($this->userWithRole('Médico')))
            ->getJson('/api/v1/lab-tests')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.name', 'Glucosa');
    }

    public function test_list_filters_by_search_category_and_active(): void
    {
        $this->makeTest(['name' => 'Glucosa', 'category' => 'Química']);
        $this->makeTest(['name' => 'Hemoglobina', 'category' => 'Hematología']);
        $this->makeTest(['name' => 'Glucosa postprandial', 'category' => 'Química', 'active' => false]);

        $headers = $this->headersFor($this->userWithRole('TecnicoLab'));

        $this->withHeaders($headers)->getJson('/api/v1/lab-tests?q=GLUCOSA')
            ->assertOk()->assertJsonCount(2, 'data');
        $this->withHeaders($headers)->getJson('/api/v1/lab-tests?category=Hematología')
            ->assertOk()->assertJsonCount(1, 'data');
        $this->withHeaders($headers)->getJson('/api/v1/lab-tests?q=glucosa&active=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Glucosa');
    }

    public function test_search_ignores_accents_on_postgres(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('La búsqueda sin acentos solo aplica en PostgreSQL.');
        }

        $this->makeTest(['name' => 'Hemoglobina', 'category' => 'Hematología']);

        $this->withHeaders($this->headersFor($this->userWithRole('Bioquimico')))
            ->getJson('/api/v1/lab-tests?q=hematologia')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_list_caps_per_page_at_fifty(): void
    {
        $this->withHeaders($this->headersFor($this->userWithRole('Bioquimico')))
            ->getJson('/api/v1/lab-tests?per_page=500')
            ->assertOk()
            ->assertJsonPath('per_page', 50);
    }

    public function test_receptionist_cannot_see_the_catalog(): void
    {
        $this->withHeaders($this->headersFor($this->userWithRole('Recepcionista')))
            ->getJson('/api/v1/lab-tests')
            ->assertForbidden();
    }

    // ── Aislamiento por hospital (RNF-LAB-02) ──────────────────────────────

    public function test_a_test_of_another_hospital_responds_404(): void
    {
        $foreign = LabTest::factory()->create(['name' => 'Ajena']);
        $headers = $this->headersFor($this->userWithRole('Bioquimico'));

        $this->withHeaders($headers)->getJson("/api/v1/lab-tests/{$foreign->id}")
            ->assertNotFound()
            ->assertExactJson(['message' => 'Recurso no encontrado.']);

        $this->withHeaders($headers)->putJson("/api/v1/lab-tests/{$foreign->id}", ['name' => 'Robada'])
            ->assertNotFound();

        $this->assertDatabaseHas('lab_tests', ['id' => $foreign->id, 'name' => 'Ajena']);
    }
}
