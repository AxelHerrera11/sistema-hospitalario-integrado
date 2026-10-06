<?php

namespace Tests\Feature;

use App\Models\Bed;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Ward;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Salas y camas — Área 4.
 *
 * Cubre listado de salas, conteo de camas, filtros,
 * validaciones, permisos y aislamiento entre hospitales.
 */
class WardBedTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->tenant = Tenant::factory()->create([
            'slug' => 'san-marcos',
        ]);
    }

    private function userWithRole(?string $role, ?Tenant $tenant = null): User
    {
        $user = User::factory()->create([
            'tenant_id' => ($tenant ?? $this->tenant)->id,
        ]);

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

    private function makeWard(array $overrides = []): Ward
    {
        return Ward::factory()->create(array_merge([
            'tenant_id' => $this->tenant->id,
        ], $overrides));
    }

    private function makeBed(Ward $ward, array $overrides = []): Bed
    {
        return Bed::factory()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'ward_id' => $ward->id,
        ], $overrides));
    }

    public function test_can_list_wards_with_bed_counts(): void
    {
        $ward = $this->makeWard([
            'name' => 'Cirugía',
        ]);

        $this->makeBed($ward, [
            'code' => 'CAM-001',
            'status' => 'disponible',
        ]);

        $this->makeBed($ward, [
            'code' => 'CAM-002',
            'status' => 'ocupada',
        ]);

        $user = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($user))
            ->getJson('/api/v1/wards')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Cirugía')
            ->assertJsonPath('data.0.beds_count', 2)
            ->assertJsonPath('data.0.available_beds_count', 1);
    }

    public function test_can_list_beds_of_a_specific_ward(): void
    {
        $ward = $this->makeWard([
            'name' => 'Emergencias',
        ]);

        $this->makeBed($ward, [
            'code' => 'CAM-EME-01',
            'status' => 'ocupada',
        ]);

        $this->makeBed($ward, [
            'code' => 'CAM-EME-02',
            'status' => 'disponible',
        ]);

        $user = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($user))
            ->getJson("/api/v1/wards/{$ward->id}/beds")
            ->assertOk()
            ->assertJsonPath('ward.id', $ward->id)
            ->assertJsonPath('ward.name', 'Emergencias')
            ->assertJsonCount(2, 'beds');
    }

    public function test_can_filter_beds_by_status(): void
    {
        $ward = $this->makeWard();

        $this->makeBed($ward, [
            'code' => 'CAM-001',
            'status' => 'disponible',
        ]);

        $this->makeBed($ward, [
            'code' => 'CAM-002',
            'status' => 'ocupada',
        ]);

        $user = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($user))
            ->getJson('/api/v1/beds?status=disponible')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'CAM-001')
            ->assertJsonPath('data.0.status', 'disponible');
    }

    public function test_can_filter_beds_by_ward_and_status(): void
    {
        $wardA = $this->makeWard([
            'name' => 'Cirugía',
        ]);

        $wardB = $this->makeWard([
            'name' => 'UCI',
        ]);

        $this->makeBed($wardA, [
            'code' => 'CAM-CIR-01',
            'status' => 'disponible',
        ]);

        $this->makeBed($wardA, [
            'code' => 'CAM-CIR-02',
            'status' => 'ocupada',
        ]);

        $this->makeBed($wardB, [
            'code' => 'CAM-UCI-01',
            'status' => 'disponible',
        ]);

        $user = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($user))
            ->getJson("/api/v1/beds?ward_id={$wardA->id}&status=disponible")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'CAM-CIR-01')
            ->assertJsonPath('data.0.ward_id', $wardA->id)
            ->assertJsonPath('data.0.status', 'disponible');
    }

    public function test_rejects_an_invalid_bed_status(): void
    {
        $user = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($user))
            ->getJson('/api/v1/beds?status=reservada')
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_endpoints_require_authentication(): void
    {
        $ward = $this->makeWard();

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/v1/wards')
            ->assertStatus(401);

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/v1/beds')
            ->assertStatus(401);

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/v1/wards/{$ward->id}/beds")
            ->assertStatus(401);
    }

    public function test_user_without_role_cannot_access_wards_or_beds(): void
    {
        $user = $this->userWithRole(null);
        $headers = $this->headersFor($user);

        $this->withHeaders($headers)
            ->getJson('/api/v1/wards')
            ->assertStatus(403);

        $this->withHeaders($headers)
            ->getJson('/api/v1/beds')
            ->assertStatus(403);
    }

    public function test_wards_and_beds_do_not_leak_between_tenants(): void
    {
        $otherTenant = Tenant::factory()->create();

        $localWard = $this->makeWard([
            'name' => 'Hospital Local',
        ]);

        $this->makeBed($localWard, [
            'code' => 'CAM-LOCAL-01',
            'status' => 'disponible',
        ]);

        $foreignWard = Ward::factory()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Hospital Externo',
        ]);

        Bed::factory()->create([
            'tenant_id' => $otherTenant->id,
            'ward_id' => $foreignWard->id,
            'code' => 'CAM-EXTERNA-01',
            'status' => 'disponible',
        ]);

        $user = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($user))
            ->getJson('/api/v1/wards')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Hospital Local');

        $this->withHeaders($this->headersFor($user))
            ->getJson('/api/v1/beds')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'CAM-LOCAL-01');

        $this->withHeaders($this->headersFor($user))
            ->getJson("/api/v1/wards/{$foreignWard->id}/beds")
            ->assertStatus(404);
    }

    public function test_can_update_bed_status(): void
    {
        $ward = $this->makeWard();

        $bed = $this->makeBed($ward, [
            'code' => 'CAM-001',
            'status' => 'disponible',
        ]);

        $user = $this->userWithRole('Admin');

        $this->withHeaders($this->headersFor($user))
            ->patchJson("/api/v1/beds/{$bed->id}/status", [
                'status' => 'mantenimiento',
            ])
            ->assertOk()
            ->assertJsonPath('bed.id', $bed->id)
            ->assertJsonPath('bed.status', 'mantenimiento');

        $this->assertDatabaseHas('beds', [
            'id' => $bed->id,
            'status' => 'mantenimiento',
        ]);
    }

    public function test_update_bed_status_rejects_invalid_status(): void
    {
        $ward = $this->makeWard();

        $bed = $this->makeBed($ward, [
            'status' => 'disponible',
        ]);

        $user = $this->userWithRole('Admin');

        $this->withHeaders($this->headersFor($user))
            ->patchJson("/api/v1/beds/{$bed->id}/status", [
                'status' => 'reservada',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_user_without_management_permission_cannot_update_bed_status(): void
    {
        $ward = $this->makeWard();

        $bed = $this->makeBed($ward, [
            'status' => 'disponible',
        ]);

        $user = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($user))
            ->patchJson("/api/v1/beds/{$bed->id}/status", [
                'status' => 'mantenimiento',
            ])
            ->assertStatus(403);

        $this->assertDatabaseHas('beds', [
            'id' => $bed->id,
            'status' => 'disponible',
        ]);
    }
}
