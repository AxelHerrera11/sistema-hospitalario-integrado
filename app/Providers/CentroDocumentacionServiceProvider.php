<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Api\V1\DocumentacionController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MicroHis\CentroDocumentacion\Application\ConsultarCatalogo;

/**
 * Integra el centro de documentación (ASII-24, semana 16) con la API v1. El catálogo vive en
 * config/centro_documentacion.php y no toca la base HOSPITAL; el permiso documentacion.ver ya está activo.
 */
final class CentroDocumentacionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/centro-documentacion/bootstrap.php');

        $this->app->bind(ConsultarCatalogo::class, static fn (): ConsultarCatalogo => new ConsultarCatalogo(config('centro_documentacion.catalogo', [])));
    }

    public function boot(): void
    {
        Route::prefix('api/v1/documentacion')->middleware(['api', 'tenant', 'jwt.auth', 'rbac:documentacion.ver'])->group(function (): void {
            Route::get('/', [DocumentacionController::class, 'index']);
            Route::get('{documento}', [DocumentacionController::class, 'contenido']);
        });
    }
}
