<?php

namespace Database\Factories;

use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabTest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * lab_order_items no tiene tenant_id: la prueba se crea en el hospital de la orden.
 *
 * @extends Factory<LabOrderItem>
 */
class LabOrderItemFactory extends Factory
{
    protected $model = LabOrderItem::class;

    public function definition(): array
    {
        return [
            'lab_order_id' => LabOrder::factory(),
            'lab_test_id' => fn (array $attributes) => LabTest::factory()->state([
                'tenant_id' => DB::table('lab_orders')->where('id', $attributes['lab_order_id'])->value('tenant_id'),
            ]),
            'status' => 'pendiente',
        ];
    }
}
