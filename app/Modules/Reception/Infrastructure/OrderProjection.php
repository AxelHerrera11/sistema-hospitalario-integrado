<?php

namespace App\Modules\Reception\Infrastructure;

use App\Modules\Reception\Application\OrderGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** Proyección local alimentada por el adaptador confiable de órdenes; sin alta pública. */
final class OrderProjection implements OrderGateway
{
    public function order(string $tenant, string $item): ?array
    {
        abort_unless(hash_equals((string) config('reception.hospital_id'), $tenant), 403);
        $row = DB::connection('hospital')->table('reception_order_items')->where('item_id', $item)->lockForUpdate()->first();
        if (!$row) return null;
        $order = (array) $row;
        if (DB::connection('hospital')->table('specimens')->where('lab_order_item_id', $item)->where('status', 'accepted')->exists()) $order['status'] = 'received';
        return $order;
    }

    public function import(array $data): void
    {
        $row = Validator::make($data, [
            'tenant_id' => ['required', 'uuid'], 'item_id' => ['required', 'uuid'],
            'local_patient_id' => ['required', 'uuid'], 'care_episode_id' => ['required', 'uuid'],
            'patient_code' => ['required', 'string', 'max:100'], 'patient_name' => ['required', 'string', 'max:200'],
            'type' => ['required', 'in:blood,urine,swab'], 'container' => ['required', 'string', 'max:100'],
            'min_volume' => ['required', 'numeric', 'between:0,1000'], 'max_minutes' => ['required', 'integer', 'between:1,10080'],
            'min_temperature' => ['required', 'numeric', 'between:-80,100'],
            'max_temperature' => ['required', 'numeric', 'gte:min_temperature', 'max:100'],
            'status' => ['required', 'in:pending,cancelled'],
        ])->validate();
        abort_unless(hash_equals((string) config('reception.hospital_id'), $row['tenant_id']), 403);
        unset($row['tenant_id']);
        $db = DB::connection('hospital');
        $db->transaction(function () use ($db, $row): void {
            $db->table('reception_order_items')->insertOrIgnore($row);
            $current = $db->table('reception_order_items')->where('item_id', $row['item_id'])->lockForUpdate()->first();
            if ($db->table('specimens')->where('lab_order_item_id', $row['item_id'])->where('status', 'accepted')->exists()) return;
            abort_unless($db->table('care_episodes')->where('care_episode_id', $row['care_episode_id'])->where('local_patient_id', $row['local_patient_id'])->exists(), 422);
            if ($current) $db->table('reception_order_items')->where('item_id', $row['item_id'])->update($row);
        });
    }
}
