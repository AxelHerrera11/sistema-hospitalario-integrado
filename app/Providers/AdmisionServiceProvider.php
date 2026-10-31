<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Api\V1\AdmissionController;
use App\Http\Middleware\EnsureHospitalDatabaseTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use MicroHis\Admision\Application\AdmissionRepository;
use MicroHis\Admision\Application\AdmitPatient;
use MicroHis\Admision\Persistence\PdoAdmissionRepository;

/**
 * Integra el Micro-HIS de admisión (ASII-07) con la API v1: cablea el adaptador PDO sobre HOSPITAL con conexión perezosa (base
 * caída = 503, no 500). El actor local lo resuelve ResolutorActorLocal, que registra TrasladosAltasServiceProvider.
 */
final class AdmisionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/admision-camas/bootstrap.php');

        $this->app->bind(AdmissionRepository::class, static fn (): AdmissionRepository => new PdoAdmissionRepository(
            static fn (): \PDO => DB::connection('hospital')->getPdo(),
        ));
        $this->app->bind(AdmitPatient::class, static fn ($app): AdmitPatient => new AdmitPatient(
            $app->make(AdmissionRepository::class),
            static fn (): string => now()->toIso8601String(),
            static fn (): string => (string) Str::uuid(),
        ));
    }

    public function boot(): void
    {
        // tenant y jwt.auth antes del permiso (ya activos); después, el guard de #1154: solo el hospital dueño de la base HOSPITAL.
        Route::prefix('api/v1')->middleware(['api', 'tenant', 'jwt.auth'])->group(function (): void {
            Route::post('admissions', [AdmissionController::class, 'store'])->middleware(['rbac:admisiones.registrar', EnsureHospitalDatabaseTenant::class]);
            Route::get('beds/available', [AdmissionController::class, 'availableBeds'])->middleware(['rbac:camas.ver', EnsureHospitalDatabaseTenant::class]);
        });
    }
}
