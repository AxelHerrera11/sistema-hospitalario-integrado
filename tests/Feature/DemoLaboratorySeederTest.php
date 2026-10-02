<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoLaboratorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_consistent_laboratory_demo_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenants = Tenant::all();
        $this->assertNotEmpty($tenants);

        foreach ($tenants as $tenant) {
            $bioquimico = User::where(
                'email',
                "bioq+{$tenant->slug}@demo.local"
            )->firstOrFail();

            $this->assertSame($tenant->id, $bioquimico->tenant_id);
            $this->assertTrue($bioquimico->hasRole('Bioquimico'));

            foreach (['pendiente', 'en_proceso', 'completada'] as $status) {
                $this->assertTrue(
                    DB::table('lab_orders')
                        ->where('tenant_id', $tenant->id)
                        ->where('status', $status)
                        ->exists(),
                    "Falta una orden {$status} en {$tenant->slug}"
                );
            }

            $results = DB::table('lab_results')
                ->where('tenant_id', $tenant->id)
                ->get();

            $this->assertNotEmpty($results);

            foreach ($results as $result) {
                $this->assertEquals($bioquimico->id, $result->validated_by);
                $this->assertNotEquals(
                    $result->entered_by,
                    $result->validated_by
                );

                $test = DB::table('lab_order_items')
                    ->join(
                        'lab_tests',
                        'lab_tests.id',
                        '=',
                        'lab_order_items.lab_test_id'
                    )
                    ->where('lab_order_items.id', $result->lab_order_item_id)
                    ->select('lab_tests.*')
                    ->first();

                $this->assertNotNull($test);

                $value = (float) $result->numeric_value;

                $critical = ($test->critical_min !== null
                    && $value <= (float) $test->critical_min)
                    || ($test->critical_max !== null
                    && $value >= (float) $test->critical_max);

                $abnormal = $critical
                    || ($test->reference_min !== null
                    && $value < (float) $test->reference_min)
                    || ($test->reference_max !== null
                    && $value > (float) $test->reference_max);

                $this->assertSame($critical, (bool) $result->is_critical);
                $this->assertSame($abnormal, (bool) $result->is_abnormal);
            }
        }

        $openOrderResults = DB::table('lab_results')
            ->join(
                'lab_order_items',
                'lab_order_items.id',
                '=',
                'lab_results.lab_order_item_id'
            )
            ->join(
                'lab_orders',
                'lab_orders.id',
                '=',
                'lab_order_items.lab_order_id'
            )
            ->whereIn('lab_orders.status', ['pendiente', 'en_proceso'])
            ->count();

        $this->assertSame(0, $openOrderResults);

        foreach (DB::table('critical_alerts')->get() as $alert) {
            $this->assertSame(
                (bool) $alert->acknowledged,
                $alert->acknowledged_at !== null
            );
        }
    }
}