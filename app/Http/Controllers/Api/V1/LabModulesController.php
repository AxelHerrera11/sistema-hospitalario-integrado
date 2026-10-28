<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Tenants\Infrastructure\Persistence\MembershipRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MicroHis\IngresoResultados\Application\{AuditLogger, CapturePendingResult, CaptureResultCommand, CorrectCapturedResult, CorrectionCommand};
use MicroHis\IngresoResultados\Persistence\PdoPendingResultRepository;
use MicroHis\ValidacionResultados\Application\{RejectReviewedResult, RejectResultCommand, ReviewAuditLogger, ValidateReviewedResult, ValidateResultCommand};
use MicroHis\ValidacionResultados\Persistence\PdoReviewResultRepository;
use MicroHis\NativePhpPrototype\Application\{PrepareLocalAction, PrepareLocalActionCommand};
use MicroHis\NativePhpPrototype\Persistence\{ArrayTelemetryRecorder, PdoLocalActionRepository};
use PDO;

final class LabModulesController extends Controller
{
    public function results(Request $request): JsonResponse
    { try { $pdo = $this->pdo($request); $this->seed($pdo, $this->tenant($request)); return response()->json(['data' => $this->row($pdo, 'lab_pending_results', $request)]); } catch (\Throwable $error) { return $this->failure($error, 'No fue posible consultar resultados.'); } }

    public function capture(Request $request, string $resultId): JsonResponse
    { $data = $request->validate(['value' => ['required', 'string', 'max:100'], 'unit' => ['required', 'string', 'max:30'], 'observation' => ['nullable', 'string', 'max:500']]); return $this->change($request, $resultId, function (PDO $pdo, $audit) use ($data, $request, $resultId): array { $result = (new CapturePendingResult(new PdoPendingResultRepository($pdo), $audit))->execute(new CaptureResultCommand($this->tenant($request), $resultId, $data['value'], $data['unit'], $data['observation'] ?? null, $this->user($request))); $this->copyToReview($pdo, $this->tenant($request), $resultId); return $result; }, 'Resultado capturado.', 'No fue posible capturar el resultado.'); }

    public function correct(Request $request, string $resultId): JsonResponse
    { $data = $request->validate(['value' => ['required', 'string', 'max:100'], 'unit' => ['required', 'string', 'max:30'], 'reason' => ['required', 'string', 'max:500']]); return $this->change($request, $resultId, function (PDO $pdo, $audit) use ($data, $request, $resultId): array { $result = (new CorrectCapturedResult(new PdoPendingResultRepository($pdo), $audit))->execute(new CorrectionCommand($this->tenant($request), $resultId, $data['value'], $data['unit'], $data['reason'], $this->user($request))); $this->copyToReview($pdo, $this->tenant($request), $resultId); return $result; }, 'Corrección guardada.', 'No fue posible corregir el resultado.'); }

    public function reviews(Request $request): JsonResponse
    { try { $pdo = $this->pdo($request); $this->seed($pdo, $this->tenant($request)); $this->copyToReview($pdo, $this->tenant($request), $this->resultId()); return response()->json(['data' => $this->row($pdo, 'lab_results_for_review', $request)]); } catch (\Throwable $error) { return $this->failure($error, 'No fue posible consultar validaciones.'); } }

    public function validateResult(Request $request, string $resultId): JsonResponse
    { return $this->reviewChange($request, $resultId, fn ($repo, $audit) => (new ValidateReviewedResult($repo, $audit))->execute(new ValidateResultCommand($this->tenant($request), $resultId, $this->user($request), $this->role($request))), 'Resultado validado.'); }

    public function reject(Request $request, string $resultId): JsonResponse
    { $data = $request->validate(['reason' => ['required', 'string', 'max:500']]); return $this->reviewChange($request, $resultId, fn ($repo, $audit) => (new RejectReviewedResult($repo, $audit))->execute(new RejectResultCommand($this->tenant($request), $resultId, $data['reason'], $this->user($request), $this->role($request))), 'Resultado rechazado.'); }

    public function nativeActions(Request $request): JsonResponse
    { try { $repo = new PdoLocalActionRepository($this->pdo($request)); return response()->json(['data' => array_map(fn ($a) => ['id' => $a->id, 'kind' => $a->kind, 'status' => $a->status->value, 'payload' => $a->payload], $repo->pendingForTenant($this->tenant($request)))]); } catch (\Throwable $error) { return $this->failure($error, 'No fue posible consultar acciones locales.'); } }

    public function prepareAction(Request $request): JsonResponse
    { $data = $request->validate(['kind' => ['required', 'string', 'max:100'], 'payload' => ['required', 'json', 'max:4000']]); try { $result = (new PrepareLocalAction(new PdoLocalActionRepository($this->pdo($request)), new ArrayTelemetryRecorder()))->execute(new PrepareLocalActionCommand($this->tenant($request), $this->user($request), $data['kind'], $data['payload'])); return response()->json(['message' => 'Acción local preparada.', 'data' => $result]); } catch (\Throwable $error) { return $this->failure($error, 'No fue posible preparar la acción.'); } }

    public function syncActions(Request $request): JsonResponse
    { $request->validate(['connectivity' => ['required', 'in:online,offline']]); try { $count = count((new PdoLocalActionRepository($this->pdo($request)))->pendingForTenant($this->tenant($request))); return response()->json(['message' => 'Sincronización simulada: no se modificó el estado remoto.', 'data' => ['status' => 'simulado', 'pending' => $count]]); } catch (\Throwable $error) { return $this->failure($error, 'No fue posible preparar la sincronización.'); } }

    private function change(Request $request, string $id, callable $operation, string $message, string $fallback): JsonResponse
    { try { $pdo = $this->pdo($request); $this->seed($pdo, $this->tenant($request)); $pdo->beginTransaction(); $result = $operation($pdo, $this->audit($pdo, $request)); $pdo->commit(); return response()->json(['message' => $message, 'data' => $result]); } catch (\Throwable $error) { if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack(); return $this->failure($error, $fallback); } }

    private function reviewChange(Request $request, string $id, callable $operation, string $message): JsonResponse
    { try { $pdo = $this->pdo($request); $this->seed($pdo, $this->tenant($request)); $this->copyToReview($pdo, $this->tenant($request), $id); $pdo->beginTransaction(); $result = $operation(new PdoReviewResultRepository($pdo), $this->audit($pdo, $request)); $pdo->commit(); return response()->json(['message' => $message, 'data' => $result]); } catch (\Throwable $error) { if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack(); return $this->failure($error, 'No fue posible aplicar la decisión.'); } }

    private function pdo(Request $request): PDO { $this->user($request); return DB::connection('hospital')->getPdo(); }
    private function tenant(Request $request): string { return (string) $request->header('X-Tenant-ID'); }
    public function user(Request $request): string { $claims = $request->attributes->get('auth_claims', []); $user = $claims['sub'] ?? $request->user('api')?->getAuthIdentifier(); if (!$user) throw new AuthenticationException('Identidad requerida.'); return (string) $user; }
    private function role(Request $request): string { $roles = app(MembershipRepository::class)->activeRoles($this->user($request), $this->tenant($request)); return in_array('bioquimico', $roles, true) ? 'bioquimico' : ''; }
    private function resultId(): string { return '00000000-0000-0000-0000-000000000019'; }

    private function seed(PDO $pdo, string $tenant): void
    { $id = $this->resultId(); $pdo->prepare('insert into lab_pending_results (id,tenant_id,patient_code,test_code,expected_unit,status,version) values (?,?,?,?,?,?,1) on conflict (id,tenant_id) do nothing')->execute([$id, $tenant, 'PAC-001', 'GLU', 'mg/dL', 'pendiente']); $pdo->prepare('insert into lab_results_for_review (id,tenant_id,patient_code,test_code,value,unit,expected_unit,status,version) values (?,?,?,?,?,?,?,?,1) on conflict (id,tenant_id) do nothing')->execute([$id, $tenant, 'PAC-001', 'GLU', '95', 'mg/dL', 'mg/dL', 'pendiente_revision']); }
    private function copyToReview(PDO $pdo, string $tenant, string $id): void
    { $pdo->prepare("update lab_results_for_review r set value=p.value, unit=p.unit, status=case when p.status in ('capturado','corregido') then 'pendiente_revision' else r.status end, version=p.version from lab_pending_results p where r.id=p.id and r.tenant_id=p.tenant_id and r.id=? and r.tenant_id=?")->execute([$id, $tenant]); }
    private function row(PDO $pdo, string $table, Request $request): array
    { $s = $pdo->prepare("select * from {$table} where tenant_id=? order by id limit 1"); $s->execute([$this->tenant($request)]); $row = $s->fetch(PDO::FETCH_ASSOC) ?: []; $row['audit'] = $this->auditRows($pdo, $row['id'] ?? null); return $row; }
    private function auditRows(PDO $pdo, ?string $id): array
    { if (!$id) return []; $s = $pdo->prepare('select action, decision, metadata, occurred_at from local_access_audits where resource_id=? order by occurred_at'); $s->execute([$id]); return array_map(fn ($r) => ['action' => $r['action'], 'decision' => $r['decision'], 'metadata' => json_decode($r['metadata'], true), 'occurred_at' => $r['occurred_at']], $s->fetchAll(PDO::FETCH_ASSOC)); }
    private function audit(PDO $pdo, Request $request): AuditLogger&ReviewAuditLogger
    { return new class($pdo, $request, $this) implements AuditLogger, ReviewAuditLogger { public function __construct(private PDO $pdo, private Request $request, private LabModulesController $controller) {} public function record(array $event): void { $this->pdo->prepare('insert into local_access_audits (correlation_id,actor_user_ref_id,module,action,resource_type,resource_id,metadata) values (?,?,?,?,?,?,?)')->execute([Str::uuid()->toString(), $this->controller->user($this->request), 'laboratorio', (string) $event['action'], 'lab_result', (string) $event['result_id'], json_encode(['version' => $event['version']])]); } }; }
    private function failure(\Throwable $error, string $fallback): JsonResponse
    { $status = $error instanceof AuthenticationException ? 401 : (str_contains($error::class, 'NotFound') ? 404 : (str_contains($error::class, 'DomainRuleViolation') ? 422 : 500)); return response()->json(['error' => $status >= 500 ? $fallback : ($error->getMessage() ?: $fallback)], $status); }
}
