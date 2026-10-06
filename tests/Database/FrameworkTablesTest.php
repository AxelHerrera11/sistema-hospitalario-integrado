<?php

namespace Tests\Database;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class FrameworkTablesTest extends TestCase
{
    public function test_framework_migrations_install_session_cache_and_job_tables_on_central(): void
    {
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', [
            '--database' => 'central',
            '--path' => database_path('migrations/framework'),
            '--realpath' => true,
            '--force' => true,
        ]);

        foreach (['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'] as $table) {
            $this->assertTrue(
                (bool) DB::connection('central')->scalar('SELECT to_regclass(?) IS NOT NULL', ['public.'.$table]),
                "Missing framework table {$table} on CENTRAL."
            );
        }
    }

    public function test_database_backed_drivers_default_to_the_central_framework_tables(): void
    {
        $this->assertSame('central', config('session.connection'));
        $this->assertSame('central', config('cache.stores.database.connection'));
        $this->assertSame('central', config('cache.stores.database.lock_connection'));
        $this->assertSame('central', config('queue.connections.database.connection'));
        $this->assertSame('central', config('queue.batching.database'));
        $this->assertSame('central', config('queue.failed.database'));
    }
}
