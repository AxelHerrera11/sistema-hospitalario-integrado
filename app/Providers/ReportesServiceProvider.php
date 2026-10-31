<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use MicroHis\Reportes\Application\EpisodeSummaryReader;
use MicroHis\Reportes\Persistence\PdoEpisodeSummaryReader;

/**
 * Conecta el Micro-HIS de reportes (semanas 3 y 5) con Laravel: el dominio
 * no depende del framework; aquí solo se cablea el adaptador real, con
 * conexión perezosa a la base HOSPITAL.
 */
final class ReportesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/reportes-analytics/bootstrap.php');

        $this->app->bind(
            EpisodeSummaryReader::class,
            fn () => new PdoEpisodeSummaryReader($this->hospitalConnection()),
        );
    }

    /** Conexión perezosa: si la base no responde, el endpoint responde 503 y no 500. */
    private function hospitalConnection(): \PDO
    {
        return DB::connection('hospital')->getPdo();
    }
}
