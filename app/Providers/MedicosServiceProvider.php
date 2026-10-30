<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use MicroHis\Medicos\Persistence\PdoDoctorStore;
use MicroHis\Medicos\Persistence\PdoScheduleProvider;
use MicroHis\Medicos\Presentation\Router;

/**
 * Conecta el Micro-HIS de médicos con Laravel: el dominio no depende del framework; aquí solo se cablea el
 * adaptador PDO sobre la conexión HOSPITAL. La zona horaria de las citas sale de `config('app.timezone')`.
 */
final class MedicosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/medicos-especialidades/app.php');

        $this->app->bind(Router::class, static function (): Router {
            $pdo = DB::connection('hospital')->getPdo();
            $store = new PdoDoctorStore($pdo, (string) config('app.timezone', 'UTC'));

            return medicos_router($store, $store, new PdoScheduleProvider($pdo));
        });
    }
}
