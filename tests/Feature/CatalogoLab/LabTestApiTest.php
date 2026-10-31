<?php

declare(strict_types=1);

namespace Tests\Feature\CatalogoLab;

use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Testing\TestResponse;
use MicroHis\CatalogoLab\Application\LabTestRepository;
use MicroHis\CatalogoLab\Persistence\InMemoryLabTestRepository;
use MicroHis\CatalogoLab\Persistence\PdoLabTestRepository;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use PDOException;
use Tests\Feature\TrasladosAltas\TrasladosTestIdentity;
use Tests\TestCase;

/** API v1 del catálogo de pruebas con middlewares, matriz RBAC y guard de #1154 reales; HOSPITAL en memoria (PostgreSQL: CatalogoLabPostgresTest). */
final class LabTestApiTest extends TestCase
{
    private InMemoryLabTestRepository $hospital;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(Kernel::class);
        // tenant = identidad de Traslados; jwt.auth = el modelo Hospital real que exige EnsureHospitalDatabaseTenant.
        $this->app['router']->aliasMiddleware('tenant', TrasladosTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', 'test.hospital-tenant');
        $this->app->instance('test.hospital-tenant', new class
        {
            public function handle($request, $next)
            {
                $request->attributes->set('tenant', new Hospital(['hospital_id' => TrasladosTestIdentity::$hospital]));

                return $next($request);
            }
        });
        $this->app->instance(RoleResolver::class, new TrasladosTestIdentity);
        $this->app->instance(DenialRecorder::class, new TrasladosTestIdentity);
        $this->hospital = new InMemoryLabTestRepository;
        $this->app->instance(LabTestRepository::class, $this->hospital);
        $this->app->instance(ResolutorActorLocal::class, new class implements ResolutorActorLocal
        {
            public function referencia(string $centralUserId): ?string
            {
                return $centralUserId === '00000000-0000-4000-8000-00000000c0a1' ? '00000000-0000-4000-8000-0000000000f1' : null;
            }
        });
        TrasladosTestIdentity::$hospital = 'hospital-a';
        config()->set('tenants.hospital_database_tenant_id', 'hospital-a');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    private function registrar(array $campos = []): TestResponse
    {
        return $this->postJson('/api/v1/lab-tests', $campos + ['code' => 'glu', 'name' => 'Glucosa', 'specimen_type' => 'serum', 'min_volume_ml' => 0.5,
            'result_type' => 'numeric', 'unit' => 'mg/dL', 'reference_low' => 70, 'reference_high' => 100, 'critical_low' => 40, 'critical_high' => 400,
            'valid_from' => now()->toDateString()]);
    }

    public function test_identity_tenant_permission_and_hospital_guard_are_enforced(): void
    {
        TrasladosTestIdentity::como(null);
        $this->getJson('/api/v1/lab-tests')->assertStatus(401);
        TrasladosTestIdentity::como('medico');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])->getJson('/api/v1/lab-tests')->assertStatus(403)->assertJsonPath('code', 'TENANT_MISMATCH');
        // medico consulta el catálogo pero no lo administra; recepcion ni lo consulta.
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a'])->registrar()->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        TrasladosTestIdentity::como('recepcion');
        $this->getJson('/api/v1/lab-tests')->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        TrasladosTestIdentity::como('medico');
        TrasladosTestIdentity::$hospital = 'hospital-b';
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])->getJson('/api/v1/lab-tests')->assertStatus(403)->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_MISMATCH');
        $this->assertSame([], $this->hospital->rows);
    }

    public function test_superadmin_registers_and_clinicians_find_the_active_version(): void
    {
        TrasladosTestIdentity::como('superadmin');
        $id = $this->withHeaders(['X-Request-ID' => 'req-catalogo-01'])->registrar(['created_by_user_ref_id' => 'suplantado'])
            ->assertStatus(201)->assertJsonPath('data.code', 'GLU')->assertJsonPath('data.version_no', 1)
            ->assertJsonPath('meta.request_id', 'req-catalogo-01')->assertHeader('Cache-Control', 'no-store, private')->json('data.lab_test_id');
        // El autor auditado es la referencia local del JWT, no un campo del cuerpo.
        $this->assertSame(['prueba_lab.registrada', '00000000-0000-4000-8000-0000000000f1', $id], $this->hospital->audit[0]);
        TrasladosTestIdentity::como('tecnico_laboratorio');
        $this->getJson('/api/v1/lab-tests?search=gluc')->assertOk()->assertJsonCount(1, 'data.lab_tests')->assertJsonPath('data.lab_tests.0.code', 'GLU');
        $this->getJson('/api/v1/lab-tests?date=2026-02-30')->assertStatus(422)->assertJsonPath('error.code', 'DATE_INVALID');
    }

    public function test_domain_rules_map_to_http_codes(): void
    {
        TrasladosTestIdentity::como('superadmin');
        $this->registrar(['critical_low' => 80])->assertStatus(422)->assertJsonPath('error.code', 'RANGES_INCOHERENT');
        $this->registrar(['code' => 'G LU'])->assertStatus(422)->assertJsonPath('error.code', 'CODE_INVALID');
        $this->registrar(['valid_from' => '2020-01-01'])->assertStatus(422)->assertJsonPath('error.field', 'valid_from');
        $this->registrar(['result_type' => 'qualitative'])->assertStatus(422)->assertJsonPath('error.code', 'RESULT_DEFINITION_INVALID');
        $this->registrar()->assertStatus(201);
        $this->registrar()->assertStatus(409)->assertJsonPath('error.code', 'LAB_TEST_VALIDITY_OVERLAP');
        $this->assertCount(1, $this->hospital->rows);
    }

    public function test_actor_without_local_reference_cannot_register(): void
    {
        TrasladosTestIdentity::como('superadmin', '00000000-0000-4000-8000-00000000c0ff');
        $this->registrar()->assertStatus(403)->assertJsonPath('error.code', 'ACTOR_WITHOUT_LOCAL_REFERENCE');
        $this->assertSame([], $this->hospital->rows);
    }

    public function test_unavailable_hospital_answers_503_without_details(): void
    {
        config()->set('app.debug', false);
        TrasladosTestIdentity::como('superadmin');
        // Adaptador real con la conexión perezosa del provider: el fallo ocurre dentro del caso de uso, no al resolver el controller.
        $this->app->instance(LabTestRepository::class, new PdoLabTestRepository(static fn () => throw new PDOException('SQLSTATE[08006] password=secreto')));
        $this->getJson('/api/v1/lab-tests')->assertStatus(503)->assertJsonPath('error.code', 'LAB_CATALOG_UNAVAILABLE')->assertHeader('Retry-After', '5');
        $respuesta = $this->registrar()->assertStatus(503)->assertJsonPath('error.code', 'LAB_CATALOG_UNAVAILABLE');
        $this->assertStringNotContainsString('secreto', $respuesta->getContent());
    }
}
