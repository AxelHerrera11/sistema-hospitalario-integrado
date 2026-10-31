<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Api\V1\ReportesOperativosController;
use App\Http\Middleware\EnsureHospitalDatabaseTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MicroHis\ReportesOperativos\Application\ReportSource;
use MicroHis\ReportesOperativos\Domain\CitasPorServicioReport;
use MicroHis\ReportesOperativos\Persistence\CsvReportExporter;
use MicroHis\ReportesOperativos\Persistence\PdoReportSource;

/** Reportes operativos (ASII-27, semana 16) en la API v1; nuevos reportes o formatos se agregan a los catálogos (OCP). */
final class ReportesOperativosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/reportes-operativos/bootstrap.php');

        $this->app->bind(ReportSource::class, static fn (): ReportSource => new PdoReportSource(static fn () => DB::connection('hospital')->getPdo()));
        $this->app->when(ReportesOperativosController::class)->needs('$definiciones')->give(static fn (): array => [new CitasPorServicioReport()]);
        $this->app->when(ReportesOperativosController::class)->needs('$exportadores')->give(static fn (): array => [new CsvReportExporter()]);
    }

    public function boot(): void
    {
        Route::prefix('api/v1/reportes')->middleware(['api', 'tenant', 'jwt.auth'])->group(function (): void {
            Route::get('/', [ReportesOperativosController::class, 'catalogo'])->middleware('rbac:reportes.ver');
            Route::get('{reporte}', [ReportesOperativosController::class, 'vistaPrevia'])->middleware(['rbac:reportes.ver', EnsureHospitalDatabaseTenant::class]);
            Route::get('{reporte}/export', [ReportesOperativosController::class, 'exportar'])->middleware(['rbac:reportes.exportar', EnsureHospitalDatabaseTenant::class]);
        });
    }
}
