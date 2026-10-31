<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Middleware\EnsureHospitalDatabaseTenant;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/** Rutas de citas (ASII-05, semana 16) con tenant, JWT, RBAC y guarda HOSPITAL; el repositorio ya está enlazado en AppServiceProvider. */
final class CitasServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::prefix('api/v1/citas')
            ->middleware(['api', 'tenant', 'jwt.auth'])
            ->group(function (): void {
                Route::get('/', [AppointmentController::class, 'index'])->middleware(['rbac:citas.ver', EnsureHospitalDatabaseTenant::class]);
                Route::post('/', [AppointmentController::class, 'store'])->middleware(['rbac:citas.crear', EnsureHospitalDatabaseTenant::class]);
                Route::get('{appointment}', [AppointmentController::class, 'show'])->middleware(['rbac:citas.ver', EnsureHospitalDatabaseTenant::class]);
                Route::post('{appointment}/confirmar', [AppointmentController::class, 'transition'])->defaults('estado', 'confirmed')
                    ->middleware(['rbac:citas.confirmar', EnsureHospitalDatabaseTenant::class]);
                Route::post('{appointment}/completar', [AppointmentController::class, 'transition'])->defaults('estado', 'completed')
                    ->middleware(['rbac:citas.confirmar', EnsureHospitalDatabaseTenant::class]);
                Route::post('{appointment}/cancelar', [AppointmentController::class, 'transition'])->defaults('estado', 'cancelled')
                    ->middleware(['rbac:citas.cancelar', EnsureHospitalDatabaseTenant::class]);
                Route::delete('{appointment}', [AppointmentController::class, 'destroy'])->middleware('rbac:citas.cancelar');
            });
    }
}
