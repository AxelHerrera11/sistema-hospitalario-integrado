<?php

namespace Tests\Database\Hospital;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Alergias\HospitalTenantIdentity;
use Tests\Feature\TrasladosAltas\TrasladosTestIdentity;
use Tests\TestCase;

/** Cola local del prototipo NativePHP (ASII-29) sobre PostgreSQL real; la sincronización es simulada y no altera el estado. */
#[Group('database-integrity')]
class NativePhpActionsPostgresTest extends TestCase
{
    private const HOSPITAL = '11111111-1111-4111-8111-111111111111';
    private const TECNICO = '00000000-0000-4000-8000-0000000000c1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertPostgreSqlIntegrityPrerequisites();
        Artisan::call('migrate:fresh', ['--database' => 'hospital', '--path' => database_path('migrations/hospital'), '--realpath' => true, '--force' => true]);
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', TrasladosTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', HospitalTenantIdentity::class);
        $this->app->instance(RoleResolver::class, new TrasladosTestIdentity());
        $this->app->instance(DenialRecorder::class, new TrasladosTestIdentity());
        TrasladosTestIdentity::$hospital = self::HOSPITAL;
        config()->set('tenants.hospital_database_tenant_id', self::HOSPITAL);
        $this->withHeaders(['X-Tenant-ID' => self::HOSPITAL]);
    }

    protected function tearDown(): void
    {
        // Los dobles de identidad son estáticos: se restauran para no afectar a otras pruebas.
        TrasladosTestIdentity::$hospital = 'hospital-a';
        TrasladosTestIdentity::como(null);
        parent::tearDown();
    }

    public function test_prepared_actions_persist_and_simulated_sync_does_not_mark_them_synchronized(): void
    {
        TrasladosTestIdentity::como('tecnico_laboratorio', self::TECNICO);
        $this->postJson('/api/v1/nativephp/actions', ['kind' => 'lab_result_preview', 'payload' => '{"result_id":"r-1"}'])->assertOk();
        $this->getJson('/api/v1/nativephp/actions')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'pendiente_sincronizar');

        $this->postJson('/api/v1/nativephp/sync', ['connectivity' => 'online'])->assertOk()
            ->assertJsonPath('data.status', 'simulado')->assertJsonPath('data.pending', 1);
        $this->assertSame('pendiente_sincronizar', DB::connection('hospital')->table('nativephp_local_actions')->value('status'));
    }

    public function test_reader_without_create_permission_cannot_queue_actions(): void
    {
        TrasladosTestIdentity::como('bioquimico', self::TECNICO);
        $this->postJson('/api/v1/nativephp/actions', ['kind' => 'lab_result_preview', 'payload' => '{}'])->assertStatus(403);
        $this->assertSame(0, (int) DB::connection('hospital')->scalar('SELECT count(*) FROM nativephp_local_actions'));
    }
}
