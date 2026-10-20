<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use MicroHis\Occupancy\Application\BedSnapshotReader;
use MicroHis\Occupancy\Application\ConsultOccupancy;
use MicroHis\Occupancy\Application\OccupancySummaryQuery;
use MicroHis\Occupancy\Domain\OccupancyCalculator;
use MicroHis\Occupancy\Persistence\PdoBedSnapshotReader;

/**
 * Conecta el Micro-HIS de ocupación (semanas 3 y 5) con Laravel.
 *
 * El dominio y la aplicación no dependen del framework; aquí solo se cablea
 * el adaptador real: la lectura de camas usa la conexión HOSPITAL configurada
 * para el hospital que atiende este proceso.
 */
final class OcupacionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/ocupacion-hospitalaria/bootstrap.php');

        $this->app->bind(
            BedSnapshotReader::class,
            fn () => new PdoBedSnapshotReader(DB::connection('hospital')->getPdo()),
        );

        $this->app->bind(
            OccupancySummaryQuery::class,
            fn ($app) => new ConsultOccupancy(
                $app->make(BedSnapshotReader::class),
                new OccupancyCalculator(),
            ),
        );
    }
}
