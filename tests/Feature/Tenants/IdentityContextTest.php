<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class IdentityContextTest extends TenantAuthTestCase
{
    public function test_login_and_me_publish_effective_permissions_from_server_memberships(): void
    {
        $this->assertSame(\App\Modules\Tenants\Domain\Identity\CanonicalRole::ALL, array_keys(config('rbac.roles')));
        foreach (['tecnico_laboratorio' => ['lab-results.create', 'lab-results.validate'],
            'bioquimico' => ['lab-results.validate', 'lab-results.create'],
            'jefatura_medica' => ['alertas.supervisar', 'notas_soap.ver']] as $role => [$allowed, $denied]) {
            DB::connection('central')->table('hospital_memberships')->where('user_id', $this->userId)->delete();
            $this->addMembership($this->userId, $this->hospitalA, $role);
            $login = $this->login()->assertOk()->assertJsonPath('roles', [$role]);
            $permissions = $login->json('user.permissions');
            $this->assertContains($allowed, $permissions);
            $this->assertNotContains($denied, $permissions);
            $this->assertSame(array_values(array_unique($permissions)), $permissions);
            $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])->withToken($this->tokenFrom($login))
                ->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('user.permissions', $permissions);
        }
    }

    public function test_login_and_me_expose_central_identity_and_live_hospital_roles(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico');
        $this->addMembership($this->userId, $this->hospitalA, 'recepcion');
        $this->addMembership($this->userId, $this->hospitalA, 'Recepcionista');

        $login = $this->login();

        $login->assertOk()
            ->assertJsonPath('token_type', 'bearer')
            ->assertJsonPath('user.user_id', $this->userId)
            ->assertJsonPath('hospital.hospital_id', $this->hospitalA)
            ->assertJsonPath('roles', ['medico', 'recepcion'])
            ->assertJsonStructure(['access_token', 'expires_in', 'roles_version']);

        $token = $this->tokenFrom($login);

        $me = $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->getJson('/api/v1/auth/me');

        $me->assertOk()
            ->assertJsonPath('user.user_id', $this->userId)
            ->assertJsonPath('hospital.hospital_id', $this->hospitalA)
            ->assertJsonPath('roles', ['medico', 'recepcion']);
    }

    public function test_authentication_events_are_audited_without_credentials(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico');
        $this->login()->assertOk();
        $this->login(password: 'incorrect-password')
            ->assertUnauthorized()
            ->assertJsonPath('error', 'INVALID_CREDENTIALS');

        $success = DB::connection('central')->table('audit_log')
            ->where('event', 'auth.login_succeeded')->first();
        $failure = DB::connection('central')->table('audit_log')
            ->where('event', 'auth.login_failed')->first();

        $this->assertNotNull($success);
        $this->assertNotNull($failure);
        $this->assertSame($this->userId, $success->actor_id);
        $this->assertSame($this->hospitalA, $failure->hospital_id);
        $this->assertSame('denied', $failure->result);
        $this->assertSame(64, strlen($success->event_hash));
        $this->assertSame($success->event_hash, $failure->previous_hash);
        $this->assertSame(
            $failure->event_hash,
            DB::connection('central')->table('audit_chain_state')->value('head_hash'),
        );

        $failureDetail = json_decode($failure->detail, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('invalid_credentials', $failureDetail['reason']);
        $this->assertArrayNotHasKey('password', $failureDetail);
    }

    public function test_audit_failure_rolls_back_session_creation(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico');
        DB::connection('central')->table('audit_chain_state')->delete();

        $this->login()->assertServerError();

        $this->assertSame(0, DB::connection('central')->table('auth_sessions')->count());
        $this->assertSame(0, DB::connection('central')->table('auth_refresh_tokens')->count());
    }

    public function test_role_lists_are_separated_per_hospital(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico');
        $this->addMembership($this->userId, $this->hospitalB, 'enfermeria');
        $this->addMembership($this->userId, $this->hospitalB, 'admin_hospital');

        $this->login(hospitalId: $this->hospitalA)
            ->assertOk()
            ->assertJsonPath('roles', ['medico']);

        $this->login(hospitalId: $this->hospitalB)
            ->assertOk()
            ->assertJsonPath('roles', ['admin_hospital', 'enfermeria']);
    }

    public function test_tenant_header_cannot_select_a_hospital_outside_the_token(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico');
        $this->addMembership($this->userId, $this->hospitalB, 'medico');

        $token = $this->tokenFrom($this->login(hospitalId: $this->hospitalA)->assertOk());

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalB])
            ->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(403)
            ->assertJsonPath('error', 'TENANT_MISMATCH');
    }

    public function test_x_user_headers_do_not_authenticate_or_replace_the_token_subject(): void
    {
        $otherUser = (string) Str::uuid();
        $this->createUser($otherUser, 'other@example.test', name: 'Other');
        $this->addMembership($this->userId, $this->hospitalA, 'medico');
        $this->addMembership($otherUser, $this->hospitalA, 'superadmin');

        $token = $this->tokenFrom($this->login()->assertOk());

        $this->withHeaders([
            'X-Tenant-ID' => $this->hospitalA,
            'X-User-ID' => $otherUser,
            'X-User-Ref' => 'other',
        ])->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.user_id', $this->userId)
            ->assertJsonPath('roles', ['medico']);
    }

    public function test_future_and_inactive_memberships_do_not_authenticate(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico', 'inactive');

        $this->login()
            ->assertStatus(403)
            ->assertJsonPath('error', 'NO_ACTIVE_MEMBERSHIP');

        $this->addMembership($this->userId, $this->hospitalA, 'medico', validFrom: now()->addDay()->toImmutable());

        $this->login()
            ->assertStatus(403)
            ->assertJsonPath('error', 'NO_ACTIVE_MEMBERSHIP');
    }

    public function test_unknown_or_legacy_roles_are_not_silently_translated(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'AUDITOR');
        $this->addMembership($this->userId, $this->hospitalA, 'Recepcionista');
        $this->addMembership($this->userId, $this->hospitalA, 'Medico');

        $this->login()
            ->assertStatus(403)
            ->assertJsonPath('error', 'NO_ACTIVE_MEMBERSHIP');
    }

    public function test_role_revocation_invalidates_existing_tokens(): void
    {
        $membership = $this->addMembership($this->userId, $this->hospitalA, 'medico');
        $this->addMembership($this->userId, $this->hospitalA, 'recepcion');

        $token = $this->tokenFrom($this->login()->assertOk());

        DB::connection('central')
            ->table('hospital_memberships')
            ->where('membership_id', $membership)
            ->update(['status' => 'revoked', 'updated_at' => now()]);

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error', 'ROLES_CHANGED');
    }

    public function test_user_deactivation_and_expired_membership_invalidate_existing_tokens(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico');
        $token = $this->tokenFrom($this->login()->assertOk());

        DB::connection('central')
            ->table('central_users')
            ->where('user_id', $this->userId)
            ->update(['status' => 'inactive', 'updated_at' => now()]);

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error', 'USER_INACTIVE');

        DB::connection('central')
            ->table('central_users')
            ->where('user_id', $this->userId)
            ->update(['status' => 'active', 'updated_at' => now()]);
        DB::connection('central')
            ->table('hospital_memberships')
            ->where('user_id', $this->userId)
            ->update(['valid_to' => now()->subMinute(), 'updated_at' => now()]);

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(403)
            ->assertJsonPath('error', 'NO_ACTIVE_ROLES');
    }
}
