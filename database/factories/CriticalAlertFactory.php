<?php

namespace Database\Factories;

use App\Models\CriticalAlert;
use App\Models\LabResult;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * Alerta valor_critico_lab: paciente y médico notificado salen de la orden
 * del resultado, como lo hará la validación en F4.
 *
 * @extends Factory<CriticalAlert>
 */
class CriticalAlertFactory extends Factory
{
    protected $model = CriticalAlert::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'lab_result_id' => fn (array $attributes) => LabResult::factory()->critical()->validated()
                ->state(['tenant_id' => $attributes['tenant_id']]),
            'patient_id' => fn (array $attributes) => $this->orderOf($attributes['lab_result_id'])->patient_id,
            'notified_user_id' => fn (array $attributes) => $this->orderOf($attributes['lab_result_id'])->ordered_by,
            'alert_type' => 'valor_critico_lab',
            'message' => 'Valor crítico de laboratorio pendiente de revisión.',
            'acknowledged' => false,
            'acknowledged_at' => null,
        ];
    }

    /** acknowledged y acknowledged_at siempre coherentes (hallazgo H-06). */
    public function acknowledged(): static
    {
        return $this->state([
            'acknowledged' => true,
            'acknowledged_at' => now(),
        ]);
    }

    /** Paciente y médico de la orden a la que pertenece el resultado. */
    private function orderOf(int $labResultId): object
    {
        return DB::table('lab_results')
            ->join('lab_order_items', 'lab_order_items.id', '=', 'lab_results.lab_order_item_id')
            ->join('lab_orders', 'lab_orders.id', '=', 'lab_order_items.lab_order_id')
            ->where('lab_results.id', $labResultId)
            ->first(['lab_orders.patient_id', 'lab_orders.ordered_by']);
    }
}
