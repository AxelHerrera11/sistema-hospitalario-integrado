<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Middleware\EnsureHospitalDatabaseTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MicroHis\DashboardBase\Application\CitasDelDia;
use MicroHis\DashboardBase\Persistence\PdoCitasDelDia;

/**
 * Integra el dashboard base (ASII-26, semana 16): la pantalla de inicio de cualquier usuario autenticado.
 * No exige un permiso propio; cada tarjeta exige el suyo dentro del read model (falla cerrada).
 */
final class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/dashboard-base/bootstrap.php');

        $this->app->bind(CitasDelDia::class, static fn (): CitasDelDia => new PdoCitasDelDia(static fn () => DB::connection('hospital')->getPdo()));
    }

    public function boot(): void
    {
        Route::prefix('api/v1')->middleware(['api', 'tenant', 'jwt.auth', EnsureHospitalDatabaseTenant::class])->group(function (): void {
            Route::get('dashboard/resumen', [DashboardController::class, 'resumen']);
        });
    }
}
