<?php
use App\Http\Controllers\Api\V1\AdmissionController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BedController;
use App\Http\Controllers\Api\V1\DoctorController;
use App\Http\Controllers\Api\V1\LabTestController;
use App\Http\Controllers\Api\V1\PatientController;
use App\Http\Controllers\Api\V1\SpecialtyController;
use App\Http\Controllers\Api\V1\WardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — prefijo /api/v1 (ver bootstrap/app.php)
|--------------------------------------------------------------------------
| Toda ruta requiere la cabecera X-Tenant-ID (middleware "tenant").
| Rutas protegidas: ['tenant', 'auth.jwt'] + permiso del módulo, p. ej.:
|
|   Route::middleware(['tenant', 'auth.jwt'])->group(function () {
|       Route::get('/patients', [PatientController::class, 'index'])
|           ->middleware('permission:pacientes.ver');
|   });
|
| Cada área agrega sus rutas en un grupo propio y comentado con su nombre
| para minimizar conflictos de merge en este archivo.
*/

Route::middleware('tenant')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');
});

Route::middleware(['tenant', 'auth.jwt'])->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Alta de usuarios: solo Admin del hospital.
    Route::post('/auth/register', [AuthController::class, 'register'])
        ->middleware('role:Admin');
});

Route::middleware(['tenant', 'jwt.refresh'])->group(function (): void {
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
});

// ── Área 1: Auth, usuarios, RBAC y auditoría ──────────────────────────────
// ── Área 2: Pacientes y expediente base ───────────────────────────────────
Route::middleware(['tenant', 'auth.jwt'])->group(function (): void {
    Route::get('/patients', [PatientController::class, 'index'])
        ->middleware('permission:pacientes.ver');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])
        ->middleware('permission:pacientes.ver');
    Route::post('/patients', [PatientController::class, 'store'])
        ->middleware('permission:pacientes.crear');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])
        ->middleware('permission:pacientes.editar');
});
// ── Área 3: Médicos, especialidades y citas ───────────────────────────────
Route::middleware(['tenant', 'auth.jwt'])->group(function (): void {
    Route::get('/specialties', [SpecialtyController::class, 'index'])
        ->middleware('permission:medicos.ver');
    Route::post('/specialties', [SpecialtyController::class, 'store'])
        ->middleware('permission:medicos.gestionar');
    Route::put('/specialties/{specialty}', [SpecialtyController::class, 'update'])
        ->middleware('permission:medicos.gestionar');

    Route::get('/doctors', [DoctorController::class, 'index'])
        ->middleware('permission:medicos.ver');
    Route::post('/doctors', [DoctorController::class, 'store'])
        ->middleware('permission:medicos.gestionar');
    Route::put('/doctors/{doctor}', [DoctorController::class, 'update'])
        ->middleware('permission:medicos.gestionar');

    Route::get('/appointments', [AppointmentController::class, 'index'])
        ->middleware('permission:citas.ver');
    Route::post('/appointments', [AppointmentController::class, 'store'])
        ->middleware('permission:citas.crear');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])
        ->middleware('permission:citas.editar');
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])
        ->middleware('permission:citas.cancelar');
    Route::post('/appointments/{appointment}/status', [AppointmentController::class, 'status'])
        ->middleware('permission:citas.editar');
});
// ── Área 4: Salas, camas, admisión, traslados y altas ─────────────────────

Route::middleware(['tenant', 'auth.jwt'])->group(function (): void {
    Route::get('/wards', [WardController::class, 'index'])
        ->middleware('permission:camas.ver');

    Route::get('/wards/{ward}/beds', [WardController::class, 'beds'])
        ->middleware('permission:camas.ver');

    Route::get('/beds', [BedController::class, 'index'])
        ->middleware('permission:camas.ver');

    Route::patch('/beds/{bed}/status', [BedController::class, 'updateStatus'])
        ->middleware('permission:camas.gestionar');

    Route::post('/admissions', [AdmissionController::class, 'store'])
        ->middleware('permission:admisiones.crear');
});
// ── Área 5: Notas SOAP, diagnósticos y signos vitales ─────────────────────
// ── Área 6: Alergias, medicamentos y prescripciones ───────────────────────
// ── Área 7: Laboratorio ───────────────────────────────────────────────────
Route::middleware(['tenant', 'auth.jwt'])->group(function (): void {
    Route::get('/lab-tests', [LabTestController::class, 'index'])
        ->middleware('permission:laboratorio.ver');
    Route::get('/lab-tests/{labTest}', [LabTestController::class, 'show'])
        ->middleware('permission:laboratorio.ver');
    Route::post('/lab-tests', [LabTestController::class, 'store'])
        ->middleware('permission:laboratorio.gestionar_catalogo');
    Route::put('/lab-tests/{labTest}', [LabTestController::class, 'update'])
        ->middleware('permission:laboratorio.gestionar_catalogo');
});
// ── Área 8: Alertas críticas, dashboard y reportes ────────────────────────
