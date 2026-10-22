<?php
declare(strict_types=1);
namespace App\Modules\Reception\Infrastructure;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

/** Read-only boundary: ASII-16 owns orders; no projection or writes here. */
final class OrdersGateway
{
    public function get(string $tenant, ?string $item = null, ?string $search = null): array
    {
        $url = config('reception.orders_url'); $token = config('reception.orders_token');
        if (!is_string($url) || !str_starts_with($url, 'https://') || !$token) {
            throw new \DomainException('Servicio de órdenes no disponible', 503);
        }
        try {
            $response = Http::withToken($token)->withHeaders(['X-Tenant-ID' => $tenant])->timeout(5)
                ->get(rtrim($url, '/').($item ? '/'.rawurlencode($item) : ''), $item ? [] : ['search' => $search]);
            if ($response->status() === 404 && $item) return [];
            $response->throw();
            $rows = $item ? [$response->json('data')] : $response->json('data');
            if (!is_array($rows) || count($rows) > 20) throw new \RuntimeException();
            foreach ($rows as $row) {
                Validator::make($row, [
                    'tenant_id' => ['required', 'uuid', 'in:'.$tenant], 'item_id' => ['required', 'uuid'],
                    'local_patient_id' => ['required', 'uuid'], 'care_episode_id' => ['nullable', 'uuid'],
                    'patient_code' => ['required', 'string'], 'patient_name' => ['required', 'string'],
                    'type' => ['required', 'in:blood,urine,swab'], 'container' => ['required', 'string'],
                    'min_volume' => ['required', 'numeric', 'min:0'], 'max_minutes' => ['required', 'integer', 'min:1'],
                    'min_temperature' => ['required', 'numeric'], 'max_temperature' => ['required', 'numeric', 'gte:min_temperature'],
                    'status' => ['required', 'in:pending,pending_new_sample,received,cancelled'],
                ])->validate();
                if ($item && $row['item_id'] !== $item) throw new \RuntimeException();
            }
            return $rows;
        } catch (\Throwable $e) { throw new \DomainException('No se pudo verificar la orden', 503, $e); }
    }
}
