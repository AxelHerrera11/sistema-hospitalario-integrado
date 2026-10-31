<?php

declare(strict_types=1);

namespace Tests\Feature\Admision;

use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use MicroHis\Admision\Application\AdmissionRepository;
use MicroHis\Admision\Persistence\InMemoryAdmissionRepository;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use Tests\Feature\TrasladosAltas\TrasladosTestIdentity;
use Tests\TestCase;

/** Semana 17: permisos por rol contra config/rbac.php, entradas hostiles y datos sensibles (amenazas D01–D14 de docs/admision-camas/semana-17). */
final class AdmissionSecurityTest extends TestCase
{
    private const ACTOR = '00000000-0000-4000-8000-0000000000f1';

    private InMemoryAdmissionRepository $hospital;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(Kernel::class);
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
        // Un paciente y una cama por rol, para que cada rol autorizado pueda admitir sin chocar con el anterior.
        $camas = [];
        foreach (range(1, 9) as $i) {
            $camas[self::cama($i)] = ['bed_id' => self::cama($i), 'code' => "A-10{$i}", 'type' => 'general', 'status' => 'available'];
        }
        $this->hospital = new InMemoryAdmissionRepository(array_map(self::paciente(...), range(1, 9)), $camas);
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

    private static function paciente(int $i): string
    {
        return "00000000-0000-4000-8000-0000000000a{$i}";
    }

    private static function cama(int $i): string
    {
        return "00000000-0000-4000-8000-0000000000b{$i}";
    }

    private function admitir(array $campos = []): TestResponse
    {
        return $this->postJson('/api/v1/admissions', $campos + ['patient_id' => self::paciente(1), 'bed_id' => self::cama(1), 'reason' => 'Neumonía grave']);
    }

    public function test_every_role_gets_exactly_what_the_rbac_matrix_grants(): void
    {
        $permitidos = ['camas' => [], 'admitir' => []];
        foreach (array_keys(config('rbac.roles')) as $i => $rol) {
            TrasladosTestIdentity::como($rol);
            $permisos = config("rbac.roles.{$rol}");
            $lectura = $this->getJson('/api/v1/beds/available');
            $escritura = $this->admitir(['patient_id' => self::paciente($i + 1), 'bed_id' => self::cama($i + 1)]);
            in_array('camas.ver', $permisos, true) ? $lectura->assertOk() : $lectura->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
            in_array('admisiones.registrar', $permisos, true) ? $escritura->assertStatus(201) : $escritura->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
            $lectura->isOk() && $permitidos['camas'][] = $rol;
            $escritura->status() === 201 && $permitidos['admitir'][] = $rol;
        }
        // Si alguien amplía la matriz, esta prueba obliga a revisar la amenaza D03 de la matriz.
        $this->assertSame(['superadmin', 'recepcion'], $permitidos['camas']);
        $this->assertSame(['superadmin', 'recepcion'], $permitidos['admitir']);
        $this->assertCount(2, $this->hospital->admissions);
    }

    public function test_body_cannot_choose_actor_start_time_or_episode(): void
    {
        TrasladosTestIdentity::como('recepcion');
        $respuesta = $this->admitir(['assigned_by_user_ref_id' => 'suplantado', 'started_at' => '1999-01-01T00:00:00Z',
            'care_episode_id' => '00000000-0000-4000-8000-0000000000ee', 'hospital_id' => 'hospital-b'])->assertStatus(201);
        $this->assertNotSame('1999-01-01T00:00:00Z', $respuesta->json('data.started_at'));
        $this->assertNotSame('00000000-0000-4000-8000-0000000000ee', $respuesta->json('data.care_episode_id'));
        $this->assertSame(['admision.cama_asignada', self::ACTOR, $respuesta->json('data.bed_stay_id')], $this->hospital->audit[0]);
    }

    public function test_hostile_input_never_answers_500(): void
    {
        config()->set('app.debug', false);
        TrasladosTestIdentity::como('recepcion');
        $this->admitir(['patient_id' => ['x']])->assertStatus(404)->assertJsonPath('error.field', 'patient_id');
        $this->admitir(['bed_id' => ['b' => 1]])->assertStatus(404)->assertJsonPath('error.field', 'bed_id');
        $this->admitir(['reason' => ['Neumonía']])->assertStatus(422)->assertJsonPath('error.code', 'REASON_REQUIRED');
        $this->admitir(['reason' => str_repeat('r', 100_000)])->assertStatus(422)->assertJsonPath('error.code', 'TEXT_TOO_LONG');
        $this->getJson('/api/v1/beds/available?type[]=icu')->assertOk();
        $this->assertSame([], $this->hospital->admissions);
        $this->admitir(['reason' => str_repeat('ó', 500)])->assertStatus(201);
    }

    public function test_bed_list_exposes_no_patient_data_and_untrusted_trace_is_replaced(): void
    {
        TrasladosTestIdentity::como('recepcion');
        $this->admitir()->assertStatus(201);
        $respuesta = $this->withHeaders(['X-Request-ID' => "<script>\r\nX-Inyectada: 1"])->getJson('/api/v1/beds/available')
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(['bed_id', 'code', 'type'], array_keys($respuesta->json('data.beds.0')));
        $this->assertStringNotContainsString(self::paciente(1), $respuesta->getContent());
        $this->assertMatchesRegularExpression('/^req-[0-9a-f]{16}$/', $respuesta->json('meta.request_id'));
    }

    public function test_logs_never_contain_reason_or_patient(): void
    {
        $registros = [];
        Event::listen(MessageLogged::class, function (MessageLogged $evento) use (&$registros): void {
            $registros[] = $evento->message.' '.json_encode($evento->context);
        });
        TrasladosTestIdentity::como('recepcion');
        $this->admitir()->assertStatus(201);
        $this->hospital->failWrites = true;
        $this->admitir(['patient_id' => self::paciente(2), 'bed_id' => self::cama(2)])->assertStatus(503);

        $this->assertCount(2, $registros);
        foreach (['Neumonía', self::paciente(1), self::paciente(2)] as $sensible) {
            $this->assertStringNotContainsString($sensible, implode("\n", $registros));
        }
    }
}
