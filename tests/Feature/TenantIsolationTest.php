<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Garantiza que el trait BelongsToTenant aísla los datos por hospital.
 * Si esta prueba falla, hay riesgo de mostrar pacientes de otro hospital.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_queries_only_return_records_of_the_current_tenant(): void
    {
        $hospitalA = Tenant::factory()->create();
        $hospitalB = Tenant::factory()->create();

        Patient::factory()->count(3)->create(['tenant_id' => $hospitalA->id]);
        Patient::factory()->count(2)->create(['tenant_id' => $hospitalB->id]);

        app()->instance('currentTenant', $hospitalA);
        $this->assertSame(3, Patient::query()->count());

        app()->instance('currentTenant', $hospitalB);
        $this->assertSame(2, Patient::query()->count());
    }

    public function test_records_from_another_tenant_cannot_be_found_by_id(): void
    {
        $hospitalA = Tenant::factory()->create();
        $hospitalB = Tenant::factory()->create();

        $foreignPatient = Patient::factory()->create(['tenant_id' => $hospitalB->id]);

        app()->instance('currentTenant', $hospitalA);

        $this->assertNull(Patient::query()->find($foreignPatient->id));
    }

    public function test_new_records_get_the_current_tenant_automatically(): void
    {
        $hospital = Tenant::factory()->create();
        app()->instance('currentTenant', $hospital);

        $patient = Patient::factory()->create(['tenant_id' => null]);

        $this->assertSame($hospital->id, $patient->tenant_id);
    }

    public function test_user_queries_are_limited_to_the_current_tenant(): void
    {
        $hospitalA = Tenant::factory()->create();
        $hospitalB = Tenant::factory()->create();

        User::factory()->count(3)->create(['tenant_id' => $hospitalA->id]);
        User::factory()->count(2)->create(['tenant_id' => $hospitalB->id]);

        app()->instance('currentTenant', $hospitalA);
        $this->assertSame(3, User::query()->count());

        app()->instance('currentTenant', $hospitalB);
        $this->assertSame(2, User::query()->count());
    }

    public function test_new_users_get_the_current_tenant_automatically(): void
    {
        $hospital = Tenant::factory()->create();
        app()->instance('currentTenant', $hospital);

        $user = User::factory()->create(['tenant_id' => null]);

        $this->assertSame($hospital->id, $user->tenant_id);
    }
}
