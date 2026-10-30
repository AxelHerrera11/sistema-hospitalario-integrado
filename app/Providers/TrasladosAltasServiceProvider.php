<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Api\V1\TrasladosAltasController;
use App\Http\Middleware\EnsureHospitalDatabaseTenant;
use App\TrasladosAltas\PdoResolutorActorLocal;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MicroHis\TrasladosAltas\Application\Autorizador;
use MicroHis\TrasladosAltas\Application\RegistroInternacion;
use MicroHis\TrasladosAltas\Persistence\AutorizadorReferenciaActiva;
use MicroHis\TrasladosAltas\Persistence\PdoRegistroInternacion;

/** Integra el micro-HIS de traslados y altas (ASII-08) con la API v1, siguiendo el patrón de MedicationCatalogServiceProvider. */
final class TrasladosAltasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/traslados-altas/bootstrap.php');
        // Se resuelven por operación y con su propia conexión PDO a la base HOSPITAL: la autorización mínima se consulta
        // fuera de la transacción de la operación (RF-TA-03).
        $this->app->bind(RegistroInternacion::class, static fn (): RegistroInternacion => new PdoRegistroInternacion(DB::connection('hospital')->getPdo()));
        $this->app->bind(Autorizador::class, static fn (): Autorizador => new AutorizadorReferenciaActiva(DB::connection('hospital')->getPdo()));
        $this->app->bind(ResolutorActorLocal::class, static fn (): ResolutorActorLocal => new PdoResolutorActorLocal(static fn () => DB::connection('hospital')->getPdo()));
    }

    public function boot(): void
    {
        // Mismo orden que el resto de la API: tenant y jwt.auth antes del permiso; la base HOSPITAL debe ser del hospital del contexto. Los permisos están PROPUESTOS (pending):
        // RequirePermission los deniega con 403 hasta que RBAC (ASII-02) los active.
        Route::prefix('api/v1')->middleware(['api', 'tenant', 'jwt.auth', EnsureHospitalDatabaseTenant::class])->group(function (): void {
            Route::post('traslados', [TrasladosAltasController::class, 'trasladar'])->middleware('rbac:traslados.registrar');
            Route::post('altas', [TrasladosAltasController::class, 'darAlta'])->middleware('rbac:altas.registrar');
            Route::get('estancias/{estancia}/internacion', [TrasladosAltasController::class, 'consultar'])->middleware('rbac:traslados.ver');
        });
    }
}
