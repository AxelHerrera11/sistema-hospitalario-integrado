<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Api\V1\MedicationCatalogController;
use App\MedicationCatalog\HospitalMedicationRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MicroHis\MedicationCatalog\Application\ConsultMedications;
use MicroHis\MedicationCatalog\Application\MedicationLookup;
use MicroHis\MedicationCatalog\Application\MedicationRepository;
use MicroHis\MedicationCatalog\Presentation\Router as CatalogRouter;

/** Integra el micro-HIS del catálogo (ASII-14) con el HIS, siguiendo el patrón de RbacServiceProvider. */
final class MedicationCatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/catalogo-medicamentos/app.php');
        $this->app->bind(MedicationRepository::class, static fn (): MedicationRepository =>
            new HospitalMedicationRepository(static fn () => DB::connection('hospital')->getPdo()));
        $this->app->bind(MedicationLookup::class, static fn ($app): MedicationLookup => new ConsultMedications($app->make(MedicationRepository::class)));
        $this->app->bind(CatalogRouter::class, static fn ($app): CatalogRouter => catalog_router($app->make(MedicationRepository::class)));
    }

    public function boot(): void
    {
        // Mismo orden que RBAC: tenant y jwt.auth antes del permiso. Escritura con permisos PROPUESTOS que aún no
        // existen en config/rbac.php: RequirePermission los deniega (403) hasta que RBAC los apruebe.
        Route::prefix('api/v1')->middleware(['api', 'tenant', 'jwt.auth'])->group(function (): void {
            Route::get('medications', [MedicationCatalogController::class, 'index'])->middleware('rbac:medicamentos.ver');
            Route::get('medications/{medication}', [MedicationCatalogController::class, 'show'])->middleware('rbac:medicamentos.ver');
            Route::post('medications', [MedicationCatalogController::class, 'store'])->middleware('rbac:medicamentos.registrar');
            Route::patch('medications/{medication}/validity', [MedicationCatalogController::class, 'changeValidity'])
                ->middleware('rbac:medicamentos.cambiar_vigencia');
        });
    }
}
