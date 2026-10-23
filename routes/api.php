<?php

use App\Http\Controllers\Api\V1\Admin\HospitalController;
use App\Http\Controllers\Api\V1\Admin\HospitalUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DoctorApiController;
use App\Http\Controllers\Api\V1\OccupancyController;
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

// Médicos y especialidades (ASII-04): cada operación declara su propio permiso.
Route::middleware(['tenant', 'jwt.auth'])->group(function (): void {
    // Límite de tasa por usuario (MED-14): las escrituras son raras; la consulta de disponibilidad es más frecuente.
    // El prefijo separa los contadores: sin él, todas las rutas `throttle:N,1` del usuario comparten uno.
    Route::post('/doctors', [DoctorApiController::class, 'register'])
        ->middleware(['rbac:medicos.registrar', 'throttle:30,1,medicos-escritura']);
    Route::post('/doctors/{doctor}/specialties', [DoctorApiController::class, 'associateSpecialty'])
        ->middleware(['rbac:medicos.asociar_especialidad', 'throttle:30,1,medicos-escritura']);
    Route::get('/availability', [DoctorApiController::class, 'availability'])
        ->middleware(['rbac:medicos.ver_disponibilidad', 'throttle:120,1,medicos-lectura']);
});
