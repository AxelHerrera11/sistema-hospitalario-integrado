<?php

declare(strict_types=1);

namespace Tests\Feature\Prescriptions;

use App\Prescripciones\PdoPrescriptionContext;
use Illuminate\Contracts\Http\Kernel;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Alergias\HospitalTenantIdentity;
use Tests\Feature\TrasladosAltas\TrasladosTestIdentity;
use Tests\TestCase;

/** Rutas de prescripciones con RBAC (config/rbac.php) y EnsureHospitalDatabaseTenant reales; solo se sustituyen tenant y jwt.auth. */
#[Group('prescriptions-feature')]
final class PrescriptionMiddlewareFeatureTest extends TestCase
{
    private const PATIENT = '00000000-0000-4000-8000-000000000001';

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', TrasladosTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', HospitalTenantIdentity::class);
        $this->app->instance(RoleResolver::class, new TrasladosTestIdentity());
        $this->app->instance(DenialRecorder::class, new TrasladosTestIdentity());
        // Sin servicio registrado a propósito: ningún caso denegado debe llegar al controlador ni a la base.
        $this->app->instance(PdoPrescriptionContext::class, Mockery::mock(PdoPrescriptionContext::class));
        TrasladosTestIdentity::$hospital = 'hospital-a';
        config()->set('tenants.hospital_database_tenant_id', 'hospital-a');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    protected function tearDown(): void
    {
        // Los dobles de identidad son estáticos: se restauran para no afectar a otras pruebas.
        TrasladosTestIdentity::$hospital = 'hospital-a';
        TrasladosTestIdentity::como(null);
        parent::tearDown();
    }

    public function test_roles_without_the_permission_are_denied_before_the_service(): void
    {
        TrasladosTestIdentity::como('enfermeria');
        $this->postJson('/api/v1/patients/'.self::PATIENT.'/prescriptions', [])->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        $this->postJson('/api/v1/prescriptions/'.self::PATIENT.'/sign')->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        TrasladosTestIdentity::como('recepcion');
        $this->getJson('/api/v1/patients/'.self::PATIENT.'/prescriptions')->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
    }

    public function test_hospital_database_guard_fails_closed(): void
    {
        TrasladosTestIdentity::como('medico');
        TrasladosTestIdentity::$hospital = 'hospital-b';
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])->getJson('/api/v1/patients/'.self::PATIENT.'/prescriptions')
            ->assertStatus(403)->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_MISMATCH');
        TrasladosTestIdentity::$hospital = 'hospital-a';
        config()->set('tenants.hospital_database_tenant_id', null);
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a'])->postJson('/api/v1/patients/'.self::PATIENT.'/prescriptions', [])
            ->assertStatus(503)->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_UNCONFIGURED');
    }
}
