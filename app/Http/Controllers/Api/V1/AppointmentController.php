<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\AppointmentWriteException;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Repositories\Contracts\AppointmentRepositoryContract;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * API v1 de citas (ASII-05). Valida en el borde, delega en el repositorio y responde con el sobre común
 * de ASII-24: `{data}` en éxito y `{message, code, trace_id[, errors]}` en error. Las reglas de solape,
 * especialidad e inmutabilidad las impone la base HOSPITAL y llegan como AppointmentWriteException.
 */
final class AppointmentController extends Controller
{
    private const STATUSES = [Appointment::STATUS_SCHEDULED, Appointment::STATUS_CONFIRMED, Appointment::STATUS_COMPLETED, Appointment::STATUS_CANCELLED];

    public function __construct(private readonly AppointmentRepositoryContract $citas)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filtros = $request->query();
        if (isset($filtros['status']) && is_string($filtros['status'])) {
            $filtros['status'] = [$filtros['status']];
        }
        $validator = Validator::make($filtros, [
            'status' => ['sometimes', 'array'],
            'status.*' => [Rule::in(self::STATUSES)],
            'doctor_profile_id' => ['sometimes', 'uuid'],
            'local_patient_id' => ['sometimes', 'uuid'],
            'date_from' => ['sometimes', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);
        if ($validator->fails()) {
            return $this->error($request, 422, 'validation_failed', 'Los datos enviados no son válidos.', $validator->errors()->toArray());
        }
        $pagina = $this->citas->paginate($validator->validated());

        return response()->json([
            'data' => array_map(fn (Appointment $c): array => $this->recurso($c), $pagina->items()),
            'meta' => [
                'current_page' => $pagina->currentPage(), 'per_page' => $pagina->perPage(),
                'total' => $pagina->total(), 'last_page' => $pagina->lastPage(),
            ],
        ])->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'local_patient_id' => ['required', 'uuid'],
            'doctor_profile_id' => ['required', 'uuid'],
            'specialty_id' => ['required', 'uuid'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'reason' => ['required', 'string', 'max:500', 'not_regex:/^\s*$/'],
        ]);
        if ($validator->fails()) {
            return $this->error($request, 422, 'validation_failed', 'Los datos enviados no son válidos.', $validator->errors()->toArray());
        }

        // El cast datetime del modelo descarta el offset al guardar: se normaliza a UTC (zona de la app) antes.
        $datos = $validator->validated();
        foreach (['starts_at', 'ends_at'] as $campo) {
            $datos[$campo] = Carbon::parse($datos[$campo])->utc()->format('Y-m-d H:i:s');
        }

        return $this->ejecutar($request, fn (): Appointment => $this->citas->create($datos), 201);
    }

    public function show(Request $request, string $appointment): JsonResponse
    {
        return $this->ejecutar($request, fn (): Appointment => $this->buscar($appointment));
    }

    /** Confirmar, completar o cancelar: el estado destino lo fija la ruta (`defaults('estado', ...)`). */
    public function transition(Request $request, string $appointment, string $estado): JsonResponse
    {
        return $this->ejecutar($request, fn (): Appointment => $this->citas->changeStatus($this->buscar($appointment), $estado));
    }

    public function destroy(Request $request): JsonResponse
    {
        return $this->error($request, 405, 'appointments_delete_protection', 'Las citas no se eliminan; use la cancelación.');
    }

    private function buscar(string $id): Appointment
    {
        if (! Str::isUuid($id)) {
            throw (new ModelNotFoundException())->setModel(Appointment::class, [$id]);
        }

        return $this->citas->find($id);
    }

    /** @param callable(): Appointment $operacion */
    private function ejecutar(Request $request, callable $operacion, int $status = 200): JsonResponse
    {
        try {
            $cita = $operacion();
        } catch (ModelNotFoundException) {
            return $this->error($request, 404, 'appointment_not_found', 'La cita no existe en este hospital.');
        } catch (AppointmentWriteException $e) {
            return $this->error($request, $e->status, $e->errorCode, $e->getMessage());
        }

        return response()->json(['data' => $this->recurso($cita)], $status)->header('Cache-Control', 'no-store, private');
    }

    /** @return array<string, string|null> */
    private function recurso(Appointment $cita): array
    {
        return $cita->only(['appointment_id', 'local_patient_id', 'doctor_profile_id', 'specialty_id', 'status', 'reason'])
            + ['starts_at' => $cita->starts_at?->toIso8601String(), 'ends_at' => $cita->ends_at?->toIso8601String()];
    }

    /** @param array<string, list<string>>|null $errores */
    private function error(Request $request, int $status, string $code, string $mensaje, ?array $errores = null): JsonResponse
    {
        $traza = (string) $request->header('X-Request-Id', '');
        $traza = preg_match('/^[A-Za-z0-9._-]{8,64}$/', $traza) === 1 ? $traza : 'trace-'.bin2hex(random_bytes(6));
        $cuerpo = ['message' => $mensaje, 'code' => $code, 'trace_id' => $traza] + ($errores === null ? [] : ['errors' => $errores]);

        return response()->json($cuerpo, $status)->header('Cache-Control', 'no-store, private');
    }
}
