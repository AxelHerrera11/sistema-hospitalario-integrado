<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Api\V1\PatientApiController;
use App\Patients\HospitalPatientRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MicroHis\Pacientes\Application\PatientRepository;
use MicroHis\Pacientes\Application\PatientService;
use PDO;

final class PatientsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/pacientes/bootstrap.php');
        $this->app->bind(PatientRepository::class, static fn (): PatientRepository =>
            new HospitalPatientRepository(static fn (): PDO => DB::connection('hospital')->getPdo()));
        $this->app->bind(PatientService::class, static fn ($app): PatientService =>
            new PatientService($app->make(PatientRepository::class)));
    }

    public function boot(): void
    {
        Route::prefix('api/v1')->middleware(['api', 'tenant', 'jwt.auth'])->group(function (): void {
            Route::get('patients/{code}', [PatientApiController::class, 'show'])
                ->middleware('rbac:pacientes.ver');
        });
    }
}
