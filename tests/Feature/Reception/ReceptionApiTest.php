<?php

namespace Tests\Feature\Reception;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ReceptionApiTest extends ReceptionTestCase
{
    private const URL = '/api/v1/lab/specimens';

    public function test_receive_replay_query_and_label_use_same_code(): void
    {
        $this->signIn(); $input = $this->payload();
        $first = $this->postJson(self::URL, $input)->assertCreated()->assertJsonPath('data.status', 'accepted');
        $code = $first->json('data.code');
        $this->postJson(self::URL, $input)->assertCreated()->assertExactJson($first->json());
        $this->getJson(self::URL.'/'.$code)->assertOk()->assertJsonPath('data.code', $code);
        $this->getJson(self::URL.'/'.$code.'/label')->assertOk()->assertJsonPath('data.code', $code);
        $this->assertSame(1, DB::connection('hospital')->table('specimens')->count());
        $this->assertSame(1, DB::connection('hospital')->table('outbox_events')->count());
        $this->assertArrayNotHasKey('request_hash', $first->json('data'));
    }

    public function test_rejection_is_recorded_without_code_and_new_sample_can_follow(): void
    {
        $this->signIn(); $input = $this->payload(); $input['derramada'] = true;
        $this->postJson(self::URL, $input)->assertCreated()->assertJsonPath('data.reason', 'derramada')->assertJsonPath('data.code', null);
        $this->postJson(self::URL, $this->payload())->assertCreated()->assertJsonPath('data.status', 'accepted');
        $this->assertSame(2, DB::connection('hospital')->table('outbox_events')->count());
    }

    public function test_missing_label_is_a_clinical_rejection(): void
    {
        $this->signIn(); $input = $this->payload(); $input['sin_etiqueta'] = true;
        unset($input['patient_code'], $input['patient_name']);
        $this->postJson(self::URL, $input)->assertCreated()->assertJsonPath('data.reason', 'sin_etiqueta');
    }

    public function test_changed_payload_and_second_acceptance_conflict(): void
    {
        $this->signIn(); $input = $this->payload();
        $this->postJson(self::URL, $input)->assertCreated();
        $input['volume'] = 4;
        $this->postJson(self::URL, $input)->assertConflict();
        $this->postJson(self::URL, $this->payload())->assertConflict();
    }

    public function test_pending_orders_and_invalid_input(): void
    {
        $this->signIn();
        $this->getJson(self::URL.'/pending-orders?search=P001')->assertOk()->assertJsonCount(1, 'data');
        $input = $this->payload(); $input['item_id'] = 'invalid';
        $this->postJson(self::URL, $input)->assertUnprocessable()->assertJsonValidationErrors('item_id');
        $this->getJson(self::URL.'/invalid')->assertNotFound();
    }

    public function test_jwt_and_receive_permission_are_required(): void
    {
        $this->withHeader('X-Tenant-ID', $this->hospitalA)->postJson(self::URL, $this->payload())->assertUnauthorized();
        $this->signIn('bioquimico');
        $this->postJson(self::URL, $this->payload())->assertForbidden();
    }

    public function test_wrong_clinical_hospital_and_unsynced_actor_are_denied(): void
    {
        $this->signIn();
        config(['reception.hospital_id' => $this->hospitalB]);
        $this->postJson(self::URL, $this->payload())->assertForbidden();
        config(['reception.hospital_id' => $this->hospitalA]);
        DB::connection('hospital')->table('local_user_refs')->delete();
        $this->postJson(self::URL, $this->payload())->assertForbidden();
    }
}
