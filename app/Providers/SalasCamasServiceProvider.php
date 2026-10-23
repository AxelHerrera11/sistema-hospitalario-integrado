<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Api\V1\WardCatalogController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MicroHis\SalasCamas\Application\BedRepository;
use MicroHis\SalasCamas\Application\CatalogService;
use MicroHis\SalasCamas\Application\PersistenceFailure;
use MicroHis\SalasCamas\Application\WardRepository;
use MicroHis\SalasCamas\Persistence\PdoBedRepository;
use MicroHis\SalasCamas\Persistence\PdoWardRepository;
use PDO;
use Throwable;

final class SalasCamasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/salas-camas/bootstrap.php');

        $this->app->scoped(
            WardRepository::class,
            fn (): WardRepository => new PdoWardRepository(fn (): PDO => $this->hospitalConnection()),
        );
        $this->app->scoped(
            BedRepository::class,
            fn (): BedRepository => new PdoBedRepository(fn (): PDO => $this->hospitalConnection()),
        );
        $this->app->scoped(
            CatalogService::class,
            fn ($app): CatalogService => new CatalogService(
                $app->make(WardRepository::class),
                $app->make(BedRepository::class),
            ),
        );
    }

    public function boot(): void
    {
        Route::prefix('api/v1')->middleware(['api', 'tenant', 'jwt.auth'])->group(function (): void {
            Route::get('wards', [WardCatalogController::class, 'index'])
                ->middleware('rbac:camas.ver');
            Route::get('wards/{ward}/beds', [WardCatalogController::class, 'beds'])
                ->middleware('rbac:camas.ver');
        });
    }

    private function hospitalConnection(): PDO
    {
        try {
            return DB::connection('hospital')->getPdo();
        } catch (Throwable $error) {
            throw new PersistenceFailure('La base HOSPITAL no está disponible.', previous: $error);
        }
    }
}
