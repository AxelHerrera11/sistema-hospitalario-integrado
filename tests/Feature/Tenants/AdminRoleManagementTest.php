<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AdminRoleManagementTest extends TenantAuthTestCase
{
    public function test_admin_assigns_laboratory_roles_and_revocation_invalidates_their_tokens(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'admin_hospital');
        $adminToken = $this->tokenFrom($this->login()->assertOk());
        foreach (['tecnico_laboratorio', 'bioquimico'] as $role) {
            $email = $role.'@example.test';
            $targetId = $this->createTarget($email);
            $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])->withToken($adminToken)
                ->postJson("/api/v1/admin/users/{$targetId}/roles", ['role_code' => $role])
                ->assertCreated()->assertJsonPath('roles', [$role]);
            $login = $this->login($email)->assertOk()->assertJsonPath('roles', [$role]);
            $targetToken = $this->tokenFrom($login);
            $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])->withToken($targetToken)
                ->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('roles', [$role]);
            $this->withHeaders(['X-Tenant-ID' => $this->hospitalB])->withToken($targetToken)
                ->getJson('/api/v1/auth/me')->assertForbidden()->assertJsonPath('error', 'TENANT_MISMATCH');
            $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])->withToken($adminToken)
                ->deleteJson("/api/v1/admin/users/{$targetId}/roles/{$role}")
                ->assertOk()->assertJsonPath('roles', []);
            $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])->withToken($targetToken)
                ->getJson('/api/v1/auth/me')->assertForbidden()->assertJsonPath('error', 'NO_ACTIVE_ROLES');
        }
    }

    public function test_superadmin_assigns_role_and_revokes_target_sessions_with_audit(): void
    {
        $targetId = $this->createTarget('target@example.test');
        $this->addMembership($this->userId, $this->hospitalA, 'superadmin');

        $token = $this->tokenFrom($this->login()->assertOk());

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->postJson("/api/v1/admin/users/{$targetId}/roles", ['role_code' => 'medico'])
            ->assertCreated()
            ->assertJsonPath('user_id', $targetId)
            ->assertJsonPath('role_code', 'medico')
            ->assertJsonPath('roles', ['medico']);

        $this->assertDatabaseHas('hospital_memberships', [
            'user_id' => $targetId,
            'hospital_id' => $this->hospitalA,
            'role_code' => 'medico',
            'status' => 'active',
        ], 'central');

        $audit = DB::connection('central')->table('audit_log')
            ->where('event', 'user.role_assigned')
            ->first();
        $detail = json_decode((string) $audit->detail, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame($this->userId, $audit->actor_id);
        $this->assertSame($this->hospitalA, $audit->hospital_id);
        $this->assertSame($targetId, $detail['target_user_id']);
        $this->assertSame('rbac.asignar', $detail['permission']);
        $this->assertSame('role_assigned', $detail['reason']);
        $this->assertNotEmpty($audit->trace_id);
    }

    public function test_admin_hospital_cannot_grant_superadmin(): void
    {
        $targetId = $this->createTarget('target@example.test');
        $this->addMembership($this->userId, $this->hospitalA, 'admin_hospital');

        $token = $this->tokenFrom($this->login()->assertOk());

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->postJson("/api/v1/admin/users/{$targetId}/roles", ['role_code' => 'superadmin'])
            ->assertStatus(403)
            ->assertJsonPath('error', 'SUPERADMIN_REQUIRES_SUPERADMIN');

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->postJson("/api/v1/admin/users/{$targetId}/roles", ['role_code' => 'Medico'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'ROLE_INVALID');
    }

    public function test_admin_cannot_change_own_roles(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'superadmin');
        $token = $this->tokenFrom($this->login()->assertOk());

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->postJson("/api/v1/admin/users/{$this->userId}/roles", ['role_code' => 'medico'])
            ->assertStatus(403)
            ->assertJsonPath('error', 'SELF_ROLE_CHANGE');

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->deleteJson("/api/v1/admin/users/{$this->userId}/roles/superadmin")
            ->assertStatus(403)
            ->assertJsonPath('error', 'SELF_ROLE_CHANGE');
    }

    public function test_revoke_role_invalidates_target_token_and_records_audit(): void
    {
        $targetId = $this->createTarget('target@example.test');
        $this->addMembership($targetId, $this->hospitalA, 'medico');
        $this->addMembership($targetId, $this->hospitalA, 'recepcion');
        $this->addMembership($this->userId, $this->hospitalA, 'superadmin');

        $targetToken = $this->tokenFrom($this->login('target@example.test')->assertOk());
        $adminToken = $this->tokenFrom($this->login()->assertOk());

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($adminToken)
            ->deleteJson("/api/v1/admin/users/{$targetId}/roles/medico")
            ->assertOk()
            ->assertJsonPath('role_code', 'medico')
            ->assertJsonPath('roles', ['recepcion']);

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($targetToken)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error', 'ROLES_CHANGED');

        $this->assertDatabaseHas('auth_sessions', [
            'user_id' => $targetId,
            'hospital_id' => $this->hospitalA,
            'revoked_reason' => 'roles_changed',
        ], 'central');

        $this->assertDatabaseHas('audit_log', [
            'event' => 'user.role_revoked',
            'actor_id' => $this->userId,
            'hospital_id' => $this->hospitalA,
        ], 'central');
    }

    public function test_approved_admin_capabilities_reach_their_authorized_operations(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'superadmin');
        $token = $this->tokenFrom($this->login()->assertOk());

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonPath('data.0.user_id', $this->userId)
            ->assertJsonPath('data.0.roles', ['superadmin']);

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->postJson('/api/v1/admin/tenants', [
                'network_id' => $this->networkId,
                'code' => 'new-hospital',
                'name' => 'Nuevo hospital',
                'timezone' => 'America/Guatemala',
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'inactive')
            ->assertJsonPath('provisioning_required', true);

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->deleteJson('/api/v1/admin/sessions/'.Str::uuid())
            ->assertNotFound()
            ->assertJsonPath('error', 'SESSION_NOT_FOUND');
    }

    public function test_admin_hospital_can_only_use_its_approved_admin_permissions(): void
    {
        $targetId = $this->createTarget('target@example.test');
        $this->addMembership($targetId, $this->hospitalA, 'medico');
        $this->addMembership($this->userId, $this->hospitalA, 'admin_hospital');
        $token = $this->tokenFrom($this->login()->assertOk());

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonPath('data.0.user_id', $this->userId);

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->postJson('/api/v1/admin/tenants')
            ->assertStatus(403)
            ->assertJsonPath('code', 'RBAC_DENIED');

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->deleteJson('/api/v1/admin/sessions/'.Str::uuid())
            ->assertNotFound()
            ->assertJsonPath('error', 'SESSION_NOT_FOUND');

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->postJson("/api/v1/admin/users/{$targetId}/deactivate")
            ->assertOk()
            ->assertJsonPath('message', 'Acceso del usuario al hospital revocado.');
    }

    private function createTarget(string $email): string
    {
        $targetId = (string) Str::uuid();
        $this->createUser($targetId, $email, 'active', 'Target');
        $this->createCredential($targetId, $email, 'secret-password');

        return $targetId;
    }
}
