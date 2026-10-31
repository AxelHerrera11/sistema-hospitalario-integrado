<?php

namespace Tests\Feature\Reception\Deployment;

use Illuminate\Support\Facades\Artisan;

final class ReceptionReleaseGateTest extends ReceptionPostgresTestCase
{
    public function test_public_http_is_rejected_even_with_demo_flag(): void
    {
        config(['app.debug'=>false,'app.url'=>'http://hospital.example.test']);
        $this->assertSame(1,Artisan::call('reception:release-gate',['--loopback-demo'=>true]));
        $this->assertStringContainsString('FAIL https',Artisan::output());
    }

    public function test_testing_environment_and_array_rate_limit_cache_are_rejected(): void
    {
        config(['app.debug'=>false,'app.url'=>'https://hospital.example.test']);
        $this->assertSame(1,Artisan::call('reception:release-gate'));
        $output = Artisan::output();
        $this->assertStringContainsString('FAIL environment',$output);
        $this->assertStringContainsString('FAIL persistent_rate_limiter',$output);
    }
}
