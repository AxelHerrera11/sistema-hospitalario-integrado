<?php

namespace Tests\Feature;

use App\Models\Allergy;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AllergyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->tenant = Tenant::factory()->create();
    }

    private function userWithRole(?string $role, ?Tenant $tenant = null): User
    {
        $user = User::factory()->create(['tenant_id' => ($tenant ?? $this->tenant)->id]);

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }

    /** @return array<string, string> */
    private function headersFor(User $user, ?Tenant $tenant = null): array
    {
        return [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($user),
            'X-Tenant-ID' => ($tenant ?? $this->tenant)->id,
        ];
    }

    private function patient(?Tenant $tenant = null): Patient
    {
        return Patient::factory()->create(['tenant_id' => ($tenant ?? $this->tenant)->id]);
    }

    /** @param array<string, mixed> $overrides */
    private function allergy(Patient $patient, User $user, array $overrides = []): Allergy
    {
        return Allergy::factory()->create(array_merge([
            'tenant_id' => $patient->tenant_id,
            'patient_id' => $patient->id,
            'registered_by' => $user->id,
        ], $overrides));
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'allergen' => 'Penicilina',
            'allergen_type' => 'medicamento',
            'severity' => 'grave',
            'reaction' => 'Dificultad respiratoria',
            'active' => true,
        ], $overrides);
    }

    public function test_authorized_user_can_list_and_filter_patient_allergies(): void
    {
        $patient = $this->patient();
        $doctor = $this->userWithRole('Médico');
        $this->allergy($patient, $doctor, ['active' => true, 'severity' => 'grave']);
        $this->allergy($patient, $doctor, ['active' => false, 'severity' => 'leve']);

        $this->withHeaders($this->headersFor($doctor))
            ->getJson("/api/v1/patients/{$patient->id}/allergies?active=1&severity=grave")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.severity', 'grave')
            ->assertJsonPath('data.0.active', true);
    }

    public function test_list_validates_filters_and_page_size(): void
    {
        $patient = $this->patient();
        $doctor = $this->userWithRole('Médico');

        $this->withHeaders($this->headersFor($doctor))
            ->getJson("/api/v1/patients/{$patient->id}/allergies?severity=critica&per_page=100")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['severity', 'per_page']);
    }

    public function test_nurse_can_register_an_allergy_with_server_owned_fields(): void
    {
        $patient = $this->patient();
        $otherTenant = Tenant::factory()->create();
        $nurse = $this->userWithRole('Enfermera');

        $this->withHeaders($this->headersFor($nurse))
            ->postJson("/api/v1/patients/{$patient->id}/allergies", $this->payload([
                'tenant_id' => $otherTenant->id,
                'registered_by' => 999999,
            ]))
            ->assertCreated()
            ->assertJsonPath('allergy.tenant_id', $this->tenant->id)
            ->assertJsonPath('allergy.registered_by', $nurse->id);

        $this->assertDatabaseHas('allergies', [
            'tenant_id' => $this->tenant->id,
            'patient_id' => $patient->id,
            'registered_by' => $nurse->id,
            'allergen' => 'Penicilina',
        ]);
    }

    public function test_registration_rejects_invalid_enum_values(): void
    {
        $patient = $this->patient();
        $doctor = $this->userWithRole('Médico');

        $this->withHeaders($this->headersFor($doctor))
            ->postJson("/api/v1/patients/{$patient->id}/allergies", $this->payload([
                'allergen_type' => 'quimico',
                'severity' => 'critica',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['allergen_type', 'severity']);
    }

    public function test_registration_rejects_missing_or_oversized_data(): void
    {
        $patient = $this->patient();
        $doctor = $this->userWithRole('Médico');

        $this->withHeaders($this->headersFor($doctor))
            ->postJson("/api/v1/patients/{$patient->id}/allergies", [
                'allergen' => str_repeat('a', 151),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['allergen', 'allergen_type', 'severity']);
    }

    public function test_doctor_can_edit_and_deactivate_an_allergy(): void
    {
        $patient = $this->patient();
        $doctor = $this->userWithRole('Médico');
        $allergy = $this->allergy($patient, $doctor, ['active' => true]);

        $this->withHeaders($this->headersFor($doctor))
            ->putJson("/api/v1/allergies/{$allergy->id}", [
                'reaction' => 'Urticaria generalizada',
                'active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('allergy.reaction', 'Urticaria generalizada')
            ->assertJsonPath('allergy.active', false);

        $this->assertDatabaseHas('allergies', ['id' => $allergy->id, 'active' => false]);
    }

    public function test_nurse_can_read_and_register_but_cannot_edit(): void
    {
        $patient = $this->patient();
        $doctor = $this->userWithRole('Médico');
        $nurse = $this->userWithRole('Enfermera');
        $allergy = $this->allergy($patient, $doctor);
        $headers = $this->headersFor($nurse);

        $this->withHeaders($headers)->getJson("/api/v1/patients/{$patient->id}/allergies")->assertOk();
        $this->withHeaders($headers)->postJson("/api/v1/patients/{$patient->id}/allergies", $this->payload())->assertCreated();
        $this->withHeaders($headers)->putJson("/api/v1/allergies/{$allergy->id}", ['active' => false])->assertStatus(403);
    }

    public function test_every_endpoint_requires_authentication(): void
    {
        $patient = $this->patient();
        $user = $this->userWithRole('Médico');
        $allergy = $this->allergy($patient, $user);
        $headers = ['X-Tenant-ID' => $this->tenant->id];

        $this->withHeaders($headers)->getJson("/api/v1/patients/{$patient->id}/allergies")->assertStatus(401);
        $this->withHeaders($headers)->postJson("/api/v1/patients/{$patient->id}/allergies", $this->payload())->assertStatus(401);
        $this->withHeaders($headers)->putJson("/api/v1/allergies/{$allergy->id}", ['active' => false])->assertStatus(401);
    }

    public function test_allergy_endpoints_require_a_tenant_header(): void
    {
        $patient = $this->patient();
        $doctor = $this->userWithRole('Médico');
        $allergy = $this->allergy($patient, $doctor);
        $headers = ['Authorization' => 'Bearer '.JWTAuth::fromUser($doctor)];

        $this->withHeaders($headers)->getJson("/api/v1/patients/{$patient->id}/allergies")->assertStatus(400);
        $this->withHeaders($headers)->postJson("/api/v1/patients/{$patient->id}/allergies", $this->payload())->assertStatus(400);
        $this->withHeaders($headers)->putJson("/api/v1/allergies/{$allergy->id}", ['active' => false])->assertStatus(400);
    }

    public function test_unknown_patient_and_allergy_return_not_found(): void
    {
        $doctor = $this->userWithRole('Médico');
        $headers = $this->headersFor($doctor);

        $this->withHeaders($headers)->getJson('/api/v1/patients/999999/allergies')->assertStatus(404);
        $this->withHeaders($headers)->postJson('/api/v1/patients/999999/allergies', $this->payload())->assertStatus(404);
        $this->withHeaders($headers)->putJson('/api/v1/allergies/999999', ['active' => false])->assertStatus(404);
    }

    public function test_user_without_permissions_cannot_access_allergies(): void
    {
        $patient = $this->patient();
        $user = $this->userWithRole(null);
        $allergy = $this->allergy($patient, $user);
        $headers = $this->headersFor($user);

        $this->withHeaders($headers)->getJson("/api/v1/patients/{$patient->id}/allergies")->assertStatus(403);
        $this->withHeaders($headers)->postJson("/api/v1/patients/{$patient->id}/allergies", $this->payload())->assertStatus(403);
        $this->withHeaders($headers)->putJson("/api/v1/allergies/{$allergy->id}", ['active' => false])->assertStatus(403);
    }

    public function test_foreign_patient_is_hidden_from_list_and_registration(): void
    {
        $otherTenant = Tenant::factory()->create();
        $foreignPatient = $this->patient($otherTenant);
        $doctor = $this->userWithRole('Médico');
        $headers = $this->headersFor($doctor);

        $this->withHeaders($headers)->getJson("/api/v1/patients/{$foreignPatient->id}/allergies")->assertStatus(404);
        $this->withHeaders($headers)->postJson("/api/v1/patients/{$foreignPatient->id}/allergies", $this->payload())->assertStatus(404);
    }

    public function test_foreign_allergy_is_hidden_from_update(): void
    {
        $otherTenant = Tenant::factory()->create();
        $foreignPatient = $this->patient($otherTenant);
        $foreignUser = $this->userWithRole('Médico', $otherTenant);
        $foreignAllergy = $this->allergy($foreignPatient, $foreignUser, ['active' => true]);
        $doctor = $this->userWithRole('Médico');

        $this->withHeaders($this->headersFor($doctor))
            ->putJson("/api/v1/allergies/{$foreignAllergy->id}", ['active' => false])
            ->assertStatus(404);

        $this->assertDatabaseHas('allergies', ['id' => $foreignAllergy->id, 'active' => true]);
    }

    public function test_token_cannot_be_used_with_another_tenant_header(): void
    {
        $otherTenant = Tenant::factory()->create();
        $patient = $this->patient($otherTenant);
        $doctor = $this->userWithRole('Médico');

        $this->withHeaders($this->headersFor($doctor, $otherTenant))
            ->getJson("/api/v1/patients/{$patient->id}/allergies")
            ->assertStatus(403);
    }
}
