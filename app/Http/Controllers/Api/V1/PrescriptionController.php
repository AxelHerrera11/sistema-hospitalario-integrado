<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Prescriptions\StorePrescriptionRequest;
use App\Prescripciones\PdoPrescriptionContext;
use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MicroHis\Prescripciones\Application\Exception\AllergyCheckUnavailable;
use MicroHis\Prescripciones\Application\Exception\AuditUnavailable;
use MicroHis\Prescripciones\Application\Exception\MedicationNotFound;
use MicroHis\Prescripciones\Application\Exception\PersistenceFailure;
use MicroHis\Prescripciones\Application\Exception\PrescriptionNotFound;
use MicroHis\Prescripciones\Application\PrescriptionRecord;
use MicroHis\Prescripciones\Application\PrescriptionService;
use MicroHis\Prescripciones\Domain\AllergyConflictBlocked;
use MicroHis\Prescripciones\Domain\NotTreatingPhysician;
use MicroHis\Prescripciones\Domain\PrescriptionNotDraft;
use MicroHis\Prescripciones\Domain\PrescriptionRuleViolation;

final class PrescriptionController extends Controller
{
    public function __construct(
        private readonly Container $container,
        private readonly PdoPrescriptionContext $context,
    ) {}

    public function store(StorePrescriptionRequest $request, string $patient): JsonResponse
    {
        return $this->respond(function () use ($request, $patient): JsonResponse {
            if (! $this->context->patientExists($patient)) {
                return $this->problem(404, 'PATIENT_NOT_FOUND', 'Paciente no encontrado.');
            }

            $doctorProfileId = $this->doctorProfileId($request);
            $careEpisodeId = $this->context->openCareEpisodeForPatient($patient);
            if ($careEpisodeId === null) {
                return $this->problem(422, 'CARE_EPISODE_NOT_OPEN', 'El paciente no tiene un episodio abierto.');
            }

            $data = $request->validated();
            $record = $this->prescriptions()->createDraft([
                'patient_id' => $patient,
                'doctor_profile_id' => $doctorProfileId,
                'care_episode_id' => $careEpisodeId,
                'medication_id' => $data['medication_id'],
                'dose_amount' => (string) $data['dosage_value'],
                'dose_unit' => $data['dosage_unit'],
                'route' => $data['route'],
                'frequency_times' => $data['frequency_times'],
                'frequency_interval' => $data['frequency_interval'] ?? null,
                'duration_value' => $data['duration_value'],
                'duration_unit' => $data['duration_unit'],
            ]);

            return response()->json($this->view($record), 201)->header('Cache-Control', 'no-store, private');
        });
    }

    public function index(string $patient): JsonResponse
    {
        return $this->respond(function () use ($patient): JsonResponse {
            if (! $this->context->patientExists($patient)) {
                return $this->problem(404, 'PATIENT_NOT_FOUND', 'Paciente no encontrado.');
            }

            return response()->json(array_map($this->view(...), $this->prescriptions()->forPatient($patient)))
                ->header('Cache-Control', 'no-store, private');
        });
    }

    public function show(string $id): JsonResponse
    {
        return $this->respond(function () use ($id): JsonResponse {
            return response()->json($this->view($this->prescriptions()->get($id)))
                ->header('Cache-Control', 'no-store, private');
        });
    }

    public function sign(Request $request, string $id): JsonResponse
    {
        return $this->respond(function () use ($request, $id): JsonResponse {
            $doctorProfileId = $this->doctorProfileId($request);

            return response()->json($this->view($this->prescriptions()->sign($id, $doctorProfileId)))
                ->header('Cache-Control', 'no-store, private');
        });
    }

    private function prescriptions(): PrescriptionService
    {
        return $this->container->make(PrescriptionService::class);
    }

    private function doctorProfileId(Request $request): string
    {
        $userId = $request->user('api')?->getAuthIdentifier();

        return (is_string($userId) ? $this->context->doctorProfileForUser($userId) : null)
            ?? abort(403, 'Se requiere un perfil médico activo.');
    }

    private function view(PrescriptionRecord $record): array
    {
        $prescription = $record->prescription;

        return [
            'id' => $prescription->id,
            'medication_id' => $prescription->medication->id,
            'dosage_value' => (float) $record->doseAmount,
            'dosage_unit' => $record->doseUnit,
            'route' => $prescription->route->value,
            'status' => $prescription->status->value,
            'warnings' => $record->warnings,
        ];
    }

    private function allergyConflict(AllergyConflictBlocked $conflict): JsonResponse
    {
        $conflicts = $conflict->conflicts;
        $priority = ['mild' => 0, 'unknown' => 1, 'moderate' => 2, 'severe' => 3, 'life_threatening' => 4];
        usort($conflicts, static fn ($left, $right): int => $priority[$right->severity->value] <=> $priority[$left->severity->value]);
        $severity = $conflicts[0]->severity->value ?? throw new \LogicException('El conflicto de alergia no contiene severidad.');

        return response()->json([
            'code' => 'ALLERGY_CONFLICT',
            'severity' => $severity,
            // Excepción fuera de esta versión; el permiso sigue pendiente.
            'exception_allowed' => false,
        ], 422)->header('Cache-Control', 'no-store, private');
    }

    private function respond(Closure $operation): JsonResponse
    {
        try {
            return $operation();
        } catch (AllergyConflictBlocked $conflict) {
            return $this->allergyConflict($conflict);
        } catch (MedicationNotFound $error) {
            return $this->problem(422, 'MEDICATION_INACTIVE', $error->getMessage());
        } catch (PrescriptionRuleViolation $error) {
            return $this->problem(422, 'VALIDATION_FAILED', $error->getMessage(), $error->field);
        } catch (PrescriptionNotFound) {
            return $this->problem(404, 'PRESCRIPTION_NOT_FOUND', 'Prescripción no encontrada.');
        } catch (NotTreatingPhysician) {
            return $this->problem(403, 'TREATING_PHYSICIAN_REQUIRED', 'Solo puede firmar el médico tratante.');
        } catch (PrescriptionNotDraft) {
            return $this->problem(409, 'PRESCRIPTION_NOT_DRAFT', 'La prescripción ya fue firmada.');
        } catch (AllergyCheckUnavailable|AuditUnavailable|PersistenceFailure) {
            return $this->problem(503, 'PRESCRIPTION_DEPENDENCY_UNAVAILABLE', 'No se pudo completar la operación.');
        }
    }

    private function problem(int $status, string $code, string $title, ?string $field = null): JsonResponse
    {
        return response()->json([
            'title' => $title,
            'status' => $status,
            'code' => $code,
            'field' => $field,
        ], $status)->header('Content-Type', 'application/problem+json')
            ->header('Cache-Control', 'no-store, private');
    }
}
