<?php

namespace Tests\Database;

use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('database-integrity')]
class MigrationLayoutTest extends TestCase
{
    public function test_federated_migration_directories_are_separate_from_framework_migrations(): void
    {
        $root = base_path('database/migrations');

        $this->assertDirectoryExists($root.'/central');
        $this->assertDirectoryExists($root.'/hospital');
        $this->assertDirectoryExists($root.'/framework');
        $this->assertArrayHasKey('migrate:central', json_decode(file_get_contents(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR)['scripts']);
        $this->assertArrayHasKey('migrate:hospital', json_decode(file_get_contents(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR)['scripts']);
    }

    public function test_only_framework_migrations_remain_outside_federated_trees(): void
    {
        $root = base_path('database/migrations');

        $this->assertFileExists($root.'/framework/0001_01_01_000002_create_cache_table.php');
        $this->assertFileExists($root.'/framework/0001_01_01_000003_create_jobs_table.php');
        $this->assertSame(['central', 'framework', 'hospital'], $this->migrationDirectories($root));
    }

    public function test_legacy_database_artifacts_and_sqlite_acceptance_scripts_are_absent(): void
    {
        foreach ([
            'app/Models/Admission.php',
            'app/Models/Tenant.php',
            'app/Models/User.php',
            'database/factories/UserFactory.php',
            'database/seeders/DemoDataSeeder2026.php',
            'database/seeders/RoleSeeder.php',
            'database/seeders/TenantSeeder.php',
        ] as $path) {
            $this->assertFileDoesNotExist(base_path($path));
        }

        $composer = file_get_contents(base_path('composer.json'));

        $this->assertStringNotContainsString('database/database.sqlite', $composer);
        $this->assertStringNotContainsString('db:demo', $composer);
        $this->assertStringNotContainsString('->call(', file_get_contents(base_path('database/seeders/DatabaseSeeder.php')));
    }

    /** @return list<string> */
    private function migrationDirectories(string $root): array
    {
        $entries = array_filter(scandir($root) ?: [], fn (string $entry) => ! in_array($entry, ['.', '..'], true));

        sort($entries);

        return array_values($entries);
    }
}
