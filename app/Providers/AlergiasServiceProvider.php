<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Api\V1\AllergyController;
use App\Http\Middleware\EnsureHospitalDatabaseTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use MicroHis\Alergias\Application\AllergyRepository;
use MicroHis\Alergias\Application\RegisterAllergy;
use MicroHis\Alergias\Persistence\PdoAllergyRepository;

/**
 * Integra el Micro-HIS de alergias (ASII-12) con la API v1: cablea el adaptador PDO sobre HOSPITAL con conexión perezosa (base
 * caída = 503, no 500). El actor local lo resuelve ResolutorActorLocal, que registra TrasladosAltasServiceProvider.
 */
final class AlergiasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once base_path('micro-his/alergias/bootstrap.php');

        $this->app->bind(AllergyRepository::class, static fn (): AllergyRepository => new PdoAllergyRepository(
            static fn (): \PDO => DB::connection('hospital')->getPdo(),
        ));
        $this->app->bind(RegisterAllergy::class, static fn ($app): RegisterAllergy => new RegisterAllergy(
            $app->make(AllergyRepository::class),
            static fn (): string => (string) Str::uuid(),
        ));
    }

    public function boot(): void
    {
        // tenant y jwt.auth antes del permiso (ya activos); después, el guard de #1154: solo el hospital dueño de la base HOSPITAL.
        Route::prefix('api/v1')->middleware(['api', 'tenant', 'jwt.auth'])->group(function (): void {
            Route::post('patients/{patient}/allergies', [AllergyController::class, 'store'])->middleware(['rbac:alergias.registrar', EnsureHospitalDatabaseTenant::class]);
            Route::get('patients/{patient}/allergy-alert', [AllergyController::class, 'alert'])->middleware(['rbac:alergias.ver', EnsureHospitalDatabaseTenant::class]);
        });
    }
}
