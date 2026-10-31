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
        'issued' => 'collected', 'collected' => 'in_process', 'in_process' => 'resulted', 'resulted' => 'validated',
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
            'clinical_note_id' => ['nullable', 'uuid'], 'priority' => ['required', 'in:routine,urgent,stat'],
            'clinical_context' => ['nullable', 'string', 'max:2000'], 'idempotency_key' => ['required', 'string', 'max:100'],
            'correlation_id' => ['nullable', 'uuid'], 'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.lab_test_id' => ['required', 'uuid'],
        ]);
        $db = DB::connection('hospital');
        return $db->transaction(function () use ($db, $data, $tenant, $actor, $request): JsonResponse {
            $existing = $db->table('lab_orders')->where('tenant_id', $tenant)->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) return $this->json(['lab_order' => $this->order($existing)], 200, $this->requestId($request));
            $tests = [];
            foreach ($data['items'] as $item) {
                $test = $db->table('lab_tests')->where('lab_test_id', $item['lab_test_id'])->where('status', 'active')->first();
                if (!$test) return $this->error('LAB_TEST_NOT_FOUND', 422, $this->requestId($request));
                $tests[] = $test;
            }
            $id = (string) Str::uuid();
            $correlation = $data['correlation_id'] ?? (string) Str::uuid();
            $db->table('lab_orders')->insert([
                'lab_order_id' => $id, 'tenant_id' => $tenant, 'local_patient_id' => $data['local_patient_id'],
                'care_episode_id' => $data['care_episode_id'], 'clinical_note_id' => $data['clinical_note_id'] ?? null,
                'ordered_by_user_ref_id' => $actor, 'priority' => $data['priority'], 'clinical_context' => $data['clinical_context'] ?? null,
                'status' => 'issued', 'idempotency_key' => $data['idempotency_key'], 'correlation_id' => $correlation,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($tests as $test) $db->table('lab_order_items')->insert([
                'lab_order_item_id' => (string) Str::uuid(), 'lab_order_id' => $id, 'lab_test_id' => $test->lab_test_id,
                'test_code' => $test->code, 'test_name' => $test->name, 'created_at' => now(),
            ]);
            $db->table('lab_order_events')->insert([
                'lab_order_id' => $id, 'event_seq' => 1, 'from_status' => null, 'to_status' => 'issued',
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
        $data = $request->validate(['status' => ['required', 'in:collected,in_process,resulted,validated']]);
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
        $db = DB::connection('hospital');
        return $db->transaction(function () use ($db, $tenant, $actor, $id, $data, $request): JsonResponse {
            $order = $db->table('lab_orders')->where('tenant_id', $tenant)->where('lab_order_id', $id)->lockForUpdate()->first();
            if (!$order) return $this->error('LAB_ORDER_NOT_FOUND', 404, $this->requestId($request));
            if (in_array($order->status, ['validated', 'cancelled'], true)) return $this->error('LAB_ORDER_CANCELLATION_INVALID', 409, $this->requestId($request));
            $db->table('lab_orders')->where('lab_order_id', $id)->update(['status' => 'cancelled', 'updated_at' => now()]);
            $seq = ((int) $db->table('lab_order_events')->where('lab_order_id', $id)->max('event_seq')) + 1;
            $db->table('lab_order_events')->insert(['lab_order_id' => $id, 'event_seq' => $seq, 'from_status' => $order->status, 'to_status' => 'cancelled', 'actor_user_ref_id' => $actor, 'occurred_at' => now(), 'correlation_id' => $order->correlation_id, 'reason' => $data['reason']]);
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
    private function json(array $data, int $status, string $requestId): JsonResponse { return response()->json(['data' => $data, 'meta' => ['request_id' => $requestId]], $status)->header('Cache-Control', 'no-store, private'); }
    private function error(string $code, int $status, string $requestId): JsonResponse { return response()->json(['error' => ['code' => $code], 'meta' => ['request_id' => $requestId]], $status)->header('Cache-Control', 'no-store, private'); }
}
