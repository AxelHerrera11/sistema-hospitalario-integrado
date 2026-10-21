<?php
namespace Tests;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $basePath = dirname(__DIR__);
        $_ENV['APP_BASE_PATH'] = $basePath;
        $_SERVER['APP_BASE_PATH'] = $basePath;

        return parent::createApplication();
    }

    protected function assertPostgreSqlIntegrityPrerequisites(): void
    {
        $this->assertTrue(extension_loaded('pdo_pgsql'));
        $this->assertTrue(extension_loaded('pgsql'));
        $this->assertGreaterThanOrEqual(150000, (int) DB::connection('central')->scalar('SHOW server_version_num'));
        $this->assertGreaterThanOrEqual(150000, (int) DB::connection('hospital')->scalar('SHOW server_version_num'));
        $this->assertSame('btree_gist', DB::connection('hospital')->scalar("SELECT extname FROM pg_extension WHERE extname = 'btree_gist'"));
    }
}
