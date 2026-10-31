<?php

declare(strict_types=1);

namespace App\ExpedienteMedico;

use App\Http\Controllers\Controller;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MicroHis\ExpedienteMedico\Domain\PatientSummary;
use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\Gobernanza\Domain\AuditCriticality;
use MicroHis\Gobernanza\Domain\AuditOutcome;
use MicroHis\Gobernanza\Domain\AuditStorageFailure;
use MicroHis\Gobernanza\Domain\InvalidAuditEvent;
use PDOException;

/**
 * Médicos tratantes de los episodios abiertos de un paciente (expediente.asignar). tenant, jwt.auth y el permiso
 * los aplican los middlewares; aquí se resuelve el actor local, se delega en CareAssignments y se audita cada cambio
 * en Gobernanza («expediente_medico.medico_asignado» / «expediente_medico.asignacion_finalizada»).
 */
final class CareAssignmentController extends Controller
{
    public function __construct(
        private readonly CareAssignments $assignments,
        private readonly ResolutorActorLocal $actors,
        private readonly RecordAuditEvent $audit,
    ) {}

    public function index(string $patientCode): JsonResponse
    {
        return $this->guard($patientCode, fn (): JsonResponse => ($overview = $this->assignments->overview($patientCode)) === null
            ? $this->json(['code' => 'PACIENTE_NO_ENCONTRADO'], 404)
            : $this->json($overview));
    }

    public function store(Request $request, string $patientCode): JsonResponse
    {
        $data = $request->validate([
            'care_episode_id' => ['required', 'string', 'max:64'],
            'doctor_profile_id' => ['required', 'string', 'max:64'],
            'specialty_id' => ['required', 'string', 'max:64'],
            'reason' => ['required', 'string', 'min:3', 'max:200'],
        ]);

        return $this->guard($patientCode, function () use ($request, $patientCode, $data): JsonResponse {
            $actor = $this->actorRef($request);
            if ($actor === null) {
                return $this->json(['code' => 'ACTOR_SIN_REFERENCIA_LOCAL'], 403);
            }
            $created = $this->assignments->assign(
                $patientCode, $data['care_episode_id'], $data['doctor_profile_id'], $data['specialty_id'], trim($data['reason']), $actor,
                fn (string $id) => $this->record($request, 'medico_asignado', $id, [
                    'care_episode_id' => $data['care_episode_id'],
                    'doctor_profile_id' => $data['doctor_profile_id'],
                ]),
            );

            return $this->json($created, 201);
        });
    }

    public function destroy(Request $request, string $patientCode, string $assignment): JsonResponse
    {
        return $this->guard($patientCode, function () use ($request, $patientCode, $assignment): JsonResponse {
            if ($this->actorRef($request) === null) {
                return $this->json(['code' => 'ACTOR_SIN_REFERENCIA_LOCAL'], 403);
            }
            $this->assignments->end($patientCode, $assignment, fn () => $this->record($request, 'asignacion_finalizada', $assignment));

            return $this->json(['care_assignment_id' => $assignment, 'status' => 'closed']);
        });
    }

    private function guard(string $patientCode, callable $action): JsonResponse
    {
        if (preg_match(PatientSummary::CODE, $patientCode) !== 1) {
            return $this->json(['code' => 'CODIGO_PACIENTE_INVALIDO'], 422);
        }
        try {
            return $action();
        } catch (CareAssignmentFailure $failure) {
            return $this->json(['code' => $failure->errorCode], $failure->status);
        } catch (AuditStorageFailure|InvalidAuditEvent) {
            // El cambio ya se revirtió en CareAssignments: sin bitácora no hay asignación.
            return $this->json(['code' => 'AUDITORIA_NO_DISPONIBLE'], 503);
        } catch (PDOException|QueryException) {
            return $this->json(['code' => 'HOSPITAL_NO_DISPONIBLE'], 503);
        }
    }

    private function actorRef(Request $request): ?string
    {
        return $this->actors->referencia((string) $request->user('api')?->getAuthIdentifier());
    }

    private function record(Request $request, string $action, string $assignmentId, array $context = []): void
    {
        $this->audit->record(
            (string) $request->user('api')?->getAuthIdentifier(),
            ExpedienteMedicoController::hospitalOf($request),
            'expediente_medico',
            $action,
            AuditOutcome::Success,
            AuditCriticality::Security,
            'care_assignment',
            $assignmentId,
            $request->header('X-Request-Id') ?: null,
            $action === 'medico_asignado' ? 'care_assignment_created' : 'care_assignment_closed',
            $context,
        );
    }

    private function json(array $body, int $status = 200): JsonResponse
    {
        return response()->json($body, $status)->withHeaders(['Cache-Control' => 'no-store, private']);
    }
}
