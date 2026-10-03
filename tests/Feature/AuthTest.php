<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
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

    public function test_malformed_token_is_rejected(): void
    {
        $this->withHeaders([
            'Authorization' => 'Bearer token-invalido',
            'X-Tenant-ID' => $this->tenant->id,
        ])->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_token_with_altered_signature_is_rejected(): void
    {
        $user = $this->userWithRole('Médico');
        [$header, $payload, $signature] = explode('.', JWTAuth::fromUser($user));
        $signature[0] = $signature[0] === 'a' ? 'b' : 'a';

        $this->withHeaders([
            'Authorization' => "Bearer {$header}.{$payload}.{$signature}",
            'X-Tenant-ID' => $this->tenant->id,
        ])->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_token_for_missing_user_is_rejected(): void
    {
        $user = $this->userWithRole('Médico');
        $headers = $this->headersFor($user);
        $user->delete();

        $this->withHeaders($headers)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
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

    public function test_same_email_can_exist_in_different_hospitals_but_not_twice_in_one(): void
    {
        $otherTenant = Tenant::factory()->create();
        $admin = $this->userWithRole('Admin');
        $otherAdmin = $this->userWithRole('Admin', $otherTenant);
        $payload = [
            'name' => 'Usuario Compartido',
            'email' => 'compartido@demo.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->withHeaders($this->headersFor($admin))
            ->postJson('/api/v1/auth/register', $payload)
            ->assertCreated();

        $this->withHeaders($this->headersFor($otherAdmin, $otherTenant))
            ->postJson('/api/v1/auth/register', $payload)
            ->assertCreated();

        $duplicate = $payload;
        $duplicate['email'] = strtoupper($payload['email']);

        $this->withHeaders($this->headersFor($admin))
            ->postJson('/api/v1/auth/register', $duplicate)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseHas('users', [
            'tenant_id' => $this->tenant->id,
            'email' => $payload['email'],
        ]);
        $this->assertDatabaseHas('users', [
            'tenant_id' => $otherTenant->id,
            'email' => $payload['email'],
        ]);
    }

    public function test_login_normalizes_email_case(): void
    {
        $user = $this->userWithRole('Médico');

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/auth/login', [
                'email' => strtoupper($user->email),
                'password' => 'password',
            ])
            ->assertOk();
    }

    public function test_token_can_be_refreshed_and_keeps_tenant_isolation(): void
    {
        $user = $this->userWithRole('Médico');

        $token = $this->withHeaders($this->headersFor($user))
            ->postJson('/api/v1/auth/refresh')
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in'])
            ->json('access_token');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-ID' => $this->tenant->id,
        ])->getJson('/api/v1/auth/me')
            ->assertOk();
    }

    public function test_expired_token_can_be_refreshed_within_refresh_period(): void
    {
        $user = $this->userWithRole('Médico');
        $issuedAt = Carbon::parse('2026-01-01 00:00:00', 'UTC');
        Carbon::setTestNow($issuedAt);
        $headers = $this->headersFor($user);
        Carbon::setTestNow($issuedAt->copy()->addMinutes((int) config('jwt.ttl') + 1));

        try {
            $this->withHeaders($headers)
                ->postJson('/api/v1/auth/refresh')
                ->assertOk()
                ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_token_outside_refresh_period_is_rejected(): void
    {
        $user = $this->userWithRole('Médico');
        $issuedAt = Carbon::parse('2026-01-01 00:00:00', 'UTC');
        Carbon::setTestNow($issuedAt);
        $headers = $this->headersFor($user);
        Carbon::setTestNow($issuedAt->copy()->addMinutes((int) config('jwt.refresh_ttl') + 1));

        try {
            $this->withHeaders($headers)
                ->postJson('/api/v1/auth/refresh')
                ->assertUnauthorized();
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_refresh_rejects_token_invalidated_by_logout(): void
    {
        $user = $this->userWithRole('Médico');
        $headers = $this->headersFor($user);

        $this->withHeaders($headers)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->withHeaders($headers)
            ->postJson('/api/v1/auth/refresh')
            ->assertUnauthorized();
    }

    public function test_refresh_rejects_token_from_another_hospital(): void
    {
        $user = $this->userWithRole('Médico');
        $otherTenant = Tenant::factory()->create();

        $this->withHeaders($this->headersFor($user, $otherTenant))
            ->postJson('/api/v1/auth/refresh')
            ->assertForbidden();
    }

    public function test_refresh_rejects_token_for_missing_user(): void
    {
        $user = $this->userWithRole('Médico');
        $headers = $this->headersFor($user);
        $user->delete();

        $this->withHeaders($headers)
            ->postJson('/api/v1/auth/refresh')
            ->assertUnauthorized();
    }
}
