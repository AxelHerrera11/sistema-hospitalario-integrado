<?php

use App\Http\Controllers\Api\V1\Admin\HospitalController;
use App\Http\Controllers\Api\V1\Admin\HospitalUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DoctorApiController;
use App\Http\Controllers\Api\V1\OccupancyController;
use App\Http\Controllers\Api\V1\LabModulesController;
use App\Http\Controllers\Api\V1\ReportesIndicatorController;
use App\Http\Middleware\EnsureHospitalDatabaseTenant;
use Illuminate\Support\Facades\Route;

Route::middleware('tenant')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
});

Route::middleware(['tenant', 'jwt.auth'])->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::prefix('admin')->group(function (): void {
        Route::get('/users', [HospitalUserController::class, 'index'])
            ->middleware('rbac:usuarios.ver');
        Route::post('/users', [HospitalUserController::class, 'store'])
            ->middleware('rbac:usuarios.crear');
        Route::post('/users/{user}/roles', [HospitalUserController::class, 'assign'])
            ->middleware('rbac:rbac.asignar');
        Route::delete('/users/{user}/roles/{role}', [HospitalUserController::class, 'revoke'])
            ->middleware('rbac:rbac.asignar');
        Route::post('/users/{user}/deactivate', [HospitalUserController::class, 'deactivate'])
            ->middleware('rbac:usuarios.desactivar');

        Route::post('/tenants', [HospitalController::class, 'store'])
            ->middleware('rbac:tenants.crear');
        Route::delete('/sessions/{session}', [HospitalUserController::class, 'revokeSession'])
            ->middleware('rbac:sesiones.revocar_ajenas');
    });
});

Route::middleware(['tenant', 'jwt.auth', 'rbac:ocupacion.ver'])->group(function (): void {
    Route::get('/occupancy', [OccupancyController::class, 'index']);
});

Route::middleware(['tenant', 'jwt.auth', EnsureHospitalDatabaseTenant::class])->group(function (): void {
    Route::get('/nativephp/actions', [LabModulesController::class, 'nativeActions'])->middleware('rbac:lab-results.read');
    Route::post('/nativephp/actions', [LabModulesController::class, 'prepareAction'])->middleware('rbac:lab-results.create');
    Route::post('/nativephp/sync', [LabModulesController::class, 'syncActions'])->middleware('rbac:lab-results.create');
    Route::get('/lab-validation/results', [LabModulesController::class, 'reviews'])->middleware('rbac:lab-results.read');
    Route::post('/lab-validation/results/{resultId}/validate', [LabModulesController::class, 'validateResult'])->middleware('rbac:lab-results.validate');
    Route::post('/lab-validation/results/{resultId}/reject', [LabModulesController::class, 'reject'])->middleware('rbac:lab-results.validate');
    Route::get('/lab-results', [LabModulesController::class, 'results'])->middleware('rbac:lab-results.read');
    Route::post('/lab-results/{resultId}/capture', [LabModulesController::class, 'capture'])->middleware('rbac:lab-results.create');
    Route::post('/lab-results/{resultId}/correct', [LabModulesController::class, 'correct'])->middleware('rbac:lab-results.correct');
});

Route::middleware(['tenant', 'jwt.auth', EnsureHospitalDatabaseTenant::class, 'rbac:reportes.ver'])->group(function (): void {
    Route::post('/reports/indicators/IND-RA-01/calculate', [ReportesIndicatorController::class, 'calcular']);
});

// Médicos y especialidades (ASII-04): cada operación declara su propio permiso; usa la base HOSPITAL de esta instancia.
Route::middleware(['tenant', 'jwt.auth', EnsureHospitalDatabaseTenant::class])->group(function (): void {
    // Límite de tasa por usuario (MED-14): las escrituras son raras; la consulta de disponibilidad es más frecuente.
    // El prefijo separa los contadores: sin él, todas las rutas `throttle:N,1` del usuario comparten uno.
    Route::post('/doctors', [DoctorApiController::class, 'register'])
        ->middleware(['rbac:medicos.registrar', 'throttle:30,1,medicos-escritura']);
    Route::post('/doctors/{doctor}/specialties', [DoctorApiController::class, 'associateSpecialty'])
        ->middleware(['rbac:medicos.asociar_especialidad', 'throttle:30,1,medicos-escritura']);
    Route::get('/availability', [DoctorApiController::class, 'availability'])
        ->middleware(['rbac:medicos.ver_disponibilidad', 'throttle:120,1,medicos-lectura']);
});
