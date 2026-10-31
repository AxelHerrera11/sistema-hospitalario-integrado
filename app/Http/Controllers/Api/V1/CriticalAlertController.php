<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use MicroHis\Alertas\Application\AcknowledgeAlert;
use MicroHis\Alertas\Application\GetInbox;
use MicroHis\Alertas\Application\HandleResultValidated;
use MicroHis\Alertas\Application\PersistenceFailure;
use MicroHis\Alertas\Domain\CriticalAlert;
use MicroHis\Alertas\Domain\DomainRuleViolation;
use PDOException;

/**
 * Alertas críticas de laboratorio (ASII-21): detección idempotente, bandejas y acuse con lectura de verificación. Las reglas viven
 * en el dominio y en `critical_alerts`; el actor sale del JWT. El log no contiene valores ni datos del paciente.
 */
final class CriticalAlertController extends Controller
{
    private const STATUS = ['ALERT_NOT_FOUND' => 404, 'NO_CARE_RELATION' => 403, 'ALERT_ALREADY_ACKNOWLEDGED' => 409];

    public function __construct(private readonly HandleResultValidated $handle, private readonly AcknowledgeAlert $acknowledge,
        private readonly GetInbox $inbox, private readonly ResolutorActorLocal $actors) {}

    public function detect(Request $request): JsonResponse
    {
        $event = $request->only(['result_version_id', 'lab_test_id', 'patient_id', 'episode_id', 'value']);
        foreach (['result_version_id', 'lab_test_id', 'patient_id', 'episode_id'] as $field) {
            // Un arreglo u objeto en el JSON no es un identificador: 422 como cualquier otro, nunca 500.
            if (! is_string($event[$field] ?? null) || ! Str::isUuid($event[$field])) {
                return $this->error('EVENT_INVALID', 'El evento de resultado validado no es válido.', 422, $request, $field);
            }
        }

        return $this->run($request, function (string $actor) use ($event, $request): JsonResponse {
            $result = $this->handle->execute($event, $actor);
            Log::info('alertas_criticas.deteccion', ['request_id' => $this->requestId($request), 'outcome' => $result['outcome'], 'alert_id' => $result['alert']?->id]);

            return $this->json(['outcome' => $result['outcome'], 'alert' => $result['alert'] ? $this->present($result['alert']) : null], $result['outcome'] === 'created' ? 201 : 200, $request);
        });
    }

    public function inbox(Request $request): JsonResponse
    {
        return $this->run($request, fn (string $actor): JsonResponse => $this->json(['alerts' => array_map($this->present(...), $this->inbox->execute($actor))], 200, $request));
    }

    public function serviceHeadInbox(Request $request): JsonResponse
    {
        return $this->run($request, fn (): JsonResponse => $this->json(['alerts' => array_map($this->present(...), $this->inbox->execute(CriticalAlert::SERVICE_HEAD))], 200, $request));
    }

    public function acknowledge(Request $request, string $alert, bool $supervisesService = false): JsonResponse
    {
        if (! Str::isUuid($alert)) {
            return $this->error('ALERT_NOT_FOUND', 'La alerta no existe en este hospital.', 404, $request, 'alert_id');
        }

        return $this->run($request, fn (string $actor): JsonResponse => $this->json(['alert' => $this->present(
            $this->acknowledge->execute($alert, $actor, (array) $request->input('read_back', []), $supervisesService))], 200, $request));
    }

    /** Solo por la ruta con rbac:alertas.supervisar: la jefatura acusa las alertas que no tienen médico tratante. */
    public function acknowledgeAsServiceHead(Request $request, string $alert): JsonResponse
    {
        return $this->acknowledge($request, $alert, true);
    }

    /** Resuelve el actor y traduce las reglas y las fallas de la base a respuestas HTTP. */
    private function run(Request $request, \Closure $operation): JsonResponse
    {
        try {
            $actor = $this->actors->referencia((string) $request->user()->getAuthIdentifier());

            return $actor === null
                ? $this->error('ACTOR_WITHOUT_LOCAL_REFERENCE', 'El usuario no tiene una referencia local vigente en este hospital.', 403, $request)
                : $operation($actor);
        } catch (DomainRuleViolation $violation) {
            return $this->error($violation->rule, 'La alerta no cumple una regla clínica.', self::STATUS[$violation->rule] ?? 422, $request, $violation->field);
        } catch (PersistenceFailure|PDOException $failure) {
            // La base rechazó una referencia inexistente (FK) o un episodio de otro paciente (trigger): el evento es inválido, no es una caída.
            if (in_array((string) $failure->getPrevious()?->getCode(), ['23503', 'P0001'], true)) {
                return $this->error('EVENT_INVALID', 'El paciente, el episodio o la prueba no corresponden.', 422, $request, 'episode_id');
            }
            Log::warning('alertas_criticas.no_disponible', ['request_id' => $this->requestId($request), 'hospital_id' => $request->header('X-Tenant-ID')]);

            return $this->error('CRITICAL_ALERTS_UNAVAILABLE', 'La base de alertas no está disponible.', 503, $request)->header('Retry-After', '5');
        }
    }

    private function present(CriticalAlert $a): array
    {
        return ['critical_alert_id' => $a->id, 'lab_result_version_id' => $a->resultVersionId, 'local_patient_id' => $a->patientId,
            'care_episode_id' => $a->episodeId, 'lab_test_id' => $a->labTestId, 'measured_value' => $a->value, 'direction' => $a->direction,
            'recipient_user_ref_id' => $a->recipient === CriticalAlert::SERVICE_HEAD ? null : $a->recipient, 'escalation_level' => $a->level,
            'due_at' => $a->dueAt, 'status' => $a->status, 'acknowledged_by_user_ref_id' => $a->acknowledgedBy];
    }

    private function requestId(Request $request): string
    {
        $header = $request->header('X-Request-ID');

        return is_string($header) && preg_match('/^[A-Za-z0-9._-]{1,64}$/', $header) === 1 ? $header : 'req-'.bin2hex(random_bytes(8));
    }

    private function json(array $data, int $status, Request $request): JsonResponse
    {
        return response()->json(['data' => $data, 'meta' => ['request_id' => $this->requestId($request)]], $status)->header('Cache-Control', 'no-store, private');
    }

    private function error(string $code, string $message, int $status, Request $request, ?string $field = null): JsonResponse
    {
        return response()->json(['error' => array_filter(['code' => $code, 'message' => $message, 'field' => $field]), 'meta' => ['request_id' => $this->requestId($request)]], $status)
            ->header('Cache-Control', 'no-store, private');
    }
}
