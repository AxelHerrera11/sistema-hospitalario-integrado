<?php

namespace Tests\Feature\Rbac;

use App\Rbac\{ConfiguredPermissions, RequirePermission};
use Illuminate\Http\Request;
use MicroHis\Rbac\Domain\{DenialRecorder, PermissionProvider, RoleResolver};
use MicroHis\Rbac\Persistence\InMemoryRbacStore;

class SecurityTest extends RbacTestCase
{
    public function test_missing_and_mismatched_tenant_are_rejected(): void
    {
        $this->withHeaders(['X-Tenant-ID' => ''])->getJson('/api/v1/permissions')
            ->assertStatus(400)->assertJsonPath('code', 'TENANT_REQUIRED');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])->getJson('/api/v1/permissions')
            ->assertForbidden()->assertJsonPath('code', 'TENANT_MISMATCH');
    }

    public function test_missing_server_context_fails_closed(): void
    {
        TestIdentity::$hospital = null;
        $this->getJson('/api/v1/permissions')->assertStatus(503)->assertJsonPath('code', 'RBAC_CONTEXT_UNAVAILABLE');
    }

    public function test_permission_must_be_configured_in_middleware(): void
    {
        $middleware = $this->app->make(RequirePermission::class);
        foreach (['', '*', 'rbac.*', 'rbac,admin', 'rbac.ver ', 'lab-results.*', 'lab-results/read'] as $permission) {
            $response = $middleware->handle(Request::create('/'), fn () => $this->fail('Controller executed'), $permission);
            $this->assertSame(503, $response->getStatusCode());
        }
    }

    public function test_audit_failure_has_no_sensitive_exception_details(): void
    {
        TestIdentity::$user = new \Illuminate\Auth\GenericUser(['id' => 'outsider']);
        $this->app->bind(DenialRecorder::class, fn () => new class implements DenialRecorder {
            public function record(array $denial): void { throw new \RuntimeException('token=private-secret'); }
        });
        $this->getJson('/api/v1/permissions')->assertStatus(503)->assertJsonPath('code', 'RBAC_UNAVAILABLE')
            ->assertDontSee('private-secret')->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_unknown_roles_and_wildcards_never_grant_access(): void
    {
        $store = new InMemoryRbacStore(['Reader' => ['*', 'rbac.*']]);
        $store->grant('reader', 'hospital-a', 'Reader');
        $store->grant('reader', 'hospital-a', 'Unknown');
        $this->app->instance(PermissionProvider::class, $store);
        $this->app->instance(RoleResolver::class, $store);
        $this->getJson('/api/v1/permissions')->assertForbidden();
    }

    public function test_policy_is_loaded_without_implicit_roles_or_dot_expansion(): void
    {
        $provider = new ConfiguredPermissions();
        $this->assertEqualsCanonicalizing(config('rbac.roles.superadmin'), $provider->permissionsFor('superadmin'));
        $this->assertNotContains('*', $provider->permissionsFor('superadmin'));
        $this->assertNotContains('prescripciones.excepcion_alergia', $provider->permissionsFor('superadmin'));
        $this->assertSame([], $provider->permissionsFor('SuperAdmin'));
        config(['rbac.roles' => ['Reader' => ['rbac.permisos.ver'], 'Reader.other' => ['alergias.ver']]]);
        $this->assertSame(['alergias.ver'], $provider->permissionsFor('Reader.other'));
        $this->app->instance(PermissionProvider::class, $provider);
        $this->getJson('/api/v1/permissions')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        config(['rbac.roles' => []]);
        $this->getJson('/api/v1/permissions')->assertForbidden();
    }
}
