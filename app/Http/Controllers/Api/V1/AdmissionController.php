<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use MicroHis\Admision\Application\AdmitPatient;
use MicroHis\Admision\Application\FindAvailableBeds;
use MicroHis\Admision\Application\PersistenceFailure;
use MicroHis\Admision\Domain\DomainRuleViolation;
use PDOException;

/**
 * Admisión hospitalaria y asignación de cama (ASII-07). Traduce HTTP a los casos de uso del Micro-HIS; las reglas (una cama por
 * paciente, un paciente por cama) viven en el dominio y en los índices de HOSPITAL. El actor sale del JWT, nunca del cuerpo.
 */
final class AdmissionController extends Controller
{
    private const STATUS = ['PATIENT_NOT_FOUND' => 404, 'BED_NOT_FOUND' => 404, 'PATIENT_ALREADY_ADMITTED' => 409, 'BED_NOT_AVAILABLE' => 409];

    public function __construct(private readonly AdmitPatient $admit, private readonly FindAvailableBeds $beds, private readonly ResolutorActorLocal $actors) {}

    public function store(Request $request): JsonResponse
    {
        $requestId = $this->requestId($request);
        $input = $request->only(['patient_id', 'bed_id', 'bed_type', 'reason']);
        foreach (['patient_id' => 'PATIENT_NOT_FOUND', 'bed_id' => 'BED_NOT_FOUND'] as $field => $code) {
            // Un arreglo u objeto en el JSON no es un identificador: 404 como cualquier otro, nunca 500.
            if (! is_string($input[$field] ?? null) || ! Str::isUuid($input[$field])) {
                return $this->error($code, 'El identificador no existe en este hospital.', 404, $requestId, $field);
            }
        }

        try {
            $actor = $this->actors->referencia((string) $request->user()->getAuthIdentifier());
            if ($actor === null) {
                return $this->error('ACTOR_WITHOUT_LOCAL_REFERENCE', 'El usuario no tiene una referencia local vigente en este hospital.', 403, $requestId);
            }
            $admission = $this->admit->execute($input, $actor);
        } catch (DomainRuleViolation $violation) {
            return $this->error($violation->rule, 'La admisión no cumple una regla del hospital.', self::STATUS[$violation->rule] ?? 422, $requestId, $violation->field);
        } catch (PersistenceFailure|PDOException) {
            return $this->unavailable($request, $requestId);
        }

        Log::info('admision.cama_asignada', ['request_id' => $requestId, 'hospital_id' => $request->header('X-Tenant-ID'), 'bed_stay_id' => $admission->stayId]);

        return $this->json(['care_episode_id' => $admission->episodeId, 'bed_stay_id' => $admission->stayId, 'local_patient_id' => $admission->patientId,
            'bed_id' => $admission->bedId, 'reason' => $admission->reason, 'started_at' => $admission->startedAt], 201, $requestId);
    }

    public function availableBeds(Request $request): JsonResponse
    {
        $requestId = $this->requestId($request);
        $type = $request->query('type', '');

        try {
            return $this->json(['beds' => $this->beds->execute(is_string($type) ? $type : '')], 200, $requestId);
        } catch (PersistenceFailure) {
            return $this->unavailable($request, $requestId);
        }
    }

    private function unavailable(Request $request, string $requestId): JsonResponse
    {
        Log::warning('admision.no_disponible', ['request_id' => $requestId, 'hospital_id' => $request->header('X-Tenant-ID')]);

        return $this->error('ADMISSIONS_UNAVAILABLE', 'La base de admisiones no está disponible.', 503, $requestId)->header('Retry-After', '5');
    }

    private function requestId(Request $request): string
    {
        $header = $request->header('X-Request-ID');

        return is_string($header) && preg_match('/^[A-Za-z0-9._-]{1,64}$/', $header) === 1 ? $header : 'req-'.bin2hex(random_bytes(8));
    }

    private function json(array $data, int $status, string $requestId): JsonResponse
    {
        return response()->json(['data' => $data, 'meta' => ['request_id' => $requestId]], $status)->header('Cache-Control', 'no-store, private');
    }

    private function error(string $code, string $message, int $status, string $requestId, ?string $field = null): JsonResponse
    {
        return response()->json(['error' => array_filter(['code' => $code, 'message' => $message, 'field' => $field]), 'meta' => ['request_id' => $requestId]], $status)
            ->header('Cache-Control', 'no-store, private');
    }
}
