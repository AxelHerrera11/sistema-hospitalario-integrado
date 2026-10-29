<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Api\V1\PrescriptionController;
use App\Http\Middleware\EnsureHospitalDatabaseTenant;
use App\Prescripciones\PdoPrescriptionContext;
use App\Prescripciones\PrescriptionsAdapters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MicroHis\Prescripciones\Application\AllergyChecker;
use MicroHis\Prescripciones\Application\AuditLogger;
use MicroHis\Prescripciones\Application\Exception\PersistenceFailure;
use MicroHis\Prescripciones\Application\MedicationCatalog;
use MicroHis\Prescripciones\Application\PrescriptionRepository;
use MicroHis\Prescripciones\Application\PrescriptionService;
use MicroHis\Prescripciones\Persistence\PdoAllergyChecker;
use MicroHis\Prescripciones\Persistence\PdoPrescriptionRepository;
use PDO;
use PDOException;

final class PrescriptionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/prescripciones/bootstrap.php');
        $this->app->scoped(PrescriptionRepository::class, fn (): PrescriptionRepository => new PdoPrescriptionRepository($this->hospitalPdo()));
        $this->app->scoped(AllergyChecker::class, fn (): AllergyChecker => new PdoAllergyChecker($this->hospitalPdo()));
        $this->app->scoped(PrescriptionsAdapters::class);
        $this->app->scoped(MedicationCatalog::class, static fn ($app): MedicationCatalog => $app->make(PrescriptionsAdapters::class));
        $this->app->scoped(AuditLogger::class, static fn ($app): AuditLogger => $app->make(PrescriptionsAdapters::class));
        $this->app->scoped(PdoPrescriptionContext::class, fn (): PdoPrescriptionContext => new PdoPrescriptionContext(fn (): PDO => $this->hospitalPdo()));
        $this->app->scoped(PrescriptionService::class, static fn ($app): PrescriptionService => new PrescriptionService(
            $app->make(PrescriptionRepository::class),
            $app->make(AllergyChecker::class),
            $app->make(MedicationCatalog::class),
            $app->make(AuditLogger::class),
        ));
    }

    private function hospitalPdo(): PDO
    {
        try {
            return DB::connection('hospital')->getPdo();
        } catch (PDOException $error) {
            throw new PersistenceFailure('No se pudo conectar con la base hospitalaria.', $error);
        }
    }

    public function boot(): void
    {
        // Como los demás módulos de la base HOSPITAL (#1154): falla cerrado si la instancia no corresponde al hospital.
        Route::prefix('api/v1')->middleware(['api', 'tenant', 'jwt.auth', EnsureHospitalDatabaseTenant::class])->group(function (): void {
            Route::post('patients/{patient}/prescriptions', [PrescriptionController::class, 'store'])->middleware('rbac:prescripciones.crear');
            Route::get('patients/{patient}/prescriptions', [PrescriptionController::class, 'index'])->middleware('rbac:prescripciones.ver');
            Route::get('prescriptions/{id}', [PrescriptionController::class, 'show'])->middleware('rbac:prescripciones.ver');
            Route::post('prescriptions/{id}/sign', [PrescriptionController::class, 'sign'])->middleware('rbac:prescripciones.firmar');
        });
    }
}
