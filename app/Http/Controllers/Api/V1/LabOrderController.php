<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** API integrada de órdenes: el estado y el historial viven en HOSPITAL, no en sesión. */
final class LabOrderController extends Controller
{
    private const TRANSITIONS = [
        'REQUESTED' => 'COLLECTED', 'COLLECTED' => 'IN_PROCESS', 'IN_PROCESS' => 'RESULTED', 'RESULTED' => 'VALIDATED',
    ];

    public function __construct(private readonly ResolutorActorLocal $actors) {}

    public function index(Request $request): JsonResponse
    {
        [$tenant] = $this->context($request);
        $query = DB::connection('hospital')->table('lab_orders')->where('tenant_id', $tenant)->orderByDesc('created_at');
        if (is_string($request->query('patient_id'))) $query->where('local_patient_id', $request->query('patient_id'));
        if (is_string($request->query('status'))) $query->where('status', $request->query('status'));
        $orders = $query->limit(50)->get()->map(fn ($row): array => $this->order($row))->values();
        return $this->json(['lab_orders' => $orders], 200, $this->requestId($request));
    }

    public function store(Request $request): JsonResponse
    {
        [$tenant, $actor] = $this->context($request);
        $data = $request->validate([
            'local_patient_id' => ['required', 'uuid'], 'care_episode_id' => ['required', 'uuid'],
            'clinical_note_id' => ['nullable', 'uuid'], 'priority' => ['nullable', 'in:ROUTINE,URGENT,STAT'],
            'clinical_context' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.lab_test_id' => ['required', 'uuid'],
        ]);
        $idempotencyKey = trim((string) $request->header('Idempotency-Key'));
        if ($idempotencyKey === '') return $this->error('IDEMPOTENCY_KEY_REQUIRED', 400, $this->requestId($request));
        $testIds = array_map(static fn (array $item): string => $item['lab_test_id'], $data['items']);
        if (count($testIds) !== count(array_unique($testIds))) return $this->error('DUPLICATE_LAB_TEST', 422, $this->requestId($request));
        $data['priority'] = strtoupper((string) ($data['priority'] ?? 'ROUTINE'));
        $data['idempotency_key'] = $idempotencyKey;
        $data['request_hash'] = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
        $db = DB::connection('hospital');
        return $db->transaction(function () use ($db, $data, $tenant, $actor, $request): JsonResponse {
            $existing = $db->table('lab_orders')->where('tenant_id', $tenant)->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                if (!hash_equals((string) $existing->idempotency_request_hash, $data['request_hash'])) return $this->error('IDEMPOTENCY_CONFLICT', 409, $this->requestId($request));
                return $this->json(['lab_order' => $this->order($existing)], 200, $this->requestId($request));
            }
            $episodeOpen = $db->table('care_episodes')->where('care_episode_id', $data['care_episode_id'])
                ->where('local_patient_id', $data['local_patient_id'])->where('status', 'open')->exists();
            if (!$episodeOpen) return $this->error('EPISODE_NOT_OPEN', 422, $this->requestId($request));
            $tests = [];
            foreach ($data['items'] as $item) {
                $test = $db->table('lab_tests')->where('lab_test_id', $item['lab_test_id'])->where('status', 'active')->first();
                if (!$test) return $this->error('LAB_TEST_NOT_FOUND', 422, $this->requestId($request));
                $tests[] = $test;
            }
            $id = (string) Str::uuid();
            $correlation = (string) ($request->header('X-Correlation-ID') ?: Str::uuid());
            $db->table('lab_orders')->insert([
                'lab_order_id' => $id, 'tenant_id' => $tenant, 'local_patient_id' => $data['local_patient_id'],
                'care_episode_id' => $data['care_episode_id'], 'clinical_note_id' => $data['clinical_note_id'] ?? null,
                'ordered_by_user_ref_id' => $actor, 'priority' => $data['priority'], 'clinical_context' => $data['clinical_context'] ?? null,
                'status' => 'REQUESTED', 'idempotency_key' => $data['idempotency_key'], 'idempotency_request_hash' => $data['request_hash'], 'correlation_id' => $correlation,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($tests as $test) $db->table('lab_order_items')->insert([
                'lab_order_item_id' => (string) Str::uuid(), 'lab_order_id' => $id, 'lab_test_id' => $test->lab_test_id,
                'test_code' => $test->code, 'test_name' => $test->name, 'created_at' => now(),
            ]);
            $db->table('lab_order_events')->insert([
                'lab_order_id' => $id, 'event_seq' => 1, 'from_status' => null, 'to_status' => 'REQUESTED',
                'actor_user_ref_id' => $actor, 'occurred_at' => now(), 'correlation_id' => $correlation,
            ]);
            return $this->json(['lab_order' => $this->showOrder($db, $tenant, $id)], 201, $this->requestId($request));
        });
    }

    public function show(Request $request, string $id): JsonResponse
    {
        [$tenant] = $this->context($request);
        $order = $this->showOrder(DB::connection('hospital'), $tenant, $id);
        return $order === null ? $this->error('LAB_ORDER_NOT_FOUND', 404, $this->requestId($request)) : $this->json(['lab_order' => $order], 200, $this->requestId($request));
    }

    public function advance(Request $request, string $id): JsonResponse
    {
        [$tenant, $actor] = $this->context($request);
        $data = $request->validate(['status' => ['required', 'in:COLLECTED,IN_PROCESS,RESULTED,VALIDATED']]);
        $db = DB::connection('hospital');
        return $db->transaction(function () use ($db, $tenant, $actor, $id, $data, $request): JsonResponse {
            $order = $db->table('lab_orders')->where('tenant_id', $tenant)->where('lab_order_id', $id)->lockForUpdate()->first();
            if (!$order) return $this->error('LAB_ORDER_NOT_FOUND', 404, $this->requestId($request));
            if ((self::TRANSITIONS[$order->status] ?? null) !== $data['status']) return $this->error('LAB_ORDER_TRANSITION_INVALID', 409, $this->requestId($request));
            $db->table('lab_orders')->where('lab_order_id', $id)->update(['status' => $data['status'], 'updated_at' => now()]);
            $seq = ((int) $db->table('lab_order_events')->where('lab_order_id', $id)->max('event_seq')) + 1;
            $db->table('lab_order_events')->insert(['lab_order_id' => $id, 'event_seq' => $seq, 'from_status' => $order->status, 'to_status' => $data['status'], 'actor_user_ref_id' => $actor, 'occurred_at' => now(), 'correlation_id' => $order->correlation_id]);
            return $this->json(['lab_order' => $this->showOrder($db, $tenant, $id)], 200, $this->requestId($request));
        });
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        [$tenant, $actor] = $this->context($request);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:250']]);
        if (trim((string) $request->header('Idempotency-Key')) === '') return $this->error('IDEMPOTENCY_KEY_REQUIRED', 400, $this->requestId($request));
        $db = DB::connection('hospital');
        return $db->transaction(function () use ($db, $tenant, $actor, $id, $data, $request): JsonResponse {
            $order = $db->table('lab_orders')->where('tenant_id', $tenant)->where('lab_order_id', $id)->lockForUpdate()->first();
            if (!$order) return $this->error('LAB_ORDER_NOT_FOUND', 404, $this->requestId($request));
            if (in_array($order->status, ['VALIDATED', 'CANCELLED'], true)) return $this->error('LAB_ORDER_CANCELLATION_INVALID', 409, $this->requestId($request));
            $db->table('lab_orders')->where('lab_order_id', $id)->update(['status' => 'CANCELLED', 'updated_at' => now()]);
            $seq = ((int) $db->table('lab_order_events')->where('lab_order_id', $id)->max('event_seq')) + 1;
            $db->table('lab_order_events')->insert(['lab_order_id' => $id, 'event_seq' => $seq, 'from_status' => $order->status, 'to_status' => 'CANCELLED', 'actor_user_ref_id' => $actor, 'occurred_at' => now(), 'correlation_id' => $order->correlation_id, 'reason' => $data['reason']]);
            return $this->json(['lab_order' => $this->showOrder($db, $tenant, $id)], 200, $this->requestId($request));
        });
    }

    private function context(Request $request): array
    {
        $tenant = (string) $request->attributes->get('tenant')?->getKey();
        $central = (string) $request->user('api')?->getAuthIdentifier();
        $actor = $this->actors->referencia($central);
        abort_unless($tenant !== '' && $actor !== null, 403, 'Actor clínico no sincronizado');
        return [$tenant, $actor];
    }

    private function showOrder($db, string $tenant, string $id): ?array
    {
        $row = $db->table('lab_orders')->where('tenant_id', $tenant)->where('lab_order_id', $id)->first();
        if (!$row) return null;
        return $this->order($row) + ['items' => $db->table('lab_order_items')->where('lab_order_id', $id)->get()->all(), 'events' => $db->table('lab_order_events')->where('lab_order_id', $id)->orderBy('event_seq')->get()->all()];
    }

    private function order(object $row): array { return (array) $row; }
    private function requestId(Request $request): string { return (string) ($request->header('X-Request-ID') ?: 'req-'.bin2hex(random_bytes(8))); }
    private function json(array $data, int $status, string $requestId): JsonResponse { return response()->json(['data' => $data, 'meta' => ['request_id' => $requestId, 'correlation_id' => $data['lab_order']['correlation_id'] ?? null]], $status)->header('Cache-Control', 'no-store, private'); }
    private function error(string $code, int $status, string $requestId): JsonResponse { return response()->json(['error' => ['code' => $code], 'meta' => ['request_id' => $requestId]], $status)->header('Cache-Control', 'no-store, private'); }
}
