<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->tenant = Tenant::factory()->create();
    }

    public function test_authorized_user_can_list_hospital_users_with_roles(): void
    {
        $admin = $this->userWithRole('Admin');
        $nurse = $this->userWithRole('Enfermera', name: 'Ana Enfermera');

        $response = $this->withHeaders($this->headersFor($admin))
            ->getJson('/api/v1/users');

        $listedNurse = collect($response->json('data'))->firstWhere('id', $nurse->id);

        $response->assertOk()
            ->assertJsonPath('total', 2);

        $this->assertNotNull($listedNurse);
        $this->assertSame('Ana Enfermera', $listedNurse['name']);
        $this->assertSame($nurse->roles->first()->id, $listedNurse['roles'][0]['id']);
        $this->assertSame('Enfermera', $listedNurse['roles'][0]['name']);

        foreach ($response->json('data') as $user) {
            $this->assertSame(['email', 'id', 'name', 'roles'], $this->sortedKeys($user));

            foreach ($user['roles'] as $role) {
                $this->assertSame(['id', 'name'], $this->sortedKeys($role));
            }
        }
    }

    public function test_list_requires_authentication(): void
    {
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/v1/users')
            ->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_list_users(): void
    {
        $receptionist = $this->userWithRole('Recepcionista');

        $this->withHeaders($this->headersFor($receptionist))
            ->getJson('/api/v1/users')
            ->assertForbidden();
    }

    public function test_list_and_search_are_isolated_by_hospital(): void
    {
        $admin = $this->userWithRole('Admin');
        $local = $this->userWithRole('Enfermera', name: 'Usuario Compartido Local');
        $otherTenant = Tenant::factory()->create();
        $foreign = $this->userWithRole('Enfermera', $otherTenant, 'Usuario Compartido Externo');

        $listResponse = $this->withHeaders($this->headersFor($admin))
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonPath('total', 2);

        $this->assertNotContains($foreign->id, collect($listResponse->json('data'))->pluck('id'));

        $searchResponse = $this->withHeaders($this->headersFor($admin))
            ->getJson('/api/v1/users?q=compartido')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $local->id);

        $this->assertNotContains($foreign->id, collect($searchResponse->json('data'))->pluck('id'));

        $this->withHeaders($this->headersFor($admin))
            ->getJson('/api/v1/users?tenant_id='.$otherTenant->id)
            ->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_users_can_be_searched_by_name_and_email(): void
    {
        $admin = $this->userWithRole('Admin');
        $user = $this->userWithRole('Enfermera', name: 'María Buscable');
        $user->update(['email' => 'correo.especifico@hospital.test']);

        $this->withHeaders($this->headersFor($admin))
            ->getJson('/api/v1/users?q=BUSCABLE')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $user->id);

        $this->withHeaders($this->headersFor($admin))
            ->getJson('/api/v1/users?q=correo.especifico')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $user->id);
    }

    public function test_zero_is_treated_as_a_valid_search_term(): void
    {
        $admin = $this->userWithRole('Admin', name: 'Administrador');
        $admin->update(['email' => 'admin@hospital.test']);
        $matching = $this->userWithRole('Enfermera', name: 'Usuario 0');
        $matching->update(['email' => 'cero@hospital.test']);
        $other = $this->userWithRole('Enfermera', name: 'Usuario Uno');
        $other->update(['email' => 'uno@hospital.test']);

        $response = $this->withHeaders($this->headersFor($admin))
            ->getJson('/api/v1/users?q=0')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $matching->id);

        $this->assertNotContains($other->id, collect($response->json('data'))->pluck('id'));
    }

    public function test_token_from_another_hospital_is_rejected(): void
    {
        $admin = $this->userWithRole('Admin');
        $otherTenant = Tenant::factory()->create();

        $this->withHeaders($this->headersFor($admin, $otherTenant))
            ->getJson('/api/v1/users')
            ->assertForbidden();
    }

    public function test_query_parameters_are_validated(): void
    {
        $admin = $this->userWithRole('Admin');

        $invalidQueries = [
            '?q='.str_repeat('a', 101),
            '?q[]=texto',
            '?per_page=0',
            '?per_page=51',
            '?per_page=texto',
            '?page=0',
            '?page=texto',
        ];

        foreach ($invalidQueries as $query) {
            $this->withHeaders($this->headersFor($admin))
                ->getJson('/api/v1/users'.$query)
                ->assertUnprocessable();
        }
    }

    public function test_list_is_paginated_and_does_not_expose_sensitive_fields(): void
    {
        $admin = $this->userWithRole('Admin');
        User::factory()->count(16)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeaders($this->headersFor($admin))
            ->getJson('/api/v1/users?per_page=5&page=2');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('current_page', 2)
            ->assertJsonPath('per_page', 5)
            ->assertJsonPath('last_page', 4)
            ->assertJsonPath('total', 17);

        $json = $response->json();
        $encoded = json_encode($json, JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('password', $encoded);
        $this->assertStringNotContainsString('remember_token', $encoded);
        $this->assertStringNotContainsString('access_token', $encoded);
        $this->assertStringNotContainsString('guard_name', $encoded);
        $this->assertStringNotContainsString('pivot', $encoded);
    }

    /** @return array<string, string> */
    private function headersFor(User $user, ?Tenant $tenant = null): array
    {
        return [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($user),
            'X-Tenant-ID' => ($tenant ?? $this->tenant)->id,
        ];
    }

    private function userWithRole(
        string $role,
        ?Tenant $tenant = null,
        ?string $name = null,
    ): User {
        $user = User::factory()->create([
            'tenant_id' => ($tenant ?? $this->tenant)->id,
            'name' => $name ?? fake()->name(),
        ]);
        $user->assignRole($role);

        return $user;
    }

    /** @return list<string> */
    private function sortedKeys(array $value): array
    {
        $keys = array_keys($value);
        sort($keys);

        return $keys;
    }
}
