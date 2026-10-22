<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            $view = (new PatientView())->patient($this->patients->search($code))['body']['patient'];
            $patient = ['patient_id' => $view['id']] + array_diff_key($view, ['id' => true]);

            return response()->json(['patient' => $patient])
                ->header('Cache-Control', 'no-store, private');
        } catch (PatientOperationFailure $failure) {
            $status = $failure->errorCode === 'PATIENT_NOT_FOUND' ? 404 : 422;

            return $this->problem($request, $status, $failure->errorCode, $failure->getMessage(), $failure->field);
        } catch (PersistenceFailure) {
            return $this->problem($request, 500, 'PERSISTENCE_FAILURE', 'No se pudo completar la operación.');
        }
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
