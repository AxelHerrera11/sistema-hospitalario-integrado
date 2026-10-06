<?php

namespace Tests\Feature;

use App\Models\CriticalAlert;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\Sample;
use App\Models\SoapNote;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las factories de laboratorio deben crear toda la cadena en un solo
 * hospital (hallazgo H-03); si no, las pruebas de aislamiento mienten.
 */
class LabFactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_critical_alert_builds_the_whole_chain_in_one_hospital(): void
    {
        $alert = CriticalAlert::factory()->create();

        $result = $alert->labResult;
        $item = $result->orderItem;
        $order = $item->labOrder;

        $this->assertSame(1, Tenant::query()->count());
        $this->assertSame($order->tenant_id, $item->labTest->tenant_id);
        $this->assertSame($order->id, $result->sample->lab_order_id);
        $this->assertSame('recibida', $result->sample->status);
        $this->assertSame($order->patient_id, $alert->patient_id);
        $this->assertSame($order->ordered_by, $alert->notified_user_id);
        $this->assertSame($order->patient_id, $order->medicalRecord->patient_id);
        $this->assertSame($order->medical_record_id, SoapNote::query()->findOrFail($order->soap_note_id)->medical_record_id);
        $this->assertTrue($result->is_critical);
        $this->assertNotNull($result->validated_at);
    }

    public function test_an_explicit_tenant_propagates_to_every_level(): void
    {
        $tenant = Tenant::factory()->create();

        LabResult::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertSame(1, Tenant::query()->count());
        $this->assertSame(1, LabOrder::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(1, Sample::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_acknowledged_alerts_are_coherent(): void
    {
        $alert = CriticalAlert::factory()->acknowledged()->create();

        $this->assertTrue($alert->acknowledged);
        $this->assertNotNull($alert->acknowledged_at);
    }
}
