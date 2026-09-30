<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TenantSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Tenant::query()->firstOrCreate(
            ['slug' => 'san-marcos-demo'],
            [
                'id'   => '00000000-0000-4000-8000-000000000001',
                'name' => 'Hospital General San Marcos (demo)',
                'data' => [],
            ]
        );

        Tenant::query()->firstOrCreate(
            ['slug' => 'santa-elena-demo'],
            [
                'id'   => '00000000-0000-4000-8000-000000000002',
                'name' => 'Clínica Santa Elena (demo)',
                'data' => [],
            ]
        );
    }
}
