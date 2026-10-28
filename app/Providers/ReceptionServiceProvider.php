<?php

namespace App\Providers;

use App\Http\Controllers\Api\V1\ReceptionController;
use App\Modules\Reception\Application\OrderGateway;
use App\Modules\Reception\Application\ReceptionRepository;
use App\Modules\Reception\Infrastructure\HospitalReceptionRepository;
use App\Modules\Reception\Infrastructure\OrderProjection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class ReceptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(ReceptionRepository::class, HospitalReceptionRepository::class);
        $this->app->bind(OrderGateway::class, OrderProjection::class);
    }

    public function boot(): void
    {
        Route::prefix('api/v1/lab/specimens')->middleware(['api', 'tenant', 'jwt.auth'])->group(function (): void {
            Route::post('/', [ReceptionController::class, 'store'])->middleware('rbac:muestras.recibir');
            Route::get('/pending-orders', [ReceptionController::class, 'pending'])->middleware('rbac:muestras.recibir');
            Route::get('/{code}/label', [ReceptionController::class, 'show'])->middleware('rbac:muestras.etiquetar');
            Route::get('/{code}', [ReceptionController::class, 'show'])->middleware('rbac:muestras.ver');
        });
    }
}
