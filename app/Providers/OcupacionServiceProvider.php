<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use MicroHis\Occupancy\Application\BedSnapshotReader;
use MicroHis\Occupancy\Application\ConsultOccupancy;
use MicroHis\Occupancy\Application\OccupancySummaryQuery;
use MicroHis\Occupancy\Application\PersistenceFailure;
use MicroHis\Occupancy\Domain\OccupancyCalculator;
use MicroHis\Occupancy\Persistence\PdoBedSnapshotReader;

/**
 * Conecta el Micro-HIS de ocupación (semanas 3 y 5) con Laravel: el dominio
 * no depende del framework; aquí solo se cablea el adaptador real, con
 * conexión perezosa a la base HOSPITAL.
 */
final class OcupacionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/ocupacion-hospitalaria/bootstrap.php');

        $this->app->bind(
            BedSnapshotReader::class,
            fn () => new PdoBedSnapshotReader(fn () => $this->hospitalConnection()),
        );

        $this->app->bind(
            OccupancySummaryQuery::class,
            fn ($app) => new ConsultOccupancy(
                $app->make(BedSnapshotReader::class),
                new OccupancyCalculator(),
            ),
        );
    }

    /** Conexión perezosa: si la base no responde, el endpoint responde 503 y no 500. */
    private function hospitalConnection(): \PDO
    {
        try {
            return DB::connection('hospital')->getPdo();
        } catch (\Throwable $error) {
            throw new PersistenceFailure('Hospital occupancy is unavailable.', previous: $error);
        }
    }
}
