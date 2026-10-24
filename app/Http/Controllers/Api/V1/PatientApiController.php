<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use MicroHis\Pacientes\Application\PatientOperationFailure;
use MicroHis\Pacientes\Application\PatientService;
use MicroHis\Pacientes\Application\PersistenceFailure;
use MicroHis\Pacientes\Presentation\PatientView;

final class PatientApiController extends Controller
{
    public function __construct(private readonly PatientService $patients)
    {
    }

    public function show(Request $request, string $code): JsonResponse
    {
        try {
            return $this->success($this->patients->search($code));
        } catch (PatientOperationFailure $failure) {
            return $this->operationError($request, $failure);
        } catch (PersistenceFailure) {
            return $this->problem($request, 500, 'PERSISTENCE_FAILURE', 'No se pudo completar la operación.');
        }
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'global_id' => ['required', 'string', 'uuid'],
            'code' => ['required', 'string', 'min:1'],
            'given_name' => ['required', 'string', 'min:1'],
            'family_name' => ['required', 'string', 'min:1'],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'sex' => ['required', 'in:female,male,other,unknown'],
            'contact' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:active,inactive,deceased'],
        ], ['global_id', 'code', 'given_name', 'family_name', 'birth_date', 'sex', 'contact', 'status']);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        try {
            return $this->success($this->patients->register($data), 201);
        } catch (PatientOperationFailure $failure) {
            return $this->operationError($request, $failure);
        } catch (PersistenceFailure $failure) {
            $conflict = $this->uniqueConflict($failure);
            return $conflict === null
                ? $this->problem($request, 500, 'PERSISTENCE_FAILURE', 'No se pudo completar la operación.')
                : $this->problem($request, 409, $conflict, 'El código o identificador global ya está registrado.');
        }
    }

    public function update(Request $request, string $code): JsonResponse
    {
        $data = $this->validated($request, [
            'given_name' => ['sometimes', 'string', 'min:1'],
            'family_name' => ['sometimes', 'string', 'min:1'],
            'birth_date' => ['sometimes', 'date_format:Y-m-d', 'before_or_equal:today'],
            'sex' => ['sometimes', 'in:female,male,other,unknown'],
            'contact' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'in:active,inactive,deceased'],
        ], ['given_name', 'family_name', 'birth_date', 'sex', 'contact', 'status']);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        try {
            return $this->success($this->patients->update($code, $data));
        } catch (PatientOperationFailure $failure) {
            return $this->operationError($request, $failure);
        } catch (PersistenceFailure) {
            return $this->problem($request, 500, 'PERSISTENCE_FAILURE', 'No se pudo completar la operación.');
        }
    }

    private function validated(Request $request, array $rules, array $allowed): array|JsonResponse
    {
        $data = $request->json()->all();
        $extra = array_diff(array_keys($data), $allowed);
        if ($extra !== []) {
            return $this->problem($request, 422, 'VALIDATION_FAILED', 'El campo no está permitido.', (string) reset($extra));
        }
        if ($data === [] && !in_array('required', array_merge(...array_values($rules)), true)) {
            return $this->problem($request, 422, 'VALIDATION_FAILED', 'Se requiere al menos un campo editable.');
        }

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return $this->problem($request, 422, 'VALIDATION_FAILED', 'El campo no es válido.', (string) $validator->errors()->keys()[0]);
        }

        return $validator->validated();
    }

    private function success(\MicroHis\Pacientes\Domain\Patient $patient, int $status = 200): JsonResponse
    {
        $view = (new PatientView())->patient($patient)['body']['patient'];
        $payload = ['patient_id' => $view['id']] + array_diff_key($view, ['id' => true]);

        return response()->json(['patient' => $payload], $status)->header('Cache-Control', 'no-store, private');
    }

    private function operationError(Request $request, PatientOperationFailure $failure): JsonResponse
    {
        $status = match ($failure->errorCode) {
            'PATIENT_NOT_FOUND' => 404,
            'PATIENT_CODE_EXISTS' => 409,
            default => 422,
        };

        return $this->problem($request, $status, $failure->errorCode, $failure->getMessage(), $failure->field);
    }

    private function uniqueConflict(PersistenceFailure $failure): ?string
    {
        for ($error = $failure->getPrevious(); $error !== null; $error = $error->getPrevious()) {
            if (preg_match('/local_patients_(global_id|code)_unique|local_patients\.(global_id|code)/i', $error->getMessage(), $match) === 1) {
                return str_contains(strtolower($match[0]), 'global_id') ? 'PATIENT_GLOBAL_ID_EXISTS' : 'PATIENT_CODE_EXISTS';
            }
        }

        return null;
    }

    private function problem(Request $request, int $status, string $code, string $title, ?string $field = null): JsonResponse
    {
        $requested = (string) $request->header('X-Request-Id', '');
        $requestId = preg_match('/^[A-Za-z0-9._-]{8,64}$/D', $requested) === 1
            ? $requested
            : 'req-' . bin2hex(random_bytes(8));

        return response()->json([
            'title' => $title,
            'status' => $status,
            'code' => $code,
            'field' => $field,
            'request_id' => $requestId,
        ], $status)->header('Content-Type', 'application/problem+json')
            ->header('Cache-Control', 'no-store, private');
    }
}
