<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use MicroHis\Alergias\Application\AllergyRepository;
use MicroHis\Alergias\Application\GetAllergyAlert;
use MicroHis\Alergias\Application\PersistenceFailure;
use MicroHis\Alergias\Application\RegisterAllergy;
use MicroHis\Alergias\Domain\DomainRuleViolation;
use PDOException;

/**
 * Alergias clínicas y alerta visual del expediente (ASII-12). Traduce HTTP a los casos de uso del Micro-HIS; las reglas
 * clínicas viven en el dominio. El actor sale del JWT, nunca del cuerpo. El log no contiene alérgenos ni datos del paciente.
 */
final class AllergyController extends Controller
{
    private const FIELDS = ['allergen_code', 'allergen_name', 'reaction', 'allergy_type', 'severity'];

    private const STATUS = ['PATIENT_NOT_FOUND' => 404, 'ALLERGY_ALREADY_ACTIVE' => 409];

    public function __construct(
        private readonly RegisterAllergy $register,
        private readonly GetAllergyAlert $alert,
        private readonly AllergyRepository $allergies,
        private readonly ResolutorActorLocal $actors,
    ) {}

    public function store(Request $request, string $patient): JsonResponse
    {
        $requestId = $this->requestId($request);
        if (! Str::isUuid($patient)) {
            return $this->patientNotFound($requestId);
        }

        try {
            $actor = $this->actors->referencia((string) $request->user()->getAuthIdentifier());
            if ($actor === null) {
                return $this->error('ACTOR_WITHOUT_LOCAL_REFERENCE', 'El usuario no tiene una referencia local vigente en este hospital.', 403, $requestId);
            }
            $allergy = $this->register->execute($patient, $request->only(self::FIELDS), $actor);
        } catch (DomainRuleViolation $violation) {
            return $this->error($violation->rule, 'La alergia no cumple una regla clínica.', self::STATUS[$violation->rule] ?? 422, $requestId, $violation->field);
        } catch (PersistenceFailure|PDOException) {
            return $this->unavailable($request, $requestId, 'alergias.registro_no_disponible');
        }

        Log::info('alergias.registrada', ['request_id' => $requestId, 'hospital_id' => $request->header('X-Tenant-ID'), 'allergy_id' => $allergy->id]);

        return $this->json(['allergy_id' => $allergy->id, 'local_patient_id' => $allergy->patientId, 'allergen_code' => $allergy->allergenCode,
            'allergen_name' => $allergy->allergenName, 'reaction' => $allergy->reaction, 'allergy_type' => $allergy->type,
            'severity' => $allergy->severity, 'status' => 'active'], 201, $requestId);
    }

    /** Falla segura: si no se pudo leer responde 503 con el aviso del dominio, nunca «sin alergias». */
    public function alert(Request $request, string $patient): JsonResponse
    {
        $requestId = $this->requestId($request);

        try {
            if (! Str::isUuid($patient) || ! $this->allergies->patientExists($patient)) {
                return $this->patientNotFound($requestId);
            }
        } catch (PersistenceFailure) {
            return $this->unavailable($request, $requestId, 'alergias.alerta_no_disponible');
        }
        $alert = $this->alert->execute($patient);

        if ($alert->level === 'unavailable') {
            return $this->unavailable($request, $requestId, 'alergias.alerta_no_disponible', $alert->message);
        }

        return $this->json(['level' => $alert->level, 'message' => $alert->message, 'allergies' => $alert->allergies], 200, $requestId);
    }

    private function patientNotFound(string $requestId): JsonResponse
    {
        return $this->error('PATIENT_NOT_FOUND', 'El paciente no existe en este hospital.', 404, $requestId, 'patient_id');
    }

    private function unavailable(Request $request, string $requestId, string $event, ?string $message = null): JsonResponse
    {
        Log::warning($event, ['request_id' => $requestId, 'hospital_id' => $request->header('X-Tenant-ID')]);

        return $this->error('ALLERGIES_UNAVAILABLE', $message ?? 'La base de alergias no está disponible.', 503, $requestId)->header('Retry-After', '5');
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
