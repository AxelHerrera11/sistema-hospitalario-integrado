<?php

declare(strict_types=1);

namespace Tests\Feature\Admision;

use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Testing\TestResponse;
use MicroHis\Admision\Application\AdmissionRepository;
use MicroHis\Admision\Persistence\InMemoryAdmissionRepository;
use MicroHis\Admision\Persistence\PdoAdmissionRepository;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use PDOException;
use Tests\Feature\TrasladosAltas\TrasladosTestIdentity;
use Tests\TestCase;

/** API v1 de admisión con middlewares, matriz RBAC y guard de #1154 reales; HOSPITAL en memoria (PostgreSQL: AdmisionPostgresTest). */
final class AdmissionApiTest extends TestCase
{
    private const PACIENTE = '00000000-0000-4000-8000-0000000000a1';

    private const CAMA = '00000000-0000-4000-8000-0000000000b1';

    private const UCI = '00000000-0000-4000-8000-0000000000b2';

    private InMemoryAdmissionRepository $hospital;

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
        $this->hospital = new InMemoryAdmissionRepository([self::PACIENTE], [
            self::CAMA => ['bed_id' => self::CAMA, 'code' => 'A-101', 'type' => 'general', 'status' => 'available'],
            self::UCI => ['bed_id' => self::UCI, 'code' => 'UCI-1', 'type' => 'icu', 'status' => 'available'],
        ]);
        $this->app->instance(AdmissionRepository::class, $this->hospital);
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

    private function admitir(array $campos = []): TestResponse
    {
        return $this->postJson('/api/v1/admissions', $campos + ['patient_id' => self::PACIENTE, 'bed_id' => self::CAMA, 'reason' => 'Neumonía']);
    }

    public function test_identity_tenant_permission_and_hospital_guard_are_enforced(): void
    {
        TrasladosTestIdentity::como(null);
        $this->admitir()->assertStatus(401);
        TrasladosTestIdentity::como('recepcion');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])->admitir()->assertStatus(403)->assertJsonPath('code', 'TENANT_MISMATCH');
        // medico ve admisiones pero no tiene admisiones.registrar ni camas.ver en config/rbac.php.
        TrasladosTestIdentity::como('medico');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a'])->admitir()->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        $this->getJson('/api/v1/beds/available')->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        TrasladosTestIdentity::como('recepcion');
        TrasladosTestIdentity::$hospital = 'hospital-b';
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])->getJson('/api/v1/beds/available')->assertStatus(403)
            ->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_MISMATCH');
        $this->assertSame([], $this->hospital->admissions);
    }

    public function test_reception_lists_beds_and_admits_the_patient(): void
    {
        TrasladosTestIdentity::como('recepcion');
        $this->getJson('/api/v1/beds/available?type=icu')->assertOk()->assertJsonCount(1, 'data.beds')->assertJsonPath('data.beds.0.code', 'UCI-1');
        $stay = $this->withHeaders(['X-Request-ID' => 'req-admision-01'])->admitir(['assigned_by_user_ref_id' => 'suplantado'])
            ->assertStatus(201)->assertJsonPath('meta.request_id', 'req-admision-01')->assertJsonPath('data.bed_id', self::CAMA)
            ->assertHeader('Cache-Control', 'no-store, private')->json('data.bed_stay_id');
        // El actor auditado es la referencia local del JWT, no un campo del cuerpo.
        $this->assertSame(['admision.cama_asignada', '00000000-0000-4000-8000-0000000000f1', $stay], $this->hospital->audit[0]);
        $this->getJson('/api/v1/beds/available')->assertOk()->assertJsonCount(1, 'data.beds')->assertJsonPath('data.beds.0.code', 'UCI-1');
    }

    public function test_domain_rules_map_to_http_codes(): void
    {
        TrasladosTestIdentity::como('recepcion');
        $this->admitir(['reason' => '  '])->assertStatus(422)->assertJsonPath('error.code', 'REASON_REQUIRED');
        $this->admitir(['bed_type' => 'icu'])->assertStatus(422)->assertJsonPath('error.field', 'bed_type');
        $this->admitir(['bed_id' => '00000000-0000-4000-8000-0000000000b9'])->assertStatus(404)->assertJsonPath('error.code', 'BED_NOT_FOUND');
        $this->admitir(['patient_id' => 'no-es-uuid'])->assertStatus(404)->assertJsonPath('error.code', 'PATIENT_NOT_FOUND');
        $this->admitir()->assertStatus(201);
        $this->admitir(['bed_id' => self::UCI])->assertStatus(409)->assertJsonPath('error.code', 'PATIENT_ALREADY_ADMITTED');
        $this->assertCount(1, $this->hospital->admissions);
    }

    public function test_actor_without_local_reference_cannot_admit(): void
    {
        TrasladosTestIdentity::como('recepcion', '00000000-0000-4000-8000-00000000c0ff');
        $this->admitir()->assertStatus(403)->assertJsonPath('error.code', 'ACTOR_WITHOUT_LOCAL_REFERENCE');
        $this->assertSame('available', $this->hospital->beds[self::CAMA]['status']);
    }

    public function test_unavailable_hospital_answers_503_without_details(): void
    {
        config()->set('app.debug', false);
        TrasladosTestIdentity::como('recepcion');
        // Adaptador real con la conexión perezosa del provider: el fallo ocurre dentro del caso de uso, no al resolver el controller.
        $this->app->instance(AdmissionRepository::class, new PdoAdmissionRepository(static fn () => throw new PDOException('SQLSTATE[08006] password=secreto')));
        $this->getJson('/api/v1/beds/available')->assertStatus(503)->assertJsonPath('error.code', 'ADMISSIONS_UNAVAILABLE')->assertHeader('Retry-After', '5');
        $respuesta = $this->admitir()->assertStatus(503)->assertJsonPath('error.code', 'ADMISSIONS_UNAVAILABLE');
        $this->assertStringNotContainsString('secreto', $respuesta->getContent());
    }
}
