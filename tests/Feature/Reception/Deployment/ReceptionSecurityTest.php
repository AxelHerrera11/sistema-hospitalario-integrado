<?php

namespace Tests\Feature\Reception\Deployment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ReceptionSecurityTest extends ReceptionPostgresTestCase
{
    private const URL = '/api/v1/lab/specimens';

    public function test_phi_responses_and_denials_cannot_be_cached(): void
    {
        $this->withHeader('X-Tenant-ID',$this->hospitalA)->getJson(self::URL.'/invalid')->assertUnauthorized()
            ->assertHeader('Pragma','no-cache')->assertHeader('X-Content-Type-Options','nosniff');
        $this->signIn();
        $response = $this->postJson(self::URL,$this->payload())->assertCreated();
        $this->assertStringContainsString('no-store',$response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('Ana Demo',$response->getContent());
        $response->assertHeader('X-Correlation-ID');
    }

    public function test_spoofed_user_headers_do_not_authenticate(): void
    {
        $this->withHeaders(['X-Tenant-ID'=>$this->hospitalA,'X-User-ID'=>$this->userId,'X-User-Ref'=>$this->actor])
            ->postJson(self::URL,$this->payload())->assertUnauthorized();
        $this->assertSame(0,DB::connection('hospital')->table('specimens')->count());
    }

    public function test_token_for_one_hospital_cannot_read_another(): void
    {
        $this->signIn();
        $this->withHeader('X-Tenant-ID',$this->hospitalB)->getJson(self::URL.'/invalid')->assertForbidden();
    }

    public function test_request_limit_and_body_limit_are_enforced(): void
    {
        $this->signIn(); config(['reception.requests_per_minute'=>1]);
        $this->getJson(self::URL.'/invalid')->assertNotFound();
        $this->getJson(self::URL.'/invalid')->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_body_limit_precedes_clinical_write(): void
    {
        $this->signIn(); $input=$this->payload(); $input['untrusted']=str_repeat('x',65537);
        $this->postJson(self::URL,$input)->assertStatus(413);
        $this->assertSame(0,DB::connection('hospital')->table('specimens')->count());
    }

    public function test_observability_never_logs_request_data(): void
    {
        $this->signIn(); Log::spy();
        $this->postJson(self::URL,$this->payload())->assertCreated();
        Log::shouldHaveReceived('info')->withArgs(function ($event,$context): bool {
            if ($event !== 'reception.request') return false;
            $this->assertEqualsCanonicalizing(['correlation_id','method','route','status','actor_id','hospital_id'],array_keys($context));
            $this->assertSame('api/v1/lab/specimens',$context['route']);
            $this->assertStringNotContainsString('Ana Demo',json_encode($context));
            return true;
        });
    }

    public function test_internal_failures_are_sanitized_even_with_debug_enabled(): void
    {
        $this->signIn(); config(['app.debug'=>true]);
        DB::connection('hospital')->statement('ALTER TABLE reception_order_items RENAME TO reception_orders_offline');
        DB::connection('hospital')->statement('SET search_path TO '.$this->schema);
        $response=$this->getJson(self::URL.'/pending-orders?search=P001')->assertStatus(503);
        $this->assertSame(['error','message'],array_keys($response->json()));
        $this->assertStringNotContainsString('SQLSTATE',$response->getContent());
    }
}
