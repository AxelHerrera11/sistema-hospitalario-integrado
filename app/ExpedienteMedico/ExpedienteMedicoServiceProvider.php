<?php

declare(strict_types=1);

namespace App\ExpedienteMedico;

use App\Http\Middleware\EnsureHospitalDatabaseTenant;
use App\TrasladosAltas\PdoResolutorActorLocal;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MicroHis\ExpedienteMedico\Application\HospitalStores;
use MicroHis\ExpedienteMedico\Application\HospitalStoresProvider;
use MicroHis\ExpedienteMedico\Persistence\HospitalRegistry;

/**
 * Integra el micro-HIS del expediente médico con la API v1, con el patrón de TrasladosAltasServiceProvider.
 *
 * Registrado en bootstrap/providers.php después de TrasladosAltasServiceProvider (autorizado por Cindy, 2026-10-23).
 * Solo se publica la consulta: la apertura necesita un permiso que aún no está en el catálogo RBAC, y declarar
 * `rbac:` con un permiso no catalogado rompe el contrato de rutas del grupo.
 */
final class ExpedienteMedicoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/expediente-medico/app.php');
        // La base HOSPITAL es la del tenant de la petición: el registro solo conoce ese hospital.
        $this->app->bind(HospitalStoresProvider::class, static function ($app): HospitalStoresProvider {
            $hospital = ExpedienteMedicoController::hospitalOf($app['request']);

            return new HospitalRegistry($hospital === '' ? [] : [
                $hospital => static fn (): HospitalStores => expediente_pdo_stores(DB::connection('hospital')->getPdo()),
            ]);
        });
        // Reutiliza el resolutor de Traslados (local_user_refs por usuario CENTRAL) sin redefinirlo si ya está enlazado.
        $this->app->bindIf(ResolutorActorLocal::class, static fn (): ResolutorActorLocal => new PdoResolutorActorLocal(static fn () => DB::connection('hospital')->getPdo()));
    }

    public function boot(): void
    {
        // Mismo orden que el resto de la API: tenant y jwt.auth antes del permiso. El guard impide que otro hospital
        // lea la base HOSPITAL de esta instancia, porque HospitalRegistry la asocia al tenant de la petición.
        Route::prefix('api/v1')->middleware(['api', 'tenant', 'jwt.auth', EnsureHospitalDatabaseTenant::class])->group(function (): void {
            Route::get('patients/{patientCode}/medical-record', [ExpedienteMedicoController::class, 'consult'])
                ->middleware('rbac:expediente.ver');
        });
    }
}
