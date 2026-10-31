<?php

namespace Tests\Feature\Reception;

use App\Modules\Reception\Infrastructure\OrderProjection;
use Illuminate\Support\Facades\DB;

final class ReceptionFlowTest extends ReceptionTestCase
{
    public function test_accepted_order_disappears_from_pending_search(): void
    {
        $this->signIn();
        $this->getJson('/api/v1/lab/specimens/pending-orders?search=P001')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson('/api/v1/lab/specimens', $this->payload())->assertCreated();
        $this->getJson('/api/v1/lab/specimens/pending-orders?search=P001')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_custody_cannot_be_changed_or_deleted(): void
    {
        $this->signIn(); $this->postJson('/api/v1/lab/specimens', $this->payload())->assertCreated();
        foreach (["UPDATE specimens SET reason = 'alterado'", 'DELETE FROM specimens'] as $sql) {
            try { DB::connection('hospital')->statement($sql); $this->fail('Custodia alterada'); }
            catch (\Illuminate\Database\QueryException $error) { $this->assertSame('23514', (string) $error->getCode()); }
        }
        $this->assertSame(1, DB::connection('hospital')->table('specimens')->count());
    }

    public function test_cancelled_order_cannot_be_received(): void
    {
        $row = $this->projection(); $row['status'] = 'cancelled';
        (new OrderProjection)->import($row);
        $this->signIn();
        $this->postJson('/api/v1/lab/specimens', $this->payload())->assertConflict()->assertJsonPath('error', 'ORDER_CONFLICT');
        $this->assertSame(0, DB::connection('hospital')->table('specimens')->count());
    }
}
