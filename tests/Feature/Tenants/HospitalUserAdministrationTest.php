<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants;

use App\Modules\Tenants\Application\Administration\ManageHospitalMembership;
use App\Modules\Tenants\Domain\Administration\AdminFailure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class HospitalUserAdministrationTest extends TenantAuthTestCase
{
    public function test_hospital_user_listing_is_paged_and_isolated_from_other_hospitals(): void
    {
        $localUser = $this->createTarget('local@example.test');
        $remoteUser = $this->createTarget('remote@example.test');
        $this->addMembership($this->userId, $this->hospitalA, 'admin_hospital');
        $this->addMembership($localUser, $this->hospitalA, 'medico');
        $this->addMembership($remoteUser, $this->hospitalB, 'recepcion');
        $token = $this->tokenFrom($this->login()->assertOk());

        $response = $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->getJson('/api/v1/admin/users?per_page=1');

        $response->assertOk()
            ->assertJsonPath('per_page', 1)
            ->assertJsonPath('total', 2);
        $this->assertSame(1, count($response->json('data')));
        $this->assertArrayNotHasKey('password_hash', $response->json('data.0'));

        $allUsers = $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->getJson('/api/v1/admin/users?per_page=100')
            ->assertOk()
            ->json('data');

        $listedIds = array_column($allUsers, 'user_id');
        $this->assertContains($this->userId, $listedIds);
        $this->assertContains($localUser, $listedIds);
        $this->assertNotContains($remoteUser, $listedIds);
    }

    public function test_admin_session_revocation_is_limited_to_foreign_sessions_in_current_hospital(): void
    {
        $targetId = $this->createTarget('target@example.test');
        $this->addMembership($targetId, $this->hospitalA, 'medico');
        $this->addMembership($targetId, $this->hospitalB, 'recepcion');
        $this->addMembership($this->userId, $this->hospitalA, 'admin_hospital');
        $tokenA = $this->tokenFrom($this->login('target@example.test')->assertOk());
        $tokenB = $this->tokenFrom($this->login(
            'target@example.test',
            'secret-password',
            $this->hospitalB,
        )->assertOk());
        $adminToken = $this->tokenFrom($this->login()->assertOk());
        $sessions = DB::connection('central')->table('auth_sessions')
            ->where('user_id', $targetId)
            ->get()
            ->keyBy('hospital_id');

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($adminToken)
            ->deleteJson('/api/v1/admin/sessions/'.$sessions[$this->hospitalA]->session_id)
            ->assertOk();

        $this->assertDatabaseHas('auth_sessions', [
            'session_id' => $sessions[$this->hospitalA]->session_id,
            'revoked_reason' => 'admin_revoked',
        ], 'central');
        $this->assertDatabaseHas('auth_sessions', [
            'session_id' => $sessions[$this->hospitalB]->session_id,
            'revoked_at' => null,
        ], 'central');
        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($tokenA)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error', 'SESSION_REVOKED');
        $this->withHeaders(['X-Tenant-ID' => $this->hospitalB])
            ->withToken($tokenB)
            ->getJson('/api/v1/auth/me')
            ->assertOk();
    }

    public function test_admin_cannot_revoke_own_session(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'superadmin');
        $token = $this->tokenFrom($this->login()->assertOk());
        $session = DB::connection('central')->table('auth_sessions')
            ->where('user_id', $this->userId)
            ->first();

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->deleteJson('/api/v1/admin/sessions/'.$session->session_id)
            ->assertNotFound()
            ->assertJsonPath('error', 'SESSION_NOT_FOUND');
    }

    public function test_non_admin_role_cannot_use_administrative_capabilities(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico');
        $token = $this->tokenFrom($this->login()->assertOk());

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->getJson('/api/v1/admin/users')
            ->assertStatus(403)
            ->assertJsonPath('code', 'RBAC_DENIED');
    }

    public function test_rbac_denial_is_persisted_with_contract_fields(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico');
        $token = $this->tokenFrom($this->login()->assertOk());

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->getJson('/api/v1/permissions')
            ->assertStatus(403)
            ->assertJsonPath('code', 'RBAC_DENIED');

        $audit = DB::connection('central')->table('audit_log')
            ->where('event', 'rbac.access_denied')
            ->first();

        $this->assertNotNull($audit);
        $detail = json_decode((string) $audit->detail, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame($this->userId, $audit->actor_id);
        $this->assertSame($this->hospitalA, $audit->hospital_id);
        $this->assertSame($this->userId, $detail['user_id']);
        $this->assertSame($this->hospitalA, $detail['tenant_id']);
        $this->assertSame('rbac.permisos.ver', $detail['permission']);
        $this->assertSame('sin_permiso_de_rol', $detail['reason']);
        $this->assertNotEmpty($audit->trace_id);
    }

    public function test_deactivation_revokes_only_target_access_in_the_current_hospital(): void
    {
        $targetId = $this->createTarget('target@example.test');
        $this->addMembership($targetId, $this->hospitalA, 'medico');
        $this->addMembership($targetId, $this->hospitalB, 'recepcion');
        $this->addMembership($this->userId, $this->hospitalA, 'superadmin');

        $targetTokenA = $this->tokenFrom($this->login('target@example.test')->assertOk());
        $targetTokenB = $this->tokenFrom($this->login(
            'target@example.test',
            'secret-password',
            $this->hospitalB,
        )->assertOk());

        app(ManageHospitalMembership::class)->deactivateInHospital(
            $this->userId,
            $this->hospitalA,
            $targetId,
        );

        $this->assertDatabaseHas('central_users', [
            'user_id' => $targetId,
            'status' => 'active',
        ], 'central');
        $this->assertDatabaseHas('auth_sessions', [
            'user_id' => $targetId,
            'hospital_id' => $this->hospitalA,
            'revoked_reason' => 'hospital_access_revoked',
        ], 'central');
        $this->assertDatabaseHas('auth_sessions', [
            'user_id' => $targetId,
            'hospital_id' => $this->hospitalB,
            'revoked_at' => null,
        ], 'central');
        $this->assertDatabaseHas('hospital_memberships', [
            'user_id' => $targetId,
            'hospital_id' => $this->hospitalA,
            'status' => 'inactive',
        ], 'central');
        $this->assertDatabaseHas('hospital_memberships', [
            'user_id' => $targetId,
            'hospital_id' => $this->hospitalB,
            'status' => 'active',
        ], 'central');
        $this->assertDatabaseHas('audit_log', [
            'event' => 'user.hospital_memberships_deactivated',
            'actor_id' => $this->userId,
            'hospital_id' => $this->hospitalA,
        ], 'central');
        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($targetTokenA)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(403)
            ->assertJsonPath('error', 'NO_ACTIVE_ROLES');
        $this->withHeaders(['X-Tenant-ID' => $this->hospitalB])
            ->withToken($targetTokenB)
            ->getJson('/api/v1/auth/me')
            ->assertOk();
    }

    public function test_deactivation_requires_admin_membership_in_the_target_hospital(): void
    {
        $targetId = $this->createTarget('target@example.test');
        $this->addMembership($targetId, $this->hospitalA, 'medico');
        $this->addMembership($this->userId, $this->hospitalB, 'superadmin');

        try {
            app(ManageHospitalMembership::class)->deactivateInHospital(
                $this->userId,
                $this->hospitalA,
                $targetId,
            );
            $this->fail('An administrator cannot revoke access in another hospital.');
        } catch (AdminFailure $failure) {
            $this->assertSame('ACTOR_NO_ACTIVE_MEMBERSHIP', $failure->errorCode);
        }

        $this->assertDatabaseHas('central_users', [
            'user_id' => $targetId,
            'status' => 'active',
        ], 'central');
        $this->assertDatabaseHas('hospital_memberships', [
            'user_id' => $targetId,
            'hospital_id' => $this->hospitalA,
            'status' => 'active',
        ], 'central');
        $this->assertDatabaseMissing('audit_log', [
            'event' => 'user.hospital_memberships_deactivated',
            'actor_id' => $this->userId,
        ], 'central');
    }

    private function createTarget(string $email): string
    {
        $targetId = (string) Str::uuid();
        $this->createUser($targetId, $email, 'active', 'Target');
        $this->createCredential($targetId, $email, 'secret-password');

        return $targetId;
    }
}
