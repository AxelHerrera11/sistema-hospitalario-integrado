<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Api\V1\LabTestController;
use App\Http\Middleware\EnsureHospitalDatabaseTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use MicroHis\CatalogoLab\Application\LabTestRepository;
use MicroHis\CatalogoLab\Application\RegisterLabTest;
use MicroHis\CatalogoLab\Persistence\PdoLabTestRepository;

/**
 * Integra el Micro-HIS del catálogo de pruebas (ASII-17) con la API v1: cablea el adaptador PDO sobre HOSPITAL (`lab_tests`, 000600)
 * con conexión perezosa (base caída = 503, no 500). El actor local lo resuelve ResolutorActorLocal, que registra Traslados.
 */
final class CatalogoLabServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/catalogo-pruebas-lab/bootstrap.php');

        $this->app->bind(LabTestRepository::class, static fn (): LabTestRepository => new PdoLabTestRepository(
            static fn (): \PDO => DB::connection('hospital')->getPdo(),
        ));
        $this->app->bind(RegisterLabTest::class, static fn ($app): RegisterLabTest => new RegisterLabTest(
            $app->make(LabTestRepository::class),
            static fn (): string => now()->toDateString(),
            static fn (): string => (string) Str::uuid(),
        ));
    }

    public function boot(): void
    {
        // laboratorio.catalogo.ver está activo; laboratorio.catalogo.administrar solo para superadmin (admin_hospital sigue en pending).
        Route::prefix('api/v1')->middleware(['api', 'tenant', 'jwt.auth'])->group(function (): void {
            Route::get('lab-tests', [LabTestController::class, 'index'])->middleware(['rbac:laboratorio.catalogo.ver', EnsureHospitalDatabaseTenant::class]);
            Route::post('lab-tests', [LabTestController::class, 'store'])->middleware(['rbac:laboratorio.catalogo.administrar', EnsureHospitalDatabaseTenant::class]);
        });
    }
}
