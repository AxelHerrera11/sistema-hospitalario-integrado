<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants;

use Illuminate\Support\Facades\DB;

final class CreateHospitalUserTest extends TenantAuthTestCase
{
    private const PASSWORD = 'Temporal-2026!x';

    public function test_admin_creates_user_with_first_role_and_the_new_user_can_log_in(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'admin_hospital');

        $response = $this->createUserRequest(['email' => '  Nueva.Medica@Example.TEST '])
            ->assertCreated()
            ->assertJsonPath('email', 'nueva.medica@example.test')
            ->assertJsonPath('hospital_id', $this->hospitalA)
            ->assertJsonPath('roles', ['medico']);
        $newUserId = $response->json('user_id');

        $this->assertArrayNotHasKey('password', $response->json());
        $this->assertDatabaseHas('central_users', ['user_id' => $newUserId, 'status' => 'active'], 'central');
        $this->assertDatabaseHas('hospital_memberships', [
            'user_id' => $newUserId,
            'hospital_id' => $this->hospitalA,
            'role_code' => 'medico',
            'status' => 'active',
        ], 'central');
        $this->assertDatabaseMissing('hospital_memberships', [
            'user_id' => $newUserId,
            'hospital_id' => $this->hospitalB,
        ], 'central');
        $hash = DB::connection('central')->table('auth_credentials')->where('user_id', $newUserId)->value('password_hash');
        $this->assertNotSame(self::PASSWORD, $hash);

        $event = DB::connection('central')->table('audit_log')->where('event', 'user.created')->first();
        $this->assertNotNull($event);
        $this->assertStringNotContainsString('nueva.medica', (string) $event->detail);

        $this->login('nueva.medica@example.test', self::PASSWORD)
            ->assertOk()
            ->assertJsonPath('roles', ['medico']);
    }

    public function test_duplicate_email_is_rejected_case_insensitively(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'admin_hospital');

        $this->createUserRequest(['email' => 'DOCTOR@example.test'])
            ->assertStatus(409)
            ->assertJsonPath('error', 'EMAIL_TAKEN');
    }

    public function test_weak_password_is_rejected_before_touching_the_database(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'admin_hospital');

        foreach (['corta1!A', 'sin-mayusculas-2026!', 'SinNumerosNiNada!', 'SinSimbolos2026x'] as $password) {
            $this->createUserRequest(['password' => $password])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('password');
        }

        $this->assertDatabaseMissing('central_users', ['email' => 'nuevo@example.test'], 'central');
    }

    public function test_only_superadmin_can_create_a_superadmin(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'admin_hospital');

        $this->createUserRequest(['role_code' => 'superadmin'])
            ->assertForbidden()
            ->assertJsonPath('error', 'SUPERADMIN_REQUIRES_SUPERADMIN');
        $this->createUserRequest(['role_code' => 'Medico'])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'ROLE_INVALID');
        $this->assertDatabaseMissing('central_users', ['email' => 'nuevo@example.test'], 'central');
    }

    public function test_role_without_usuarios_crear_is_denied(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico');

        $this->createUserRequest()
            ->assertForbidden()
            ->assertJsonPath('code', 'RBAC_DENIED');
    }

    public function test_admin_of_another_hospital_cannot_create_users_here(): void
    {
        $this->addMembership($this->userId, $this->hospitalB, 'admin_hospital');
        $this->addMembership($this->userId, $this->hospitalA, 'recepcion');

        $this->createUserRequest()
            ->assertForbidden()
            ->assertJsonPath('code', 'RBAC_DENIED');
        $this->assertDatabaseMissing('central_users', ['email' => 'nuevo@example.test'], 'central');
    }

    private function createUserRequest(array $overrides = [])
    {
        $token = $this->tokenFrom($this->login()->assertOk());

        return $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->postJson('/api/v1/admin/users', array_merge([
                'email' => 'nuevo@example.test',
                'display_name' => 'Dra. Nueva Prueba',
                'password' => self::PASSWORD,
                'role_code' => 'medico',
            ], $overrides));
    }
}
