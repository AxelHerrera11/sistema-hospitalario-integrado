<?php

namespace Tests\Feature\Reception;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ReceptionContractTest extends ReceptionTestCase
{
    public function test_unknown_order_and_internal_fields_are_not_exposed(): void
    {
        $this->signIn(); $data = $this->payload(); $data['item_id'] = (string) Str::uuid();
        $this->postJson('/api/v1/lab/specimens', $data)->assertNotFound()->assertJsonPath('error', 'ORDER_NOT_FOUND');
        $result = $this->postJson('/api/v1/lab/specimens', $this->payload())->assertCreated()->json('data');
        $this->assertEqualsCanonicalizing(['id','item_id','status','reason','code','received_at'], array_keys($result));
    }

    public function test_revoked_local_actor_cannot_write(): void
    {
        $this->signIn();
        DB::connection('hospital')->table('local_user_refs')->update(['status' => 'revoked']);
        $this->postJson('/api/v1/lab/specimens', $this->payload())->assertForbidden();
        $this->assertSame(0, DB::connection('hospital')->table('specimens')->count());
    }

    public function test_reader_cannot_print_labels(): void
    {
        $this->signIn('bioquimico');
        $this->getJson('/api/v1/lab/specimens/AAAAAAAAAAAAAAAAAAAAAAAA-6/label')->assertForbidden();
    }
}
