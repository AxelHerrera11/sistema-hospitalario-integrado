<?php

namespace App\Providers;

use App\Modules\Reception\Http\ReceptionSecurity;
use Illuminate\Support\ServiceProvider;

final class ReceptionSecurityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(function (): void {
            foreach ($this->app['router']->getRoutes() as $route) {
                if (!str_starts_with($route->uri(), 'api/v1/lab/specimens')) continue;
                $action = $route->getAction();
                $action['middleware'] = [ReceptionSecurity::class, ...($action['middleware'] ?? [])];
                $route->setAction($action);
            }
        });
    }
}
