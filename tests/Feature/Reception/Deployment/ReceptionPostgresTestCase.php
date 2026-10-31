<?php

namespace Tests\Feature\Reception\Deployment;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Reception\ReceptionTestCase;

abstract class ReceptionPostgresTestCase extends ReceptionTestCase
{
    private string $centralSchema;

    protected function createSchema(): void
    {
        $config = (require config_path('database.php'))['connections']['central'];
        if (!str_ends_with($config['database'], '_test')) throw new \RuntimeException('Solo CENTRAL *_test');
        $this->centralSchema = 'reception_c_'.bin2hex(random_bytes(6));
        config(['database.connections.central' => $config]); DB::purge('central');
        DB::connection('central')->statement('CREATE SCHEMA '.$this->centralSchema);
        config(['database.connections.central.search_path' => $this->centralSchema.',public']); DB::purge('central');
        foreach (glob(database_path('migrations/central/*.php')) as $file) {
            DB::connection('central')->transaction(fn () => (require $file)->up());
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->centralSchema)) DB::connection('central')->statement('DROP SCHEMA '.$this->centralSchema.' CASCADE');
        parent::tearDown();
    }
}
