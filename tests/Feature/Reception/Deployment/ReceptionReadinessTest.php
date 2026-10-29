<?php

namespace Tests\Feature\Reception\Deployment;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

final class ReceptionReadinessTest extends ReceptionPostgresTestCase
{
    public function test_readiness_detects_debug_and_accepts_real_postgres_installation(): void
    {
        config(['app.debug' => false]);
        $this->assertSame(0, Artisan::call('reception:readiness', ['--json' => true]));
        $report = json_decode(Artisan::output(), true);
        $this->assertTrue($report['checks']['central_postgresql']);
        $this->assertTrue($report['checks']['hospital_postgresql']);
        config(['app.debug' => true]);
        $this->assertSame(1, Artisan::call('reception:readiness', ['--json' => true]));
        $this->assertFalse(json_decode(Artisan::output(), true)['checks']['debug_disabled']);
    }

    public function test_real_central_jwt_reaches_clinical_postgres_and_outbox(): void
    {
        $this->signIn(); $input = $this->payload();
        $first = $this->postJson('/api/v1/lab/specimens', $input)->assertCreated();
        $this->postJson('/api/v1/lab/specimens', $input)->assertCreated()->assertExactJson($first->json());
        $this->assertSame(1, DB::connection('hospital')->table('specimens')->count());
        $this->assertSame(1, DB::connection('hospital')->table('outbox_events')->count());
        $this->assertGreaterThan(0, DB::connection('central')->table('auth_sessions')->count());
    }
}
