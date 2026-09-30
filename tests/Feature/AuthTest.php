<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthTest extends TestCase
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
    private function headersFor(User $user, ?Tenant $tenant = null): array
    {
        return [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($user),
            'X-Tenant-ID' => ($tenant ?? $this->tenant)->id,
        ];
    }

    public function test_requests_without_tenant_header_are_rejected(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => 'x@x.com', 'password' => 'password'])
            ->assertStatus(400);
    }

    public function test_non_uuid_tenant_header_is_rejected(): void
    {
        $this->withHeader('X-Tenant-ID', 'hospital-1')
            ->postJson('/api/v1/auth/login', ['email' => 'x@x.com', 'password' => 'password'])
            ->assertStatus(400);
    }

    public function test_user_can_log_in_to_own_tenant(): void
    {
        $user = $this->userWithRole('Médico');

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['access_token', 'user']);
    }

    public function test_user_cannot_log_in_to_another_tenant(): void
    {
        $user = $this->userWithRole('Médico');
        $otherTenant = Tenant::factory()->create();

        $this->withHeader('X-Tenant-ID', $otherTenant->id)
            ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(422);
    }

    public function test_token_is_rejected_with_a_different_tenant_header(): void
    {
        $user = $this->userWithRole('Médico');
        $otherTenant = Tenant::factory()->create();

        $this->withHeaders($this->headersFor($user, $otherTenant))
            ->getJson('/api/v1/auth/me')
            ->assertStatus(403);
    }

    public function test_me_returns_role_permissions(): void
    {
        $user = $this->userWithRole('Recepcionista');

        $permissions = $this->withHeaders($this->headersFor($user))
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->json('permissions');

        $this->assertContains('pacientes.crear', $permissions);
        $this->assertNotContains('prescripciones.crear', $permissions);
    }

    public function test_register_requires_authentication(): void
    {
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/auth/register', [])
            ->assertStatus(401);
    }

    public function test_non_admin_cannot_register_users(): void
    {
        $user = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($user))
            ->postJson('/api/v1/auth/register', [
                'name' => 'Nuevo',
                'email' => 'nuevo@demo.local',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertStatus(403);
    }

    public function test_admin_can_register_user_with_role_in_own_tenant(): void
    {
        $admin = $this->userWithRole('Admin');

        $this->withHeaders($this->headersFor($admin))
            ->postJson('/api/v1/auth/register', [
                'name' => 'Enfermera Nueva',
                'email' => 'enfermera@demo.local',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'Enfermera',
            ])
            ->assertCreated();

        $created = User::query()->where('email', 'enfermera@demo.local')->firstOrFail();
        $this->assertSame($this->tenant->id, $created->tenant_id);
        $this->assertTrue($created->hasRole('Enfermera'));
    }
}
