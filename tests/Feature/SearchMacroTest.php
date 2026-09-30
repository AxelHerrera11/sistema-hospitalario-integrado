<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchMacroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create();
        app()->instance('currentTenant', $tenant);

        Patient::factory()->create(['tenant_id' => $tenant->id, 'first_name' => 'María', 'last_name' => 'López']);
        Patient::factory()->create(['tenant_id' => $tenant->id, 'first_name' => 'Mario', 'last_name' => 'Pérez']);
        Patient::factory()->create(['tenant_id' => $tenant->id, 'first_name' => 'Juan', 'last_name' => 'García']);
    }

    public function test_search_ignores_case(): void
    {
        $this->assertSame(2, Patient::query()->whereSearch('first_name', 'MAR')->count());
    }

    public function test_search_across_several_columns(): void
    {
        $this->assertSame(1, Patient::query()->whereSearch(['first_name', 'last_name'], 'juan')->count());
    }

    public function test_empty_term_returns_everything(): void
    {
        $this->assertSame(3, Patient::query()->whereSearch('first_name', '  ')->count());
    }

    public function test_search_ignores_accents_on_postgres(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('La búsqueda sin acentos solo aplica en PostgreSQL.');
        }

        $this->assertSame(1, Patient::query()->whereSearch('last_name', 'lopez')->count());
        $this->assertSame(1, Patient::query()->whereSearch('first_name', 'maria')->count());
    }
}
