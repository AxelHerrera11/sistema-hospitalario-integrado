<?php

namespace Tests\Feature\Rbac;

use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Tenants\TenantAuthTestCase;

/** Kernel HTTP, JWT, RBAC y ambos escritores reales; CENTRAL SQLite de prueba. */
class GovernanceHttpTest extends TenantAuthTestCase
{
    private function authenticateAs(string $role = 'superadmin'): string
    {
        $this->addMembership($this->userId, $this->hospitalA, $role);
        $token = $this->tokenFrom($this->login()->assertOk());
        $this->withToken($token)->withHeaders(['X-Tenant-ID' => $this->hospitalA]);
        return $token;
    }

    public function test_registered_endpoint_reads_denied_events_and_records_its_own_query(): void
    {
        $this->authenticateAs();
        $writer = $this->app->make(AuditLogRepositoryInterface::class);
        foreach ([$this->hospitalA, $this->hospitalB] as $hospital) {
            $writer->logRaw('rbac.access_denied', [
                'actor_id' => $this->userId, 'hospital_id' => $hospital,
                'result' => 'denied', 'criticality' => 'security',
                'resource' => ['type' => 'permission', 'id' => 'rbac.permisos.ver'],
                'trace_id' => 'trace-denial-http',
            ]);
        }
        $this->getJson('/api/v1/audit-events?resultado=denied')
            ->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.tenant_id', $this->hospitalA)
            ->assertJsonPath('data.0.outcome', 'denied')
            ->assertJsonPath('data.0.criticality', 'security')
            ->assertJsonPath('data.0.resource.id', 'rbac.permisos.ver')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(1, DB::connection('central')->table('audit_log')
            ->where('event', 'gobernanza.auditoria_consultada')->count());
    }

    public function test_medico_is_denied_before_filters_and_denial_is_persisted_once(): void
    {
        $this->authenticateAs('medico');
        $response = $this->getJson('/api/v1/audit-events?por_pagina=invalid')
            ->assertForbidden()->assertJsonPath('code', 'RBAC_DENIED');
        $events = DB::connection('central')->table('audit_log')
            ->where('event', 'rbac.access_denied')->get();
        $this->assertCount(1, $events);
        $this->assertSame($this->hospitalA, $events[0]->hospital_id);
        $this->assertSame($response->json('trace_id'), $events[0]->trace_id);
    }

    public function test_real_token_cannot_select_foreign_hospital_in_header_or_query(): void
    {
        $this->authenticateAs();
        $this->getJson('/api/v1/audit-events?hospital_id='.$this->hospitalB)
            ->assertForbidden()->assertJsonPath('code', 'TENANT_MISMATCH');
        $this->withHeaders(['X-Tenant-ID' => $this->hospitalB])
            ->getJson('/api/v1/audit-events')->assertForbidden();
    }

    public function test_logout_and_role_revocation_prevent_further_audit_access(): void
    {
        $this->authenticateAs();
        $this->getJson('/api/v1/audit-events')->assertOk();
        DB::connection('central')->table('hospital_memberships')
            ->where('user_id', $this->userId)->update(['role_code' => 'medico']);
        $this->getJson('/api/v1/audit-events')->assertUnauthorized();
        $token = $this->tokenFrom($this->login()->assertOk());
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->getJson('/api/v1/audit-events')->assertUnauthorized();
    }

    public function test_invalid_filters_and_storage_failure_do_not_expose_internal_details(): void
    {
        $token = $this->authenticateAs();
        $this->getJson('/api/v1/audit-events?por_pagina=101')
            ->assertUnprocessable()->assertJsonPath('code', 'AUDIT_FILTER_INVALID');
        DB::connection('central')->statement('DROP TABLE audit_log');
        $response = $this->getJson('/api/v1/audit-events')->assertStatus(503)
            ->assertJsonPath('code', 'AUDIT_UNAVAILABLE');
        $this->assertStringNotContainsString($token, $response->getContent());
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
    }
}
