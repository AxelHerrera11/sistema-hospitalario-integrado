<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeders_create_users_for_each_demo_hospital(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'tenant_id' => '00000000-0000-4000-8000-000000000001',
            'email' => 'admin+san-marcos-demo@demo.local',
        ]);
        $this->assertDatabaseHas('users', [
            'tenant_id' => '00000000-0000-4000-8000-000000000002',
            'email' => 'admin+santa-elena-demo@demo.local',
        ]);
    }
}
