<?php

namespace Database\Factories;

use App\Models\LabOrder;
use App\Models\Sample;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sample>
 */
class SampleFactory extends Factory
{
    protected $model = Sample::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'lab_order_id' => fn (array $attributes) => LabOrder::factory()
                ->state(['tenant_id' => $attributes['tenant_id']]),
            'received_by' => null,
            'barcode' => fake()->unique()->numerify('BC-FAC-######'),
            'sample_type' => 'sangre',
            'collected_at' => now(),
            'received_at' => null,
            'status' => 'pendiente',
            'notes' => null,
        ];
    }

    /** Muestra de una orden existente, en el hospital de esa orden. */
    public function forOrder(LabOrder $order): static
    {
        return $this->state([
            'tenant_id' => $order->tenant_id,
            'lab_order_id' => $order->id,
        ]);
    }

    public function received(): static
    {
        return $this->state([
            'status' => 'recibida',
            'received_at' => now(),
            'received_by' => fn (array $attributes) => User::factory()
                ->state(['tenant_id' => $attributes['tenant_id']]),
        ]);
    }
}
