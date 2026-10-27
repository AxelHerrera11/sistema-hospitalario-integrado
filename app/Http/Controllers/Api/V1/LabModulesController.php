<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Tenants\Infrastructure\Persistence\MembershipRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MicroHis\IngresoResultados\Application\{CapturePendingResult, CaptureResultCommand, CorrectionCommand, CorrectCapturedResult};
use MicroHis\IngresoResultados\Domain\{PendingLabResult, ResultStatus};
use MicroHis\IngresoResultados\Persistence\{ArrayAuditLogger, InMemoryPendingResultRepository};
use MicroHis\ValidacionResultados\Application\{RejectReviewedResult, RejectResultCommand, ValidateReviewedResult, ValidateResultCommand};
use MicroHis\ValidacionResultados\Domain\{ReviewStatus, ReviewableLabResult};
use MicroHis\ValidacionResultados\Persistence\{ArrayReviewAuditLogger, InMemoryReviewResultRepository};
use MicroHis\NativePhpPrototype\Application\{PrepareLocalAction, PrepareLocalActionCommand, SyncLocalActions, SyncLocalActionsCommand};
use MicroHis\NativePhpPrototype\Domain\{ConnectivityStatus, LocalHisAction};
use MicroHis\NativePhpPrototype\Persistence\{ArrayTelemetryRecorder, FakeSyncGateway, InMemoryLocalActionRepository};

final class LabModulesController extends Controller
{
    public function results(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->labState($request)]);
    }

    public function capture(Request $request, string $resultId): JsonResponse
    {
        $data = $request->validate(['value' => ['required', 'string', 'max:100'], 'unit' => ['required', 'string', 'max:30'], 'observation' => ['nullable', 'string', 'max:500']]);
        $state = $this->labState($request);
        $repo = new InMemoryPendingResultRepository([$this->pending($state['result'])]);
        $audit = new ArrayAuditLogger();

        try {
            $result = (new CapturePendingResult($repo, $audit))->execute(new CaptureResultCommand(
                $this->tenant($request), $resultId, $data['value'], $data['unit'], $data['observation'] ?? null, $this->user($request),
            ));
            $this->saveLab($request, $repo->getById($this->tenant($request), $resultId), $audit->events);
            return response()->json(['message' => 'Resultado capturado.', 'data' => $result]);
        } catch (\Throwable $error) {
            return $this->failure($error, 'No fue posible capturar el resultado.');
        }
    }

    public function correct(Request $request, string $resultId): JsonResponse
    {
        $data = $request->validate(['value' => ['required', 'string', 'max:100'], 'unit' => ['required', 'string', 'max:30'], 'reason' => ['required', 'string', 'max:500']]);
        $state = $this->labState($request);
        $repo = new InMemoryPendingResultRepository([$this->pending($state['result'])]);
        $audit = new ArrayAuditLogger();

        try {
            $result = (new CorrectCapturedResult($repo, $audit))->execute(new CorrectionCommand(
                $this->tenant($request), $resultId, $data['value'], $data['unit'], $data['reason'], $this->user($request),
            ));
            $this->saveLab($request, $repo->getById($this->tenant($request), $resultId), $audit->events);
            return response()->json(['message' => 'Corrección guardada.', 'data' => $result]);
        } catch (\Throwable $error) {
            return $this->failure($error, 'No fue posible corregir el resultado.');
        }
    }

    public function reviews(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->reviewState($request)]);
    }

    public function validateResult(Request $request, string $resultId): JsonResponse
    {
        $state = $this->reviewState($request);
        $repo = new InMemoryReviewResultRepository([$this->review($state['result'])]);
        $audit = new ArrayReviewAuditLogger();

        try {
            $result = (new ValidateReviewedResult($repo, $audit))->execute(new ValidateResultCommand(
                $this->tenant($request), $resultId, $this->user($request), $this->role($request),
            ));
            $this->saveReview($request, $repo->getPending($this->tenant($request), $resultId), $audit->events);
            return response()->json(['message' => 'Resultado validado.', 'data' => $result]);
        } catch (\Throwable $error) {
            return $this->failure($error, 'No fue posible validar el resultado.');
        }
    }

    public function reject(Request $request, string $resultId): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $state = $this->reviewState($request);
        $repo = new InMemoryReviewResultRepository([$this->review($state['result'])]);
        $audit = new ArrayReviewAuditLogger();

        try {
            $result = (new RejectReviewedResult($repo, $audit))->execute(new RejectResultCommand(
                $this->tenant($request), $resultId, $data['reason'], $this->user($request), $this->role($request),
            ));
            $this->saveReview($request, $repo->getPending($this->tenant($request), $resultId), $audit->events);
            return response()->json(['message' => 'Resultado rechazado.', 'data' => $result]);
        } catch (\Throwable $error) {
            return $this->failure($error, 'No fue posible rechazar el resultado.');
        }
    }

    public function nativeActions(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->session()->get('native.actions', [])]);
    }

    public function prepareAction(Request $request): JsonResponse
    {
        $data = $request->validate(['kind' => ['required', 'string', 'max:100'], 'payload' => ['required', 'json', 'max:4000']]);
        $repo = new InMemoryLocalActionRepository();
        $result = (new PrepareLocalAction($repo, new ArrayTelemetryRecorder()))->execute(new PrepareLocalActionCommand(
            $this->tenant($request), $this->user($request), $data['kind'], $data['payload'],
        ));
        $actions = $request->session()->get('native.actions', []);
        $actions[] = ['id' => $result['id'], 'tenant_id' => $this->tenant($request), 'actor_id' => $this->user($request), 'kind' => $data['kind'], 'payload' => $data['payload'], 'status' => $result['status']];
        $request->session()->put('native.actions', $actions);
        return response()->json(['message' => 'Acción local preparada.', 'data' => $result]);
    }

    public function syncActions(Request $request): JsonResponse
    {
        $data = $request->validate(['connectivity' => ['required', 'in:online,offline']]);
        $actions = $request->session()->get('native.actions', []);
        $items = array_map(fn (array $item): LocalHisAction => new LocalHisAction($item['id'], $item['tenant_id'], $item['actor_id'], $item['kind'], $item['payload']), array_filter($actions, fn (array $item): bool => $item['status'] === 'pendiente_sincronizar'));

        try {
            $result = (new SyncLocalActions(new InMemoryLocalActionRepository($items), new FakeSyncGateway(), new ArrayTelemetryRecorder()))
                ->execute(new SyncLocalActionsCommand($this->tenant($request), ConnectivityStatus::from($data['connectivity'])));
            foreach ($actions as &$item) {
                if ($item['tenant_id'] === $this->tenant($request) && $item['status'] === 'pendiente_sincronizar') $item['status'] = 'sincronizado';
            }
            unset($item);
            $request->session()->put('native.actions', $actions);
            return response()->json(['message' => 'Sincronización completada.', 'data' => $result]);
        } catch (\Throwable $error) {
            return $this->failure($error, 'No fue posible sincronizar.');
        }
    }

    private function labState(Request $request): array
    {
        return $request->session()->get('lab.state', ['result' => ['id' => 'res-001', 'tenant_id' => $this->tenant($request), 'patient_code' => 'PAC-001', 'test_code' => 'GLU', 'expected_unit' => 'mg/dL', 'status' => 'pendiente', 'value' => null, 'unit' => null, 'observation' => null, 'version' => 1], 'audit' => []]);
    }

    private function reviewState(Request $request): array
    {
        return $request->session()->get('review.state', ['result' => ['id' => 'rev-001', 'tenant_id' => $this->tenant($request), 'patient_code' => 'PAC-001', 'test_code' => 'GLU', 'value' => '95', 'unit' => 'mg/dL', 'expected_unit' => 'mg/dL', 'status' => 'pendiente_revision', 'decision_reason' => null, 'version' => 1], 'audit' => []]);
    }

    private function pending(array $data): PendingLabResult
    {
        return new PendingLabResult($data['id'], $data['tenant_id'], $data['patient_code'], $data['test_code'], $data['expected_unit'], ResultStatus::from($data['status']), $data['value'], $data['unit'], $data['observation'], $data['version']);
    }

    private function review(array $data): ReviewableLabResult
    {
        return new ReviewableLabResult($data['id'], $data['tenant_id'], $data['patient_code'], $data['test_code'], $data['value'], $data['unit'], $data['expected_unit'], ReviewStatus::from($data['status']), $data['decision_reason'], $data['version']);
    }

    private function saveLab(Request $request, PendingLabResult $result, array $events): void
    {
        $state = $this->labState($request);
        $state['result'] = ['id' => $result->id, 'tenant_id' => $result->tenantId, 'patient_code' => $result->patientCode, 'test_code' => $result->testCode, 'expected_unit' => $result->expectedUnit, 'status' => $result->status->value, 'value' => $result->value, 'unit' => $result->unit, 'observation' => $result->observation, 'version' => $result->version];
        $state['audit'] = array_merge($state['audit'], $events);
        $request->session()->put('lab.state', $state);
    }

    private function saveReview(Request $request, ReviewableLabResult $result, array $events): void
    {
        $state = $this->reviewState($request);
        $state['result'] = ['id' => $result->id, 'tenant_id' => $result->tenantId, 'patient_code' => $result->patientCode, 'test_code' => $result->testCode, 'value' => $result->value, 'unit' => $result->unit, 'expected_unit' => $result->expectedUnit, 'status' => $result->status->value, 'decision_reason' => $result->decisionReason, 'version' => $result->version];
        $state['audit'] = array_merge($state['audit'], $events);
        $request->session()->put('review.state', $state);
    }

    private function tenant(Request $request): string { return (string) $request->header('X-Tenant-ID'); }
    private function user(Request $request): string
    {
        $claims = $request->attributes->get('auth_claims', []);

        return (string) ($claims['sub'] ?? $request->user('api')?->getAuthIdentifier() ?? 'local-demo-user');
    }
    private function role(Request $request): string
    {
        $roles = app(MembershipRepository::class)->activeRoles($this->user($request), $this->tenant($request));

        return in_array('bioquimico', $roles, true) ? 'bioquimico' : '';
    }
    private function failure(\Throwable $error, string $fallback): JsonResponse { return response()->json(['error' => $error->getMessage() ?: $fallback], 422); }
}
