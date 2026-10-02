<?php

namespace Tests\Feature;

use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Pacientes — Área 2.
 *
 * Cubre listado, búsqueda, detalle, alta, edición, validaciones, permisos y
 * aislamiento entre hospitales. Las pruebas verifican comportamiento real
 * sobre la API: no hay asserts decorativos.
 */
class PatientTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        // slug fijo para que los códigos generados sean predecibles:
        // 'san-marcos' -> prefijo SANMAR -> PAC-SANMAR-0001
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

    /** @param array<string, mixed> $overrides */
    private function makePatient(array $overrides = []): Patient
    {
        return Patient::factory()->create(array_merge([
            'tenant_id' => $this->tenant->id,
        ], $overrides));
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ana',
            'last_name' => 'Ríos',
            'birth_date' => '1992-03-08',
            'gender' => 'F',
            'phone' => '5555-1111',
            'email' => 'ana@demo.local',
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function searchFixtures(): array
    {
        return [
            'maria' => $this->makePatient([
                'first_name' => 'María', 'last_name' => 'López',
                'dpi' => '1000000000001', 'code' => 'PAC-SANMAR-0001', 'phone' => '5555-1001',
            ]),
            'ana' => $this->makePatient([
                'first_name' => 'Ana', 'last_name' => 'Ríos',
                'dpi' => '2000000000002', 'code' => 'PAC-SANMAR-0002', 'phone' => '5555-1002',
            ]),
            'luis' => $this->makePatient([
                'first_name' => 'Luis', 'last_name' => 'Pérez',
                'dpi' => '3000000000003', 'code' => 'PAC-SANMAR-0003', 'phone' => '5555-1003',
            ]),
        ];
    }

    // ── Listado y búsqueda ────────────────────────────────────────────────

    public function test_list_returns_the_patients_of_the_current_tenant(): void
    {
        $this->searchFixtures();
        $receptionist = $this->userWithRole('Recepcionista');

        $response = $this->withHeaders($this->headersFor($receptionist))
            ->getJson('/api/v1/patients')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('total', 3);

        // Orden por defecto: last_name asc -> López, Pérez, Ríos.
        $this->assertSame(
            ['María', 'Luis', 'Ana'],
            array_column($response->json('data'), 'first_name'),
        );
    }

    public function test_list_paginates_and_reports_the_page_meta(): void
    {
        $this->searchFixtures();
        $receptionist = $this->userWithRole('Recepcionista');

        // El paginador nativo de Laravel expone la metadata en la raíz
        // (data, total, per_page, last_page...), no dentro de 'meta'.
        $this->withHeaders($this->headersFor($receptionist))
            ->getJson('/api/v1/patients?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('total', 3)
            ->assertJsonPath('per_page', 2)
            ->assertJsonPath('last_page', 2)
            ->assertJsonPath('current_page', 1);
    }

    public function test_list_caps_per_page_at_fifty(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->getJson('/api/v1/patients?per_page=500')
            ->assertOk()
            ->assertJsonPath('per_page', 50);
    }

    public function test_list_sorts_by_last_name_ascending_by_default(): void
    {
        $this->makePatient(['first_name' => 'Zulma', 'last_name' => 'Adams', 'code' => 'PAC-SANMAR-0001']);
        $this->makePatient(['first_name' => 'Belén', 'last_name' => 'Baker', 'code' => 'PAC-SANMAR-0002']);
        $this->makePatient(['first_name' => 'Carla', 'last_name' => 'Carter', 'code' => 'PAC-SANMAR-0003']);
        $receptionist = $this->userWithRole('Recepcionista');

        $response = $this->withHeaders($this->headersFor($receptionist))
            ->getJson('/api/v1/patients')
            ->assertOk();

        $this->assertSame(
            ['Adams', 'Baker', 'Carter'],
            array_column($response->json('data'), 'last_name'),
        );
    }

    public function test_list_can_sort_by_another_column_descending(): void
    {
        $this->makePatient(['first_name' => 'Belén', 'last_name' => 'Adams', 'code' => 'PAC-SANMAR-0001']);
        $this->makePatient(['first_name' => 'Zulma', 'last_name' => 'Baker', 'code' => 'PAC-SANMAR-0002']);
        $receptionist = $this->userWithRole('Recepcionista');

        $response = $this->withHeaders($this->headersFor($receptionist))
            ->getJson('/api/v1/patients?sort_by=first_name&sort_dir=desc')
            ->assertOk();

        $this->assertSame(
            ['Zulma', 'Belén'],
            array_column($response->json('data'), 'first_name'),
        );
    }

    public function test_list_ignores_an_unknown_sort_column(): void
    {
        $this->searchFixtures();
        $receptionist = $this->userWithRole('Recepcionista');

        // 'birth_date; drop table patients' debe caer al valor por defecto.
        $this->withHeaders($this->headersFor($receptionist))
            ->getJson('/api/v1/patients?sort_by='.urlencode('birth_date; drop table patients'))
            ->assertOk()
            ->assertJsonPath('total', 3);
    }

    public function test_search_ignores_case(): void
    {
        $this->searchFixtures();
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->getJson('/api/v1/patients?q=ana')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', 'Ana');
    }

    public function test_search_accepts_both_search_and_q_parameters(): void
    {
        $this->searchFixtures();
        $receptionist = $this->userWithRole('Recepcionista');
        $headers = $this->headersFor($receptionist);

        $this->withHeaders($headers)->getJson('/api/v1/patients?search=Luis')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->withHeaders($headers)->getJson('/api/v1/patients?q=Luis')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_search_matches_last_name_dpi_code_and_phone(): void
    {
        $this->searchFixtures();
        $receptionist = $this->userWithRole('Recepcionista');
        $headers = $this->headersFor($receptionist);

        // apellido con acento, buscado tal cual
        $this->withHeaders($headers)->getJson('/api/v1/patients?search='.urlencode('López'))
            ->assertOk()->assertJsonCount(1, 'data');

        $this->withHeaders($headers)->getJson('/api/v1/patients?search=2000000000002')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->withHeaders($headers)->getJson('/api/v1/patients?search=PAC-SANMAR-0003')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->withHeaders($headers)->getJson('/api/v1/patients?search=5555-1001')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_search_ignores_accents_on_postgres(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('La búsqueda sin acentos solo aplica en PostgreSQL.');
        }

        $this->searchFixtures();
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->getJson('/api/v1/patients?search=lopez')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withHeaders($this->headersFor($receptionist))
            ->getJson('/api/v1/patients?search=maria')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_empty_search_returns_every_patient(): void
    {
        $this->searchFixtures();
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->getJson('/api/v1/patients?search=')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    // ── Detalle ───────────────────────────────────────────────────────────

    public function test_show_returns_the_patient_with_the_medical_record_summary(): void
    {
        $patient = $this->makePatient(['first_name' => 'Ana', 'last_name' => 'Ríos']);
        MedicalRecord::factory()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $patient->id,
            'record_number' => 'EXP-SANMAR-00001',
        ]);
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->getJson("/api/v1/patients/{$patient->id}")
            ->assertOk()
            ->assertJsonPath('patient.id', $patient->id)
            ->assertJsonPath('patient.first_name', 'Ana')
            ->assertJsonPath('patient.medical_record.record_number', 'EXP-SANMAR-00001');
    }

    public function test_show_returns_null_medical_record_when_the_patient_has_none(): void
    {
        $patient = $this->makePatient();
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->getJson("/api/v1/patients/{$patient->id}")
            ->assertOk()
            ->assertJsonPath('patient.medical_record', null);
    }

    public function test_show_hides_a_medical_record_belonging_to_another_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        $patient = $this->makePatient();

        // Expediente huérfano de otro hospital: no debe asomarse por la relación.
        MedicalRecord::factory()->create([
            'tenant_id' => $otherTenant->id,
            'patient_id' => $patient->id,
            'record_number' => 'EXP-OTRO-00001',
        ]);

        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->getJson("/api/v1/patients/{$patient->id}")
            ->assertOk()
            ->assertJsonPath('patient.medical_record', null);
    }

    public function test_show_returns_404_for_an_unknown_patient(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->getJson('/api/v1/patients/999999')
            ->assertStatus(404);
    }

    public function test_show_returns_404_for_a_soft_deleted_patient(): void
    {
        $patient = $this->makePatient();
        $patient->delete();
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->getJson("/api/v1/patients/{$patient->id}")
            ->assertStatus(404);
    }

    // ── Alta ──────────────────────────────────────────────────────────────

    public function test_receptionist_can_create_a_patient(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/patients', $this->payload([
                'dpi' => '9999999999999',
                'blood_type' => 'O+',
                'emergency_contact_name' => 'Luis Ríos',
                'emergency_contact_phone' => '5555-9999',
            ]))
            ->assertCreated()
            ->assertJsonPath('patient.first_name', 'Ana')
            ->assertJsonPath('patient.tenant_id', $this->tenant->id)
            ->assertJsonPath('patient.medical_record', null);

        $this->assertDatabaseHas('patients', [
            'tenant_id' => $this->tenant->id,
            'dpi' => '9999999999999',
            'blood_type' => 'O+',
        ]);
    }

    public function test_create_generates_a_correlative_code_when_none_is_sent(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/patients', $this->payload())
            ->assertCreated()
            ->assertJsonPath('patient.code', 'PAC-SANMAR-0001');
    }

    public function test_create_generates_a_different_code_for_each_patient(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');
        $headers = $this->headersFor($receptionist);

        $first = $this->withHeaders($headers)->postJson('/api/v1/patients', $this->payload())
            ->assertCreated()->json('patient.code');

        $second = $this->withHeaders($headers)->postJson('/api/v1/patients', $this->payload(['first_name' => 'Luis']))
            ->assertCreated()->json('patient.code');

        $this->assertSame('PAC-SANMAR-0001', $first);
        $this->assertSame('PAC-SANMAR-0002', $second);
        $this->assertNotSame($first, $second);
    }

    public function test_create_never_reuses_the_code_of_a_soft_deleted_patient(): void
    {
        // patients.code tiene la restricción dura uq_patients_tenant_code: un
        // paciente borrado lógicamente sigue ocupando su código.
        $this->makePatient(['code' => 'PAC-SANMAR-0001', 'first_name' => 'Borrado'])->delete();

        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/patients', $this->payload())
            ->assertCreated()
            ->assertJsonPath('patient.code', 'PAC-SANMAR-0002');
    }

    public function test_create_resumes_the_sequence_after_a_manual_code(): void
    {
        $this->makePatient(['code' => 'PAC-SANMAR-0007']);

        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/patients', $this->payload())
            ->assertCreated()
            ->assertJsonPath('patient.code', 'PAC-SANMAR-0008');
    }

    public function test_create_accepts_an_explicit_code(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/patients', $this->payload(['code' => 'PAC-LEGACY-99']))
            ->assertCreated()
            ->assertJsonPath('patient.code', 'PAC-LEGACY-99');
    }

    public function test_create_rejects_a_code_already_used_in_the_same_tenant(): void
    {
        $this->makePatient(['code' => 'PAC-SANMAR-0001']);
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/patients', $this->payload(['code' => 'PAC-SANMAR-0001']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_create_allows_the_same_code_in_another_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        Patient::factory()->create([
            'tenant_id' => $otherTenant->id,
            'code' => 'PAC-SHARED-01',
        ]);
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/patients', $this->payload(['code' => 'PAC-SHARED-01']))
            ->assertCreated()
            ->assertJsonPath('patient.code', 'PAC-SHARED-01');
    }

    public function test_create_rejects_missing_required_data(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/patients', ['last_name' => 'Solo Apellido'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'birth_date', 'gender']);
    }

    public function test_create_rejects_a_future_birth_date(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/patients', $this->payload(['birth_date' => '2099-01-01']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('birth_date');
    }

    public function test_create_rejects_gender_outside_the_database_enum(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');

        // Si el enum no se validara, PostgreSQL devolvería un error SQL 500.
        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/patients', $this->payload(['gender' => 'X']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('gender');
    }

    public function test_create_rejects_blood_type_outside_the_database_enum(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/patients', $this->payload(['blood_type' => 'Z+']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('blood_type');
    }

    public function test_create_rejects_a_malformed_dpi_and_email(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/patients', $this->payload([
                'dpi' => '123',
                'email' => 'no-es-un-correo',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['dpi', 'email']);
    }

    public function test_create_rejects_values_longer_than_the_column(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/patients', $this->payload([
                'first_name' => str_repeat('a', 101),
                'insurance_policy' => str_repeat('b', 51),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'insurance_policy']);
    }

    // ── Edición ───────────────────────────────────────────────────────────

    public function test_receptionist_can_update_a_patient(): void
    {
        $patient = $this->makePatient(['first_name' => 'Ana', 'phone' => '5555-1111']);
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->putJson("/api/v1/patients/{$patient->id}", $this->payload([
                'first_name' => 'Ana María',
                'phone' => '5555-2222',
            ]))
            ->assertOk()
            ->assertJsonPath('patient.first_name', 'Ana María')
            ->assertJsonPath('patient.phone', '5555-2222');

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'first_name' => 'Ana María',
        ]);
    }

    public function test_update_keeps_the_code_when_it_is_not_sent(): void
    {
        $patient = $this->makePatient(['code' => 'PAC-SANMAR-0005']);
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->putJson("/api/v1/patients/{$patient->id}", $this->payload())
            ->assertOk()
            ->assertJsonPath('patient.code', 'PAC-SANMAR-0005');
    }

    public function test_update_accepts_keeping_its_own_code(): void
    {
        $patient = $this->makePatient(['code' => 'PAC-SANMAR-0005']);
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->putJson("/api/v1/patients/{$patient->id}", $this->payload(['code' => 'PAC-SANMAR-0005']))
            ->assertOk()
            ->assertJsonPath('patient.code', 'PAC-SANMAR-0005');
    }

    public function test_update_rejects_a_code_used_by_another_patient_of_the_same_tenant(): void
    {
        $this->makePatient(['code' => 'PAC-SANMAR-0001']);
        $patient = $this->makePatient(['code' => 'PAC-SANMAR-0002']);
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->putJson("/api/v1/patients/{$patient->id}", $this->payload(['code' => 'PAC-SANMAR-0001']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_update_rejects_missing_required_data(): void
    {
        $patient = $this->makePatient();
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->putJson("/api/v1/patients/{$patient->id}", ['phone' => '5555-1111'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'birth_date', 'gender']);
    }

    public function test_update_returns_404_for_an_unknown_patient(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->putJson('/api/v1/patients/999999', $this->payload())
            ->assertStatus(404);
    }

    // ── Autorización ──────────────────────────────────────────────────────

    public function test_all_patient_endpoints_require_authentication(): void
    {
        $patient = $this->makePatient();

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/v1/patients')->assertStatus(401);

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/patients', $this->payload())->assertStatus(401);

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/v1/patients/{$patient->id}")->assertStatus(401);

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->putJson("/api/v1/patients/{$patient->id}", $this->payload())->assertStatus(401);
    }

    public function test_user_without_any_role_cannot_reach_patients(): void
    {
        $patient = $this->makePatient();
        $user = $this->userWithRole(null);
        $headers = $this->headersFor($user);

        $this->withHeaders($headers)->getJson('/api/v1/patients')->assertStatus(403);
        $this->withHeaders($headers)->getJson("/api/v1/patients/{$patient->id}")->assertStatus(403);
        $this->withHeaders($headers)->postJson('/api/v1/patients', $this->payload())->assertStatus(403);
        $this->withHeaders($headers)->putJson("/api/v1/patients/{$patient->id}", $this->payload())->assertStatus(403);
    }

    public function test_nurse_can_read_but_cannot_create_or_update_patients(): void
    {
        // El rol Enfermera tiene pacientes.ver pero no pacientes.crear/editar.
        $patient = $this->makePatient();
        $nurse = $this->userWithRole('Enfermera');
        $headers = $this->headersFor($nurse);

        $this->withHeaders($headers)->getJson('/api/v1/patients')->assertOk();
        $this->withHeaders($headers)->getJson("/api/v1/patients/{$patient->id}")->assertOk();
        $this->withHeaders($headers)->postJson('/api/v1/patients', $this->payload())->assertStatus(403);
        $this->withHeaders($headers)->putJson("/api/v1/patients/{$patient->id}", $this->payload())->assertStatus(403);
    }

    public function test_admin_can_create_and_update_patients(): void
    {
        $admin = $this->userWithRole('Admin');
        $headers = $this->headersFor($admin);

        $id = $this->withHeaders($headers)->postJson('/api/v1/patients', $this->payload())
            ->assertCreated()->json('patient.id');

        $this->withHeaders($headers)
            ->putJson("/api/v1/patients/{$id}", $this->payload(['first_name' => 'Administrada']))
            ->assertOk()
            ->assertJsonPath('patient.first_name', 'Administrada');
    }

    public function test_token_from_another_tenant_is_rejected(): void
    {
        $otherTenant = Tenant::factory()->create();
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist, $otherTenant))
            ->getJson('/api/v1/patients')
            ->assertStatus(403);
    }

    // ── Aislamiento entre hospitales ──────────────────────────────────────

    public function test_list_and_search_do_not_leak_patients_of_another_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();

        $this->makePatient(['first_name' => 'Ana', 'code' => 'PAC-SANMAR-0001']);
        Patient::factory()->create([
            'tenant_id' => $otherTenant->id,
            'first_name' => 'Ana',
            'code' => 'PAC-OTRO-0001',
        ]);

        $receptionist = $this->userWithRole('Recepcionista');

        $response = $this->withHeaders($this->headersFor($receptionist))
            ->getJson('/api/v1/patients?search=Ana')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame('PAC-SANMAR-0001', $response->json('data.0.code'));
    }

    public function test_show_and_update_return_404_for_a_patient_of_another_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        $foreign = Patient::factory()->create([
            'tenant_id' => $otherTenant->id,
            'first_name' => 'Intrusa',
        ]);
        $receptionist = $this->userWithRole('Recepcionista');
        $headers = $this->headersFor($receptionist);

        $this->withHeaders($headers)->getJson("/api/v1/patients/{$foreign->id}")->assertStatus(404);
        $this->withHeaders($headers)->putJson("/api/v1/patients/{$foreign->id}", $this->payload())->assertStatus(404);

        $this->assertDatabaseHas('patients', [
            'id' => $foreign->id,
            'tenant_id' => $otherTenant->id,
            'first_name' => 'Intrusa',
        ]);
    }

    public function test_create_ignores_a_tenant_id_sent_by_the_client(): void
    {
        $otherTenant = Tenant::factory()->create();
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->postJson('/api/v1/patients', $this->payload([
                'tenant_id' => $otherTenant->id,
                'first_name' => 'Ana',
            ]))
            ->assertCreated()
            ->assertJsonPath('patient.tenant_id', $this->tenant->id);

        $this->assertDatabaseHas('patients', [
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Ana',
        ]);
        $this->assertDatabaseMissing('patients', [
            'tenant_id' => $otherTenant->id,
            'first_name' => 'Ana',
        ]);
    }

    public function test_update_ignores_a_tenant_id_sent_by_the_client(): void
    {
        $otherTenant = Tenant::factory()->create();
        $patient = $this->makePatient(['first_name' => 'Ana']);
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->putJson("/api/v1/patients/{$patient->id}", $this->payload([
                'tenant_id' => $otherTenant->id,
                'first_name' => 'Ana María',
            ]))
            ->assertOk()
            ->assertJsonPath('patient.tenant_id', $this->tenant->id);

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Ana María',
        ]);
    }
}
