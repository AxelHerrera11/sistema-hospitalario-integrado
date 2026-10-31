<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Api\V1\SoapNoteController;
use App\Http\Middleware\EnsureHospitalDatabaseTenant;
use App\NotasSoap\HospitalCareAssignmentReader;
use App\NotasSoap\HospitalClinicalNoteStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MicroHis\NotasSoap\Application\AuditTrail;
use MicroHis\NotasSoap\Application\CareAssignmentReader;
use MicroHis\NotasSoap\Application\ClinicalNoteRepository;
use MicroHis\NotasSoap\Application\Icd10Catalog;
use MicroHis\NotasSoap\Persistence\PdoAuditTrail;
use MicroHis\NotasSoap\Persistence\PdoClinicalNoteStore;
use MicroHis\NotasSoap\Persistence\PdoIcd10Catalog;
use MicroHis\NotasSoap\Presentation\Router as SoapRouter;

/**
 * Integra el micro-HIS de Notas SOAP (ASII-11) con el HIS, con el patrón del catálogo de medicamentos: el dominio no
 * conoce Laravel; aquí solo se cablean los adaptadores PDO de la base HOSPITAL y las rutas detrás de los middlewares.
 * Nota y médico tratante usan el esquema HOSPITAL real (App\NotasSoap); el actor local lo resuelve ResolutorActorLocal,
 * que registra TrasladosAltasServiceProvider.
 */
final class NotasSoapServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/notas-soap/app.php');
        $this->app->scoped(AuditTrail::class, static fn (): AuditTrail => new PdoAuditTrail(DB::connection('hospital')->getPdo()));
        $this->app->scoped(ClinicalNoteRepository::class, static fn ($app): ClinicalNoteRepository =>
            new HospitalClinicalNoteStore(DB::connection('hospital')->getPdo(),
                new PdoClinicalNoteStore(DB::connection('hospital')->getPdo(), $app->make(AuditTrail::class)), $app->make(AuditTrail::class)));
        $this->app->bind(CareAssignmentReader::class, static fn (): CareAssignmentReader => new HospitalCareAssignmentReader(DB::connection('hospital')->getPdo()));
        $this->app->bind(Icd10Catalog::class, static fn (): Icd10Catalog => new PdoIcd10Catalog(DB::connection('hospital')->getPdo()));
        $this->app->bind(SoapRouter::class, static fn ($app): SoapRouter =>
            notas_soap_router($app->make(ClinicalNoteRepository::class), $app->make(CareAssignmentReader::class), $app->make(Icd10Catalog::class)));
    }

    public function boot(): void
    {
        // Mismo orden que el resto de módulos: tenant, jwt.auth y la base HOSPITAL de ese hospital antes del permiso `modulo.accion`.
        Route::prefix('api/v1')->middleware(['api', 'tenant', 'jwt.auth', EnsureHospitalDatabaseTenant::class])->group(function (): void {
            Route::post('care-episodes/{episode}/soap-notes', [SoapNoteController::class, 'store'])->middleware('rbac:notas_soap.crear');
            Route::post('soap-notes/{note}/diagnoses', [SoapNoteController::class, 'associateDiagnosis'])->middleware('rbac:notas_soap.asociar_diagnostico');
            Route::post('soap-notes/{note}/sign', [SoapNoteController::class, 'sign'])->middleware('rbac:notas_soap.firmar');
            Route::get('soap-notes/{note}', [SoapNoteController::class, 'show'])->middleware('rbac:notas_soap.ver');
        });
    }
}
