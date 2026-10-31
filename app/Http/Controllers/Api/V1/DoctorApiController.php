<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Medicos\HospitalActors;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MicroHis\Medicos\Presentation\Request as DoctorRequest;
use MicroHis\Medicos\Presentation\Router;

/**
 * Puerta HTTP del Micro-HIS de médicos (ASII-04). No tiene reglas: arma la petición del módulo con el actor local
 * y delega en su Router, que conserva el contrato de la Semana 5. Tenant, JWT y permiso los resuelven los middleware de la ruta.
 */
final class DoctorApiController extends Controller
{
    public function __construct(private readonly HospitalActors $actors)
    {
    }

    public function register(Request $request): JsonResponse
    {
        return $this->handle($request, '/doctors');
    }

    public function associateSpecialty(Request $request, string $doctor): JsonResponse
    {
        return $this->handle($request, "/doctors/$doctor/specialties");
    }

    public function availability(Request $request): JsonResponse
    {
        return $this->handle($request, '/availability');
    }

    private function handle(Request $request, string $path): JsonResponse
    {
        try {
            $actor = $this->actors->localUserRef((string) $request->user('api')?->getAuthIdentifier());
            if ($actor === null) {
                return $this->failure(403, 'ACTOR_NOT_PROVISIONED');
            }
            $result = app(Router::class)->dispatch(new DoctorRequest(
                $request->method(), $path, ['X-User-ID' => $actor], $request->query(), $request->json()->all(),
            ));
        } catch (\PDOException) { // incluye QueryException
            return $this->failure(503, 'DOCTOR_STORAGE_UNAVAILABLE');
        }

        return response()->json(json_decode($result['body'], true), $result['status'])->header('Cache-Control', 'no-store, private');
    }

    private function failure(int $status, string $code): JsonResponse
    {
        return response()->json(['code' => $code], $status)->header('Cache-Control', 'no-store, private');
    }
}
