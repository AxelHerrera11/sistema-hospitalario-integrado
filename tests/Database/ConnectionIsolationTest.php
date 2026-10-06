<?php

namespace Tests\Database;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class ConnectionIsolationTest extends TestCase
{
    public function test_central_and_hospital_use_distinct_postgresql_databases(): void
    {
        $this->assertPostgreSqlIntegrityPrerequisites();

        $central = config('database.connections.central');
        $hospital = config('database.connections.hospital');

        $this->assertSame('pgsql', $central['driver']);
        $this->assertSame('pgsql', $hospital['driver']);
        $this->assertNotSame($central['database'], $hospital['database']);
        $this->assertSame('pgsql', DB::connection('central')->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $this->assertSame('pgsql', DB::connection('hospital')->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $this->assertNotSame(
            DB::connection('central')->scalar('SELECT current_database()'),
            DB::connection('hospital')->scalar('SELECT current_database()'),
        );
    }
}
