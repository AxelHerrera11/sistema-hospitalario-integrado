<?php

namespace Database\Factories;

use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabResult;
use App\Models\Sample;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Ítem y muestra recibida pertenecen a la misma orden y al mismo hospital.
 * Las banderas no se calculan aquí: la clasificación real es de
 * CriticalValueEvaluator (F4).
 *
 * @extends Factory<LabResult>
 */
class LabResultFactory extends Factory
{
    protected $model = LabResult::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'lab_order_item_id' => fn (array $attributes) => LabOrderItem::factory()->state([
                'lab_order_id' => LabOrder::factory()->state(['tenant_id' => $attributes['tenant_id']]),
            ]),
            'sample_id' => fn (array $attributes) => Sample::factory()->received()->state([
                'tenant_id' => $attributes['tenant_id'],
                'lab_order_id' => LabOrderItem::query()->findOrFail($attributes['lab_order_item_id'])->lab_order_id,
            ]),
            'entered_by' => fn (array $attributes) => User::factory()
                ->state(['tenant_id' => $attributes['tenant_id']]),
            'validated_by' => null,
            'numeric_value' => 95,
            'text_value' => null,
            'is_critical' => false,
            'is_abnormal' => false,
            'resulted_at' => now(),
            'validated_at' => null,
            'sent_to_emr' => false,
            'sent_to_emr_at' => null,
        ];
    }

    public function validated(): static
    {
        return $this->state([
            'validated_by' => fn (array $attributes) => User::factory()
                ->state(['tenant_id' => $attributes['tenant_id']]),
            'validated_at' => now(),
        ]);
    }

    public function critical(): static
    {
        return $this->state([
            'numeric_value' => 450,
            'is_abnormal' => true,
            'is_critical' => true,
        ]);
    }
}
