<?php

declare(strict_types=1);

namespace App\QaCalidad;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\QaCalidad\Application\QaPermissions;
use MicroHis\QaCalidad\Ci\ModuleFactory;
use MicroHis\QaCalidad\Domain\TestRunRepository;
use MicroHis\QaCalidad\Integration\GobernanzaAuditTrail;
use MicroHis\QaCalidad\Integration\RbacAccessGate;
use MicroHis\QaCalidad\Integration\SystemClock;
use MicroHis\QaCalidad\QaModule;
use MicroHis\Rbac\Application\Authorize;

/**
 * Conecta micro-his/qa-calidad-versiones (gestión de ejecuciones automatizadas y calidad de versiones) con Laravel.
 *
 * Registrado después de Gobernanza en bootstrap/providers.php: reutiliza RecordAuditEvent (bitácora CENTRAL) y
 * Authorize (RBAC). Las ejecuciones se guardan en un SQLite propio (config/qa.php), no en CENTRAL ni HOSPITAL:
 * no hay migraciones de PostgreSQL. Ver docs/CI-E2E/semana-16/integracion-laravel.md.
 */
final class QaCalidadServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/qa-calidad-versiones/bootstrap.php');

        $this->app->singleton(TestRunRepository::class, static function (): TestRunRepository {
            $path = (string) config('qa.runs_sqlite');
            if ($path !== ':memory:' && ! is_dir(dirname($path))) {
                mkdir(dirname($path), 0750, true);
            }

            return ModuleFactory::repository($path);
        });
        $this->app->bind(QaModule::class, static fn ($app): QaModule => QaModule::compose(
            $app->make(TestRunRepository::class),
            new RbacAccessGate($app->make(Authorize::class)),
            new GobernanzaAuditTrail($app->make(RecordAuditEvent::class)),
            new SystemClock,
            ModuleFactory::policy(base_path('micro-his/qa-calidad-versiones/config/quality-gate.json')),
            static fn (string $code, array $context) => Log::error('qa_calidad.'.$code, array_filter($context, 'is_scalar')),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ImportCiEvidence::class]);
        }
        // Mismo orden que el resto de la API: tenant y jwt.auth antes del permiso; el caso de uso lo vuelve a exigir.
        Route::prefix('api/v1/qa/runs')->middleware(['api', 'tenant', 'jwt.auth'])->group(function (): void {
            Route::get('/', [QaRunController::class, 'handle'])->middleware('rbac:'.QaPermissions::VIEW);
            Route::get('{run}', [QaRunController::class, 'handle'])->middleware('rbac:'.QaPermissions::VIEW);
            Route::post('/', [QaRunController::class, 'handle'])->middleware('rbac:'.QaPermissions::REGISTER);
            Route::post('{run}/transitions', [QaRunController::class, 'handle'])->middleware('rbac:'.QaPermissions::REGISTER);
            Route::post('{run}/results', [QaRunController::class, 'handle'])->middleware('rbac:'.QaPermissions::REGISTER);
            Route::post('{run}/gate-decisions', [QaRunController::class, 'handle'])->middleware('rbac:'.QaPermissions::EVALUATE);
        });
    }
}
