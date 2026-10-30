<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Api\V1\CriticalAlertController;
use App\Http\Middleware\EnsureHospitalDatabaseTenant;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use MicroHis\Alertas\Application\AlertRepository;
use MicroHis\Alertas\Application\HandleResultValidated;
use MicroHis\Alertas\Persistence\PdoAlertRepository;

/**
 * Integra el Micro-HIS de alertas críticas (ASII-21) con la API v1: cablea el adaptador PDO sobre HOSPITAL (`critical_alerts`, 000660)
 * con conexión perezosa (base caída = 503, no 500). El actor local lo resuelve ResolutorActorLocal, que registra Traslados.
 */
final class AlertasCriticasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/alertas-criticas/bootstrap.php');

        $this->app->bind(AlertRepository::class, static fn (): AlertRepository => new PdoAlertRepository(
            static fn (): \PDO => DB::connection('hospital')->getPdo(),
        ));
        $this->app->bind(HandleResultValidated::class, static fn ($app): HandleResultValidated => new HandleResultValidated(
            $app->make(AlertRepository::class),
            static fn (): DateTimeImmutable => now()->toDateTimeImmutable(),
            static fn (): string => (string) Str::uuid(),
        ));
    }

    public function boot(): void
    {
        // La detección la dispara quien valida el resultado (ASII-20); la bandeja de la jefatura reúne las alertas sin médico tratante.
        Route::prefix('api/v1/critical-alerts')->middleware(['api', 'tenant', 'jwt.auth'])->group(function (): void {
            Route::post('result-validated', [CriticalAlertController::class, 'detect'])->middleware(['rbac:lab-results.validate', EnsureHospitalDatabaseTenant::class]);
            Route::get('/', [CriticalAlertController::class, 'inbox'])->middleware(['rbac:alertas.ver', EnsureHospitalDatabaseTenant::class]);
            Route::get('service-head', [CriticalAlertController::class, 'serviceHeadInbox'])->middleware(['rbac:alertas.supervisar', EnsureHospitalDatabaseTenant::class]);
            Route::post('{alert}/acknowledge', [CriticalAlertController::class, 'acknowledge'])->middleware(['rbac:alertas.acusar', EnsureHospitalDatabaseTenant::class]);
            Route::post('{alert}/acknowledge-as-service-head', [CriticalAlertController::class, 'acknowledgeAsServiceHead'])
                ->middleware(['rbac:alertas.supervisar', EnsureHospitalDatabaseTenant::class]);
        });
    }
}
