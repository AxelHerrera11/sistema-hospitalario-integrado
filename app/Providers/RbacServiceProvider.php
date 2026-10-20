<?php

declare(strict_types=1);

namespace App\Providers;

use App\Rbac\{CentralRoles, ConfiguredPermissions, LogDenials, RequirePermission};
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MicroHis\Rbac\Application\Authorize;
use MicroHis\Rbac\Domain\{DenialRecorder, PermissionProvider, RoleGrantsPermission, RoleResolver};

final class RbacServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/rbac/bootstrap.php');
        $this->app->bind(RoleResolver::class, CentralRoles::class);
        $this->app->bind(PermissionProvider::class, ConfiguredPermissions::class);
        $this->app->bind(DenialRecorder::class, LogDenials::class);
        $this->app->bind(Authorize::class, fn ($app) => new Authorize(
            $app->make(RoleResolver::class), $app->make(PermissionProvider::class),
            $app->make(DenialRecorder::class), new RoleGrantsPermission(),
        ));
    }

    public function boot(Router $router): void
    {
        $router->aliasMiddleware('rbac', RequirePermission::class);
        $router->aliasMiddleware('permission', RequirePermission::class);
        Route::prefix('api/v1')->middleware(['api', 'tenant', 'jwt.auth'])->group(function (): void {
            Route::get('permissions', fn () => response()->json(['permissions' => config('rbac.catalog')]))
                ->middleware('rbac:rbac.permisos.ver');
        });
    }
}
