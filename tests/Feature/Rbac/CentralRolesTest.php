<?php

namespace Tests\Feature\Rbac;

use App\Rbac\CentralRoles;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Consulta real del adaptador sobre SQLite; no acredita migraciones PostgreSQL. */
class CentralRolesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->startOfSecond());
        config(['database.connections.central' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('central');
        $schema = Schema::connection('central');
        foreach (['central_users' => 'user_id', 'hospitals' => 'hospital_id'] as $table => $key) {
            $schema->create($table, function (Blueprint $t) use ($key): void {
                $t->uuid($key)->primary();
                $t->string('status');
            });
        }
        $schema->create('hospital_memberships', function (Blueprint $t): void {
            $t->uuid('user_id');
            $t->uuid('hospital_id');
            $t->string('role_code');
            $t->string('status');
            $t->timestamp('valid_from');
            $t->timestamp('valid_to')->nullable();
        });
        DB::connection('central')->table('central_users')->insert([
            ['user_id' => $this->user(), 'status' => 'active'],
            ['user_id' => $this->user(2), 'status' => 'active'],
        ]);
        DB::connection('central')->table('hospitals')->insert([
            ['hospital_id' => $this->hospital(), 'status' => 'active'],
            ['hospital_id' => $this->hospital(2), 'status' => 'active'],
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('central');
        $this->travelBack();
        parent::tearDown();
    }

    public function test_roles_are_isolated_by_user_and_hospital_and_not_cached(): void
    {
        $this->grant('medico');
        $this->grant('enfermeria', ['hospital_id' => $this->hospital(2)]);
        $this->grant('superadmin', ['user_id' => $this->user(2)]);
        $resolver = new CentralRoles;
        $this->assertSame(['medico'], $resolver->rolesIn($this->user(), $this->hospital()));
        $this->assertSame(['enfermeria'], $resolver->rolesIn($this->user(), $this->hospital(2)));
        DB::connection('central')->table('hospital_memberships')->where('role_code', 'medico')->update(['status' => 'inactive']);
        $this->assertSame([], $resolver->rolesIn($this->user(), $this->hospital()));
    }

    public function test_membership_intervals_include_start_and_exclude_end(): void
    {
        $this->grant('medico', ['valid_from' => now()]);
        $this->grant('enfermeria', ['valid_from' => now()->addSecond()]);
        $this->grant('recepcion', ['valid_to' => now()]);
        $this->grant('admin_hospital', ['valid_to' => now()->subSecond()]);
        $this->grant('superadmin', ['status' => 'inactive']);
        $resolver = new CentralRoles;
        $this->assertSame(['medico'], $resolver->rolesIn($this->user(), $this->hospital()));
        $this->travel(1)->seconds();
        $this->assertEqualsCanonicalizing(['medico', 'enfermeria'], $resolver->rolesIn($this->user(), $this->hospital()));
    }

    public function test_inactive_identity_or_hospital_cannot_supply_roles(): void
    {
        $this->grant('superadmin');
        $resolver = new CentralRoles;
        foreach (['central_users', 'hospitals'] as $table) {
            DB::connection('central')->table($table)->update(['status' => 'inactive']);
            $this->assertSame([], $resolver->rolesIn($this->user(), $this->hospital()));
            DB::connection('central')->table($table)->update(['status' => 'active']);
        }
        $this->assertSame(['superadmin'], $resolver->rolesIn($this->user(), $this->hospital()));
    }

    private function grant(string $role, array $overrides = []): void
    {
        DB::connection('central')->table('hospital_memberships')->insert(array_replace([
            'user_id' => $this->user(), 'hospital_id' => $this->hospital(),
            'role_code' => $role, 'status' => 'active',
            'valid_from' => now()->subDay(), 'valid_to' => null,
        ], $overrides));
    }

    private function user(int $suffix = 1): string
    {
        return sprintf('00000000-0000-0000-0001-%012d', $suffix);
    }

    private function hospital(int $suffix = 1): string
    {
        return sprintf('00000000-0000-0000-0002-%012d', $suffix);
    }
}
