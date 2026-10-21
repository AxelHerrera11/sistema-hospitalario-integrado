<?php

declare(strict_types=1);

namespace App\GobernanzaAuditoria;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MicroHis\Gobernanza\Application\AccessGate;
use MicroHis\Gobernanza\Application\AuditPermissions;
use MicroHis\Gobernanza\Application\Clock;
use MicroHis\Gobernanza\Application\FailureReporter;
use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\Gobernanza\Domain\AuditEventReader;
use MicroHis\Gobernanza\Domain\AuditLogger;
use MicroHis\Gobernanza\GobernanzaModule;
use MicroHis\Gobernanza\Integration\RbacAccessGate;
use MicroHis\Gobernanza\Integration\SystemClock;
use MicroHis\Gobernanza\Persistence\PdoAuditLog;
use MicroHis\Gobernanza\Presentation\AuditHttpHandler;
use MicroHis\Rbac\Application\Authorize;

/**
 * Conecta micro-his/gobernanza-auditoria con Laravel.
 *
 * NO está registrado en bootstrap/providers.php (archivo compartido). Para
 * activarlo se requiere, en este orden: RbacServiceProvider (develop), las
 * migraciones de audit_log en CENTRAL y el permiso auditoria.ver aprobado en
 * config/rbac.php. Ver docs/gobernanza-auditoria/semana-05/contrato-api.md.
 */
final class GobernanzaAuditoriaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/gobernanza-auditoria/bootstrap.php');

        $this->app->singleton(PdoAuditLog::class, fn () => new PdoAuditLog(DB::connection('central')->getPdo()));
        $this->app->bind(AuditLogger::class, PdoAuditLog::class);
        $this->app->bind(AuditEventReader::class, PdoAuditLog::class);
        $this->app->bind(Clock::class, SystemClock::class);
        $this->app->bind(FailureReporter::class, LaravelLogFailureReporter::class);
        $this->app->bind(AccessGate::class, fn ($app) => new RbacAccessGate($app->make(Authorize::class)));
        $this->app->bind(GobernanzaModule::class, fn ($app) => GobernanzaModule::compose(
            $app->make(AuditLogger::class),
            $app->make(AuditEventReader::class),
            $app->make(AccessGate::class),
            $app->make(Clock::class),
            $app->make(FailureReporter::class),
            (string) config('app.timezone', 'UTC'),
            AuditPermissions::VIEW,
        ));
        // Puntos de uso: otros módulos inyectan RecordAuditEvent; el controlador, AuditHttpHandler.
        $this->app->bind(RecordAuditEvent::class, fn ($app) => $app->make(GobernanzaModule::class)->recorder);
        $this->app->bind(AuditHttpHandler::class, fn ($app) => $app->make(GobernanzaModule::class)->http);
    }

    public function boot(): void
    {
        // Mismo orden de middleware que RbacServiceProvider: tenant -> jwt.auth -> rbac.
        Route::prefix('api/v1')->middleware(['api', 'tenant', 'jwt.auth', 'rbac:'.AuditPermissions::VIEW])
            ->group(function (): void {
                Route::get('audit-events', [AuditEventController::class, 'index']);
            });
    }
}
