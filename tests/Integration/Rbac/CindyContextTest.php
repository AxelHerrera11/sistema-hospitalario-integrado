<?php

namespace Tests\Integration\Rbac;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Tenants\TenantAuthTestCase;

/** Ejecutar explícitamente sobre la integración con la entrega local de Cindy. */
class CindyContextTest extends TenantAuthTestCase
{
    public function test_real_jwt_reaches_catalog_and_membership_revocation_is_immediate(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'admin_hospital');
        $token = $this->tokenFrom($this->login()->assertOk());
        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])->withToken($token)
            ->getJson('/api/v1/permissions')->assertOk()
            ->assertJsonFragment(['rbac.permisos.ver'])
            ->assertHeader('Cache-Control', 'no-store, private');

        DB::connection('central')->table('hospital_memberships')
            ->where('user_id', $this->userId)->where('hospital_id', $this->hospitalA)
            ->update(['status' => 'inactive']);
        $this->getJson('/api/v1/permissions')->assertForbidden();
    }

    public function test_denial_is_persisted_with_identity_hospital_and_trace_without_token(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico');
        $token = $this->tokenFrom($this->login()->assertOk());
        $response = $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])->withToken($token)
            ->getJson('/api/v1/permissions')->assertForbidden()->assertJsonPath('code', 'RBAC_DENIED');
        $event = DB::connection('central')->table('audit_log')->where('event', 'rbac.access_denied')->first();
        $this->assertNotNull($event);
        $this->assertSame($this->userId, $event->actor_id);
        $this->assertSame($this->hospitalA, $event->hospital_id);
        $this->assertSame($response->json('trace_id'), $event->trace_id);
        $this->assertStringNotContainsString($token, $event->detail);
        $this->assertStringNotContainsString('secret-password', $event->detail);
    }

    public function test_roles_from_another_hospital_or_client_headers_do_not_grant_catalog_access(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico');
        $this->addMembership($this->userId, $this->hospitalB, 'admin_hospital');
        $token = $this->tokenFrom($this->login()->assertOk());
        $this->withHeaders([
            'X-Tenant-ID' => $this->hospitalA, 'X-User-ID' => $this->userId,
            'X-User-Ref' => 'superadmin',
        ])->withToken($token)->getJson('/api/v1/permissions')->assertForbidden();
        $this->withHeaders(['X-Tenant-ID' => $this->hospitalB])
            ->getJson('/api/v1/permissions')->assertForbidden()->assertJsonPath('error', 'TENANT_MISMATCH');
    }

    public function test_audit_outage_returns_503_without_executing_the_protected_route(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico');
        $token = $this->tokenFrom($this->login()->assertOk());
        DB::connection('central')->table('audit_chain_state')->delete();
        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])->withToken($token)
            ->getJson('/api/v1/permissions')->assertStatus(503)
            ->assertJsonPath('code', 'RBAC_UNAVAILABLE')->assertJsonMissingPath('permissions');
    }
}
