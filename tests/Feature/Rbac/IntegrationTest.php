<?php

namespace Tests\Feature\Rbac;

use App\Rbac\RequirePermission;
use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use MicroHis\Rbac\Domain\{DenialRecorder, PermissionProvider, RoleResolver};
use MicroHis\Rbac\Persistence\InMemoryRbacStore;
use Tests\TestCase;

/** Auth y tenant simulados SOLO en tests: nunca se registran en la aplicación. */
class IntegrationTest extends TestCase
{
    protected InMemoryRbacStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = new InMemoryRbacStore(['Reader' => ['rbac.permisos.ver']]);
        $this->store->grant('reader', 'hospital-a', 'Reader');
        foreach ([RoleResolver::class, PermissionProvider::class, DenialRecorder::class] as $contract) {
            $this->app->instance($contract, $this->store);
        }
        TestIdentity::$user = new GenericUser(['id' => 'reader']);
        TestIdentity::$hospital = 'hospital-a';
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', TestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', TestIdentity::class);
    }

    public function test_catalog_is_registered_and_protected(): void
    {
        $route = Route::getRoutes()->match(Request::create('/api/v1/permissions'));
        $this->assertContains('rbac:rbac.permisos.ver', $route->gatherMiddleware());
        $this->assertContains('jwt.auth', $route->gatherMiddleware());
        $this->getJson('/api/v1/permissions')->assertOk()
            ->assertJsonFragment(['permissions' => config('rbac.catalog')]);
    }

    public function test_anonymous_and_other_hospital_cannot_access(): void
    {
        TestIdentity::$user = null;
        $this->getJson('/api/v1/permissions')->assertUnauthorized();
        TestIdentity::$user = new GenericUser(['id' => 'reader']);
        TestIdentity::$hospital = 'hospital-b';
        $this->getJson('/api/v1/permissions')->assertForbidden()->assertJsonPath('code', 'RBAC_DENIED');
        $this->assertSame('hospital-b', $this->store->denials()[0]['hospital_id']);
    }

    public function test_dependency_failure_never_runs_protected_action(): void
    {
        $this->app->bind(RoleResolver::class, fn () => new class implements RoleResolver {
            public function rolesIn(string $userId, string $hospitalId): array { throw new \RuntimeException('private DSN'); }
        });
        $this->getJson('/api/v1/permissions')->assertStatus(503)
            ->assertJsonPath('code', 'RBAC_UNAVAILABLE')->assertDontSee('private DSN');
    }

    public function test_spoofed_identity_headers_are_ignored(): void
    {
        TestIdentity::$user = new GenericUser(['id' => 'outsider']);
        $this->withHeaders(['X-User-ID' => 'reader', 'X-User-Ref' => 'reader', 'X-Tenant-ID' => 'hospital-a'])
            ->getJson('/api/v1/permissions')->assertForbidden();
        $this->assertSame('outsider', $this->store->denials()[0]['user_id']);
    }
}

class TestIdentity
{
    public static ?GenericUser $user;
    public static ?string $hospital;

    public function handle(Request $request, Closure $next)
    {
        $request->setUserResolver(fn () => self::$user);
        $request->attributes->set('tenant', new class {
            public function getKey(): ?string { return TestIdentity::$hospital; }
        });
        return $next($request);
    }
}
