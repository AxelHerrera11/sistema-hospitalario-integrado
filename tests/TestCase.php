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

    /**
     * Pasos de migrate:rollback (un solo lote, orden descendente por nombre) para revertir HOSPITAL hasta la migración
     * $desde inclusive. Se cuentan los archivos, así una migración nueva de otro módulo no cambia el resultado de la prueba.
     */
    protected function hospitalRollbackSteps(string $desde): int
    {
        return count(array_filter(glob(database_path('migrations/hospital/*.php')), static fn (string $f): bool => basename($f, '.php') >= $desde));
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
