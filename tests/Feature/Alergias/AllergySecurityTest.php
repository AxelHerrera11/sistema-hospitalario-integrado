<?php

declare(strict_types=1);

namespace Tests\Feature\Alergias;

use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use MicroHis\Alergias\Application\AllergyRepository;
use MicroHis\Alergias\Persistence\InMemoryAllergyRepository;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use Tests\Feature\TrasladosAltas\TrasladosTestIdentity;
use Tests\TestCase;

/** Semana 17: permisos por rol contra config/rbac.php y datos sensibles (amenazas A01–A13 de docs/alergias/semana-17). */
final class AllergySecurityTest extends TestCase
{
    private const PACIENTE = '00000000-0000-4000-8000-0000000000a1';

    private const OTRO_PACIENTE = '00000000-0000-4000-8000-0000000000a2';

    private const ACTOR = '00000000-0000-4000-8000-0000000000f1';

    private InMemoryAllergyRepository $hospital;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', TrasladosTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', HospitalTenantIdentity::class);
        $this->app->instance(RoleResolver::class, new TrasladosTestIdentity);
        $this->app->instance(DenialRecorder::class, new TrasladosTestIdentity);
        $this->hospital = new InMemoryAllergyRepository([self::PACIENTE, self::OTRO_PACIENTE]);
        $this->app->instance(AllergyRepository::class, $this->hospital);
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
        return $this->postJson('/api/v1/patients/'.self::PACIENTE.'/allergies', $campos + ['allergen_name' => 'Penicilina',
            'reaction' => 'Anafilaxia', 'allergy_type' => 'allergy', 'severity' => 'life_threatening']);
    }

    public function test_every_role_gets_exactly_what_the_rbac_matrix_grants(): void
    {
        $permitidos = ['ver' => [], 'registrar' => []];
        foreach (array_keys(config('rbac.roles')) as $rol) {
            TrasladosTestIdentity::como($rol);
            $permisos = config("rbac.roles.{$rol}");
            $lectura = $this->getJson('/api/v1/patients/'.self::PACIENTE.'/allergy-alert');
            $escritura = $this->registrar(['allergen_name' => "Alérgeno {$rol}"]);
            in_array('alergias.ver', $permisos, true) ? $lectura->assertOk() : $lectura->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
            in_array('alergias.registrar', $permisos, true) ? $escritura->assertStatus(201) : $escritura->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
            $lectura->isOk() && $permitidos['ver'][] = $rol;
            $escritura->status() === 201 && $permitidos['registrar'][] = $rol;
        }
        // Si alguien amplía la matriz, esta prueba obliga a revisar la amenaza A03 de la matriz.
        $this->assertSame(['superadmin', 'medico', 'enfermeria'], $permitidos['ver']);
        $this->assertSame(['superadmin', 'medico', 'enfermeria'], $permitidos['registrar']);
        $this->assertCount(3, $this->hospital->rows);
    }

    public function test_body_cannot_choose_patient_actor_status_or_hospital(): void
    {
        TrasladosTestIdentity::como('medico');
        $respuesta = $this->registrar(['local_patient_id' => self::OTRO_PACIENTE, 'status' => 'resolved', 'verified_by_user_ref_id' => 'suplantado',
            'hospital_id' => 'hospital-b', 'allergen_name' => '<img src=x onerror=alert(1)>'])->assertStatus(201)
            ->assertJsonPath('data.local_patient_id', self::PACIENTE)->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.allergen_name', '<img src=x onerror=alert(1)>');
        // El texto se guarda tal cual (la pantalla lo interpola, sin v-html) y el autor es la referencia local del JWT.
        $this->assertSame(['alergia.registrada', self::ACTOR, $respuesta->json('data.allergy_id')], $this->hospital->audit[0]);
        $this->assertSame([], $this->hospital->activeFor(self::OTRO_PACIENTE));
    }

    public function test_oversized_text_is_rejected_before_reaching_hospital(): void
    {
        TrasladosTestIdentity::como('medico');
        $this->registrar(['allergen_name' => str_repeat('a', 201)])->assertStatus(422)
            ->assertJsonPath('error.code', 'TEXT_TOO_LONG')->assertJsonPath('error.field', 'allergen_name');
        $this->registrar(['reaction' => str_repeat('r', 100_000)])->assertStatus(422)->assertJsonPath('error.field', 'reaction');
        $this->registrar(['allergen_code' => str_repeat('C', 51)])->assertStatus(422)->assertJsonPath('error.field', 'allergen_code');
        $this->assertSame([], $this->hospital->rows);
        $this->registrar(['allergen_name' => str_repeat('ñ', 200)])->assertStatus(201);
    }

    public function test_untrusted_request_id_is_replaced(): void
    {
        TrasladosTestIdentity::como('enfermeria');
        foreach (['<script>alert(1)</script>', str_repeat('a', 65), "req\r\nX-Inyectada: 1"] as $cabecera) {
            $id = $this->withHeaders(['X-Request-ID' => $cabecera])->getJson('/api/v1/patients/'.self::PACIENTE.'/allergy-alert')
                ->assertOk()->json('meta.request_id');
            $this->assertMatchesRegularExpression('/^req-[0-9a-f]{16}$/', $id);
        }
    }

    public function test_logs_never_contain_allergen_reaction_or_patient(): void
    {
        $registros = [];
        Event::listen(MessageLogged::class, function (MessageLogged $evento) use (&$registros): void {
            $registros[] = $evento->message.' '.json_encode($evento->context);
        });
        TrasladosTestIdentity::como('medico');
        $this->registrar()->assertStatus(201);
        $this->getJson('/api/v1/patients/'.self::PACIENTE.'/allergy-alert')->assertOk();
        $this->hospital->failReads = true;
        $this->getJson('/api/v1/patients/'.self::PACIENTE.'/allergy-alert')->assertStatus(503);

        $this->assertNotEmpty($registros);
        $todo = implode("\n", $registros);
        foreach (['Penicilina', 'Anafilaxia', self::PACIENTE, 'life_threatening'] as $sensible) {
            $this->assertStringNotContainsString($sensible, $todo);
        }
    }
}
