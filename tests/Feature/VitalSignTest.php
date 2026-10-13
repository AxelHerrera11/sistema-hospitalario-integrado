<?php

namespace Tests\Feature;

use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Signos vitales — Área 5.
 *
 * Cubre registro, alertas por valores anormales, validación por hospital,
 * listado/paginación y permisos. Sigue el patrón de PatientTest.
 */
class VitalSignTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->tenant = Tenant::factory()->create(['slug' => 'san-marcos']);
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

    private function makeMedicalRecord(?Tenant $tenant = null): MedicalRecord
    {
        $patient = Patient::factory()->create(['tenant_id' => ($tenant ?? $this->tenant)->id]);

        return MedicalRecord::factory()->create([
            'tenant_id' => ($tenant ?? $this->tenant)->id,
            'patient_id' => $patient->id,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'measured_at' => '2026-10-06 09:00:00',
            'temperature' => 36.6,
            'heart_rate' => 72,
            'respiratory_rate' => 16,
            'systolic_bp' => 120,
            'diastolic_bp' => 80,
            'oxygen_saturation' => 98,
        ], $overrides);
    }

    // ── Permisos ───────────────────────────────────────────────────────────

    public function test_vital_sign_endpoints_require_authentication(): void
    {
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/v1/vital-signs')->assertStatus(401);

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/vital-signs', $this->payload())->assertStatus(401);
    }

    public function test_receptionist_cannot_register_vital_signs(): void
    {
        // La Recepcionista no tiene signos_vitales.ver ni .registrar.
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->getJson('/api/v1/vital-signs')->assertStatus(403);

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/vital-signs', $this->payload())->assertStatus(403);
    }

    // ── Registro ───────────────────────────────────────────────────────────

    public function test_nurse_can_register_vital_signs_with_registered_by_from_token(): void
    {
        $record = $this->makeMedicalRecord();
        $nurse = $this->userWithRole('Enfermera');

        $this->withHeaders($this->headersFor($nurse))
            ->postJson('/api/v1/vital-signs', $this->payload([
                'medical_record_id' => $record->id,
            ]))
            ->assertCreated()
            ->assertJsonPath('vital_sign.registered_by', $nurse->id)
            ->assertJsonPath('vital_sign.has_alert', false)
            ->assertJsonPath('vital_sign.tenant_id', $this->tenant->id);
    }

    public function test_register_with_abnormal_value_flags_has_alert_and_details(): void
    {
        $record = $this->makeMedicalRecord();
        $nurse = $this->userWithRole('Enfermera');

        $this->withHeaders($this->headersFor($nurse))
            ->postJson('/api/v1/vital-signs', $this->payload([
                'medical_record_id' => $record->id,
                'temperature' => 40.5,
            ]))
            ->assertCreated()
            ->assertJsonPath('vital_sign.has_alert', true)
            ->assertJsonPath('vital_sign.alert_details.0.field', 'temperature')
            ->assertJsonPath('vital_sign.alert_details.0.value', 40.5);
    }

    public function test_register_ignores_a_tenant_id_sent_by_the_client(): void
    {
        $otherTenant = Tenant::factory()->create();
        $record = $this->makeMedicalRecord();
        $nurse = $this->userWithRole('Enfermera');

        $this->withHeaders($this->headersFor($nurse))
            ->postJson('/api/v1/vital-signs', $this->payload([
                'medical_record_id' => $record->id,
                'tenant_id' => $otherTenant->id,
            ]))
            ->assertCreated()
            ->assertJsonPath('vital_sign.tenant_id', $this->tenant->id);
    }

    public function test_register_rejects_a_medical_record_of_another_hospital(): void
    {
        $otherTenant = Tenant::factory()->create();
        $foreign = $this->makeMedicalRecord($otherTenant);
        $nurse = $this->userWithRole('Enfermera');

        $this->withHeaders($this->headersFor($nurse))
            ->postJson('/api/v1/vital-signs', $this->payload([
                'medical_record_id' => $foreign->id,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('medical_record_id');
    }

    public function test_register_requires_a_valid_medical_record(): void
    {
        $nurse = $this->userWithRole('Enfermera');

        $this->withHeaders($this->headersFor($nurse))
            ->postJson('/api/v1/vital-signs', $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('medical_record_id');
    }

    public function test_register_rejects_extreme_values_beyond_the_column(): void
    {
        $record = $this->makeMedicalRecord();
        $nurse = $this->userWithRole('Enfermera');

        // weight/height son decimal(5,2) -> máx 999.99; glucosa decimal(6,2) -> máx 9999.99.
        $this->withHeaders($this->headersFor($nurse))
            ->postJson('/api/v1/vital-signs', $this->payload([
                'medical_record_id' => $record->id,
                'weight' => 1000,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('weight');

        $this->withHeaders($this->headersFor($nurse))
            ->postJson('/api/v1/vital-signs', $this->payload([
                'medical_record_id' => $record->id,
                'glucose' => 10000,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('glucose');
    }

    public function test_doctor_can_view_but_receptionist_cannot_see_catalog(): void
    {
        $record = $this->makeMedicalRecord();
        $nurse = $this->userWithRole('Enfermera');
        $this->withHeaders($this->headersFor($nurse))
            ->postJson('/api/v1/vital-signs', $this->payload([
                'medical_record_id' => $record->id,
            ]))
            ->assertCreated();

        $doctor = $this->userWithRole('Médico');

        $this->withHeaders($this->headersFor($doctor))
            ->getJson('/api/v1/vital-signs?medical_record_id='.$record->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ── Listado ────────────────────────────────────────────────────────────

    public function test_list_paginates_and_caps_per_page_at_fifty(): void
    {
        $record = $this->makeMedicalRecord();
        $nurse = $this->userWithRole('Enfermera');
        $headers = $this->headersFor($nurse);

        for ($i = 1; $i <= 3; $i++) {
            $this->withHeaders($headers)
                ->postJson('/api/v1/vital-signs', $this->payload([
                    'medical_record_id' => $record->id,
                    'measured_at' => "2026-10-0{$i} 09:00:00",
                ]))
                ->assertCreated();
        }

        $this->withHeaders($headers)
            ->getJson('/api/v1/vital-signs?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('total', 3)
            ->assertJsonPath('per_page', 2);

        $this->withHeaders($headers)
            ->getJson('/api/v1/vital-signs?per_page=500')
            ->assertOk()
            ->assertJsonPath('per_page', 50);
    }

    public function test_list_filters_by_medical_record_and_dates(): void
    {
        $record = $this->makeMedicalRecord();
        $nurse = $this->userWithRole('Enfermera');
        $headers = $this->headersFor($nurse);

        $this->withHeaders($headers)
            ->postJson('/api/v1/vital-signs', $this->payload([
                'medical_record_id' => $record->id,
                'measured_at' => '2026-10-01 09:00:00',
            ]))->assertCreated();

        $this->withHeaders($headers)
            ->postJson('/api/v1/vital-signs', $this->payload([
                'medical_record_id' => $record->id,
                'measured_at' => '2026-10-15 09:00:00',
            ]))->assertCreated();

        $this->withHeaders($headers)
            ->getJson('/api/v1/vital-signs?medical_record_id='.$record->id.'&from=2026-10-10&to=2026-10-20')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_list_does_not_leak_records_of_another_tenant(): void
    {
        $record = $this->makeMedicalRecord();
        $nurse = $this->userWithRole('Enfermera');

        $this->withHeaders($this->headersFor($nurse))
            ->postJson('/api/v1/vital-signs', $this->payload([
                'medical_record_id' => $record->id,
                'measured_at' => '2026-10-01 09:00:00',
            ]))->assertCreated();

        $otherTenant = Tenant::factory()->create();
        $otherRecord = $this->makeMedicalRecord($otherTenant);
        $otherNurse = $this->userWithRole('Enfermera', $otherTenant);

        $this->withHeaders($this->headersFor($otherNurse, $otherTenant))
            ->postJson('/api/v1/vital-signs', $this->payload([
                'medical_record_id' => $otherRecord->id,
                'measured_at' => '2026-10-02 09:00:00',
            ]))->assertCreated();

        // El listado del primer hospital no ve las mediciones del segundo.
        $this->withHeaders($this->headersFor($nurse))
            ->getJson('/api/v1/vital-signs?medical_record_id='.$record->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withHeaders($this->headersFor($nurse))
            ->getJson('/api/v1/vital-signs')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_list_ignores_an_unknown_sort_column(): void
    {
        $record = $this->makeMedicalRecord();
        $nurse = $this->userWithRole('Enfermera');

        $this->withHeaders($this->headersFor($nurse))
            ->getJson('/api/v1/vital-signs?sort_by='.urlencode('measured_at; drop table vital_signs'))
            ->assertOk();
    }

    // ── Timeline por expediente ────────────────────────────────────────────

    public function test_index_by_medical_record_returns_only_that_record(): void
    {
        $recordA = $this->makeMedicalRecord();
        $recordB = $this->makeMedicalRecord();
        $nurse = $this->userWithRole('Enfermera');
        $headers = $this->headersFor($nurse);

        foreach ([$recordA, $recordB] as $record) {
            $this->withHeaders($headers)
                ->postJson('/api/v1/vital-signs', $this->payload([
                    'medical_record_id' => $record->id,
                ]))->assertCreated();
        }

        $this->withHeaders($headers)
            ->getJson("/api/v1/medical-records/{$recordA->id}/vital-signs")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}