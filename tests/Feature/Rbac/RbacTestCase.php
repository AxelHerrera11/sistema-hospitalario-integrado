<?php

namespace Tests\Feature\Rbac;

use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use MicroHis\Rbac\Domain\{DenialRecorder, PermissionProvider, RoleResolver};
use MicroHis\Rbac\Persistence\InMemoryRbacStore;
use Tests\TestCase;

/** Auth/tenant de prueba; no se registra en providers ni rutas de producción. */
abstract class RbacTestCase extends TestCase
{
    protected InMemoryRbacStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = new InMemoryRbacStore(['Reader' => ['rbac.permisos.ver']]);
        $this->store->grant('reader', 'hospital-a', 'Reader');
        config(['tenants.hospital_database_tenant_id' => 'hospital-a']);
        foreach ([RoleResolver::class, PermissionProvider::class, DenialRecorder::class] as $contract) {
            $this->app->instance($contract, $this->store);
        }
        TestIdentity::$user = new GenericUser(['id' => 'reader']);
        TestIdentity::$hospital = 'hospital-a';
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', TestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', TestIdentity::class);
    }
}

class TestIdentity
{
    public static ?GenericUser $user;
    public static ?string $hospital;

    public function handle(Request $request, Closure $next)
    {
        $request->setUserResolver(fn () => self::$user);
        $request->attributes->set('tenant', new Hospital([
            'hospital_id' => TestIdentity::$hospital,
        ]));
        return $next($request);
    }
}
