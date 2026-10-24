<?php

declare(strict_types=1);

namespace Tests\Feature\Alergias;

use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Testing\TestResponse;
use MicroHis\Alergias\Application\AllergyRepository;
use MicroHis\Alergias\Persistence\InMemoryAllergyRepository;
use MicroHis\Alergias\Persistence\PdoAllergyRepository;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use PDOException;
use Tests\Feature\TrasladosAltas\TrasladosTestIdentity;
use Tests\TestCase;

/** API v1 de alergias con middlewares y matriz RBAC reales; HOSPITAL en memoria. El resolver real de actor y PostgreSQL: AlergiasPostgresTest. */
final class AllergyApiTest extends TestCase
{
    private const PACIENTE = '00000000-0000-4000-8000-0000000000a1';

    private const ACTOR = '00000000-0000-4000-8000-0000000000f1';

    private InMemoryAllergyRepository $hospital;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', TrasladosTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', TrasladosTestIdentity::class);
        $this->app->instance(RoleResolver::class, new TrasladosTestIdentity);
        $this->app->instance(DenialRecorder::class, new TrasladosTestIdentity);
        $this->hospital = new InMemoryAllergyRepository([self::PACIENTE]);
        $this->app->instance(AllergyRepository::class, $this->hospital);
        $this->app->instance(ResolutorActorLocal::class, new class implements ResolutorActorLocal
        {
            public function referencia(string $centralUserId): ?string
            {
                return $centralUserId === '00000000-0000-4000-8000-00000000c0a1' ? '00000000-0000-4000-8000-0000000000f1' : null;
            }
        });
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    private function registrar(array $campos = []): TestResponse
    {
        return $this->postJson('/api/v1/patients/'.self::PACIENTE.'/allergies', $campos + ['allergen_code' => 'PENICILINA',
            'allergen_name' => 'Penicilina', 'reaction' => 'Anafilaxia', 'allergy_type' => 'allergy', 'severity' => 'life_threatening']);
    }

    public function test_identity_tenant_and_permission_are_enforced(): void
    {
        TrasladosTestIdentity::como(null);
        $this->registrar()->assertStatus(401);
        TrasladosTestIdentity::como('medico');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])->registrar()->assertStatus(403)->assertJsonPath('code', 'TENANT_MISMATCH');
        // recepcion no tiene alergias.registrar ni alergias.ver en config/rbac.php.
        TrasladosTestIdentity::como('recepcion');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a'])->registrar()->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        $this->getJson('/api/v1/patients/'.self::PACIENTE.'/allergy-alert')->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        $this->assertSame([], $this->hospital->rows);
    }

    public function test_register_then_alert_shows_critical_banner(): void
    {
        TrasladosTestIdentity::como('medico');
        $this->getJson('/api/v1/patients/'.self::PACIENTE.'/allergy-alert')->assertOk()->assertJsonPath('data.level', 'none');
        $id = $this->withHeaders(['X-Request-ID' => 'req-alergia-01'])->registrar(['verified_by_user_ref_id' => 'suplantado'])
            ->assertStatus(201)->assertJsonPath('meta.request_id', 'req-alergia-01')->assertJsonPath('data.status', 'active')
            ->assertHeader('Cache-Control', 'no-store, private')->json('data.allergy_id');
        // El actor auditado es la referencia local del JWT, no un campo del cuerpo.
        $this->assertSame(['alergia.registrada', self::ACTOR, $id], $this->hospital->audit[0]);
        TrasladosTestIdentity::como('enfermeria');
        $this->getJson('/api/v1/patients/'.self::PACIENTE.'/allergy-alert')->assertOk()->assertJsonPath('data.level', 'critical')
            ->assertJsonPath('data.allergies.0.allergen_name', 'Penicilina');
    }

    public function test_domain_rules_map_to_http_codes(): void
    {
        TrasladosTestIdentity::como('medico');
        $this->registrar()->assertStatus(201);
        $this->registrar(['allergen_name' => 'penicilina'])->assertStatus(409)->assertJsonPath('error.code', 'ALLERGY_ALREADY_ACTIVE');
        $this->registrar(['severity' => 'grave'])->assertStatus(422)->assertJsonPath('error.field', 'severity');
        $this->registrar(['allergen_code' => ' ', 'allergen_name' => ''])->assertStatus(422)->assertJsonPath('error.code', 'ALLERGEN_REQUIRED');
        $otro = '00000000-0000-4000-8000-0000000000a9';
        $this->postJson("/api/v1/patients/{$otro}/allergies", ['allergen_name' => 'Látex', 'allergy_type' => 'allergy', 'severity' => 'mild'])
            ->assertStatus(404)->assertJsonPath('error.code', 'PATIENT_NOT_FOUND');
        $this->getJson("/api/v1/patients/{$otro}/allergy-alert")->assertStatus(404)->assertJsonPath('error.code', 'PATIENT_NOT_FOUND');
        $this->getJson('/api/v1/patients/no-es-uuid/allergy-alert')->assertStatus(404)->assertJsonPath('error.code', 'PATIENT_NOT_FOUND');
        $this->assertCount(1, $this->hospital->rows);
    }

    public function test_unavailable_hospital_answers_503_and_never_no_allergies(): void
    {
        config()->set('app.debug', false);
        TrasladosTestIdentity::como('medico');
        // Adaptador real con la conexión perezosa del provider: el fallo ocurre dentro del caso de uso, no al resolver el controller.
        $this->app->instance(AllergyRepository::class, new PdoAllergyRepository(static fn () => throw new PDOException('SQLSTATE[08006] password=secreto')));
        $alerta = $this->getJson('/api/v1/patients/'.self::PACIENTE.'/allergy-alert')->assertStatus(503)
            ->assertJsonPath('error.code', 'ALLERGIES_UNAVAILABLE')->assertHeader('Retry-After', '5');
        $this->assertStringNotContainsString('Sin alergias', $alerta->getContent());
        $registro = $this->registrar()->assertStatus(503)->assertJsonPath('error.code', 'ALLERGIES_UNAVAILABLE');
        $this->assertStringNotContainsString('secreto', $registro->getContent());
    }

    public function test_failed_read_keeps_the_domain_safe_warning(): void
    {
        TrasladosTestIdentity::como('enfermeria');
        $this->hospital->failReads = true;
        $this->getJson('/api/v1/patients/'.self::PACIENTE.'/allergy-alert')->assertStatus(503)
            ->assertJsonPath('error.message', 'Alergias no disponibles: verificar con el paciente antes de prescribir');
    }
}
