<?php

namespace Tests\Feature;

use App\Models\Medication;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MedicationTest extends TestCase
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

    private function medication(array $overrides = []): Medication
    {
        return Medication::factory()->create(['tenant_id' => $this->tenant->id, ...$overrides]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Amoxicilina 500 mg',
            'generic_name' => 'Amoxicilina',
            'category' => 'Antibiótico',
            'presentation' => 'Cápsula',
            'concentration' => '500 mg',
            ...$overrides,
        ];
    }

    public function test_admin_can_create_a_medication_with_server_owned_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        $admin = $this->userWithRole('Admin');

        $this->withHeaders($this->headersFor($admin))
            ->postJson('/api/v1/medications', $this->payload(['tenant_id' => $otherTenant->id]))
            ->assertCreated()
            ->assertJsonPath('medication.name', 'Amoxicilina 500 mg')
            ->assertJsonPath('medication.tenant_id', $this->tenant->id)
            ->assertJsonPath('medication.active', true);

        $this->assertDatabaseHas('medications', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Amoxicilina 500 mg',
        ]);
    }

    public function test_medication_name_is_required_and_fields_respect_schema_lengths(): void
    {
        $admin = $this->userWithRole('Admin');

        $this->withHeaders($this->headersFor($admin))
            ->postJson('/api/v1/medications', $this->payload([
                'name' => '',
                'generic_name' => str_repeat('a', 101),
                'presentation' => str_repeat('a', 51),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'generic_name', 'presentation']);
    }

    public function test_name_is_unique_within_each_hospital(): void
    {
        $this->medication(['name' => 'Amoxicilina 500 mg']);
        Medication::factory()->create(['name' => 'Ibuprofeno 400 mg']);
        $admin = $this->userWithRole('Admin');

        $this->withHeaders($this->headersFor($admin))
            ->postJson('/api/v1/medications', $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->withHeaders($this->headersFor($admin))
            ->postJson('/api/v1/medications', $this->payload(['name' => 'Ibuprofeno 400 mg']))
            ->assertCreated();
    }

    public function test_admin_can_edit_and_deactivate_a_medication(): void
    {
        $medication = $this->medication(['name' => 'Amoxicilina']);

        $this->withHeaders($this->headersFor($this->userWithRole('Admin')))
            ->putJson("/api/v1/medications/{$medication->id}", [
                ...$this->payload(['name' => 'Amoxicilina']),
                'active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('medication.active', false);

        $this->assertDatabaseHas('medications', ['id' => $medication->id, 'active' => false]);
    }

    public function test_doctor_and_nurse_can_list_and_view_medications(): void
    {
        $medication = $this->medication(['name' => 'Metformina 850 mg']);

        foreach (['Médico', 'Enfermera'] as $role) {
            $headers = $this->headersFor($this->userWithRole($role));

            $this->withHeaders($headers)->getJson('/api/v1/medications')
                ->assertOk()->assertJsonPath('data.0.name', 'Metformina 850 mg');
            $this->withHeaders($headers)->getJson("/api/v1/medications/{$medication->id}")
                ->assertOk()->assertJsonPath('medication.id', $medication->id);
        }
    }

    public function test_nurse_cannot_create_or_edit_medications(): void
    {
        $medication = $this->medication(['name' => 'Metformina']);
        $headers = $this->headersFor($this->userWithRole('Enfermera'));

        $this->withHeaders($headers)->postJson('/api/v1/medications', $this->payload())->assertForbidden();
        $this->withHeaders($headers)->putJson(
            "/api/v1/medications/{$medication->id}",
            $this->payload(['name' => 'Cambio'])
        )->assertForbidden();
    }

    public function test_list_is_tenant_scoped_and_supports_search_and_filters(): void
    {
        $this->medication([
            'name' => 'Tylenol 500 mg',
            'generic_name' => 'Acetaminofén',
            'category' => 'Analgésico',
        ]);
        $this->medication(['name' => 'Ibuprofeno', 'category' => 'AINE', 'active' => false]);
        Medication::factory()->create(['name' => 'Medicamento ajeno']);
        $headers = $this->headersFor($this->userWithRole('Médico'));

        $this->withHeaders($headers)->getJson('/api/v1/medications?q=acetaminofén')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Tylenol 500 mg');
        $this->withHeaders($headers)->getJson('/api/v1/medications?category=AINE&active=0')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Ibuprofeno');
        $this->withHeaders($headers)->getJson('/api/v1/medications')
            ->assertOk()->assertJsonPath('total', 2);
    }

    public function test_search_treats_wildcards_as_literal_text(): void
    {
        $this->medication(['name' => 'Medicamento normal']);

        $this->withHeaders($this->headersFor($this->userWithRole('Médico')))
            ->getJson('/api/v1/medications?q=%25')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_search_ignores_accents_on_postgres(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('La búsqueda sin acentos solo aplica en PostgreSQL.');
        }

        $this->medication(['name' => 'Paracetamol', 'category' => 'Analgésico']);

        $this->withHeaders($this->headersFor($this->userWithRole('Médico')))
            ->getJson('/api/v1/medications?q=analgesico')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_list_caps_per_page_at_fifty(): void
    {
        $this->withHeaders($this->headersFor($this->userWithRole('Médico')))
            ->getJson('/api/v1/medications?per_page=500')
            ->assertOk()
            ->assertJsonPath('per_page', 50);
    }

    public function test_foreign_medication_is_hidden_from_show_and_update(): void
    {
        $foreign = Medication::factory()->create(['name' => 'Ajeno']);
        $headers = $this->headersFor($this->userWithRole('Admin'));

        $this->withHeaders($headers)->getJson("/api/v1/medications/{$foreign->id}")
            ->assertNotFound()
            ->assertExactJson(['message' => 'Recurso no encontrado.']);
        $this->withHeaders($headers)->putJson(
            "/api/v1/medications/{$foreign->id}",
            $this->payload(['name' => 'Robado'])
        )->assertNotFound();

        $this->assertDatabaseHas('medications', ['id' => $foreign->id, 'name' => 'Ajeno']);
    }

    public function test_medication_routes_require_authentication_and_tenant(): void
    {
        $medication = $this->medication();
        $admin = $this->userWithRole('Admin');

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/v1/medications')->assertUnauthorized();

        $this->flushHeaders();
        $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($admin))
            ->getJson("/api/v1/medications/{$medication->id}")->assertBadRequest();
    }
}
