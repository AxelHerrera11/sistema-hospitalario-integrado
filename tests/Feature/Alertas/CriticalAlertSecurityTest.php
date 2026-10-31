<?php

declare(strict_types=1);

namespace Tests\Feature\Alertas;

use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use MicroHis\Alertas\Application\AlertRepository;
use MicroHis\Alertas\Persistence\InMemoryAlertRepository;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use Tests\Feature\TrasladosAltas\TrasladosTestIdentity;
use Tests\TestCase;

/** Semana 17: permisos por rol contra config/rbac.php, relación de cuidado, entradas hostiles y datos sensibles (amenazas C01–C14 de docs/alertas-criticas/semana-17). */
final class CriticalAlertSecurityTest extends TestCase
{
    private const MEDICA = '00000000-0000-4000-8000-0000000000f1'; // referencia local del usuario c0a1, médica tratante

    private const OTRO_MEDICO = '00000000-0000-4000-8000-00000000c0b2'; // usuario central con referencia f2, sin relación de cuidado

    private const PRUEBA = '00000000-0000-4000-8000-0000000000d1';

    private const PACIENTE = '00000000-0000-4000-8000-0000000000a1';

    private const EPISODIO = '00000000-0000-4000-8000-0000000000c1';

    private InMemoryAlertRepository $hospital;

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
        $this->hospital = new InMemoryAlertRepository([self::PRUEBA => ['critical_low' => 40.0, 'critical_high' => 400.0]], [[self::EPISODIO, self::PACIENTE, self::MEDICA]]);
        $this->app->instance(AlertRepository::class, $this->hospital);
        $this->app->instance(ResolutorActorLocal::class, new class implements ResolutorActorLocal
        {
            public function referencia(string $centralUserId): ?string
            {
                return ['00000000-0000-4000-8000-00000000c0a1' => '00000000-0000-4000-8000-0000000000f1',
                    '00000000-0000-4000-8000-00000000c0b2' => '00000000-0000-4000-8000-0000000000f2'][$centralUserId] ?? null;
            }
        });
        TrasladosTestIdentity::$hospital = 'hospital-a';
        config()->set('tenants.hospital_database_tenant_id', 'hospital-a');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a']);
    }

    private function detectar(array $campos = []): TestResponse
    {
        return $this->postJson('/api/v1/critical-alerts/result-validated', $campos + ['result_version_id' => '00000000-0000-4000-8000-0000000000e1',
            'lab_test_id' => self::PRUEBA, 'patient_id' => self::PACIENTE, 'episode_id' => self::EPISODIO, 'value' => 31.7]);
    }

    public function test_every_role_gets_exactly_what_the_rbac_matrix_grants_on_the_five_routes(): void
    {
        $rutas = [
            'lab-results.validate' => fn (int $i): TestResponse => $this->detectar(['result_version_id' => "00000000-0000-4000-8000-0000000000e{$i}"]),
            'alertas.ver' => fn (): TestResponse => $this->getJson('/api/v1/critical-alerts'),
            'alertas.supervisar' => fn (): TestResponse => $this->getJson('/api/v1/critical-alerts/service-head'),
            // Una alerta inexistente: con permiso responde 404 del controller; sin permiso, 403 del middleware antes de buscarla.
            'alertas.acusar' => fn (): TestResponse => $this->postJson('/api/v1/critical-alerts/00000000-0000-4000-8000-0000000000ff/acknowledge', []),
            'alertas.supervisar:acuse' => fn (): TestResponse => $this->postJson('/api/v1/critical-alerts/00000000-0000-4000-8000-0000000000ff/acknowledge-as-service-head', []),
        ];
        $permitidos = array_fill_keys(array_keys($rutas), []);
        foreach (array_keys(config('rbac.roles')) as $i => $rol) {
            TrasladosTestIdentity::como($rol);
            foreach ($rutas as $ruta => $llamar) {
                $respuesta = $llamar($i);
                if (in_array(explode(':', $ruta)[0], config("rbac.roles.{$rol}"), true)) {
                    $this->assertContains($respuesta->status(), [200, 201, 404], "{$rol} en {$ruta}");
                    $permitidos[$ruta][] = $rol;
                } else {
                    $respuesta->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
                }
            }
        }
        // enfermeria sigue en 'pending' para ver y acusar; si alguien lo activa, esta prueba obliga a revisar C03.
        $this->assertSame(['superadmin', 'bioquimico'], $permitidos['lab-results.validate']);
        $this->assertSame(['superadmin', 'medico', 'jefatura_medica'], $permitidos['alertas.ver']);
        $this->assertSame(['superadmin', 'medico', 'jefatura_medica'], $permitidos['alertas.acusar']);
        $this->assertSame(['superadmin', 'jefatura_medica'], $permitidos['alertas.supervisar']);
        $this->assertSame(['superadmin', 'jefatura_medica'], $permitidos['alertas.supervisar:acuse']);
    }

    public function test_a_doctor_without_care_relation_neither_sees_nor_acknowledges(): void
    {
        TrasladosTestIdentity::como('bioquimico', self::OTRO_MEDICO);
        $id = $this->detectar()->assertStatus(201)->json('data.alert.critical_alert_id');
        TrasladosTestIdentity::como('medico', self::OTRO_MEDICO);
        $this->getJson('/api/v1/critical-alerts')->assertOk()->assertJsonCount(0, 'data.alerts');
        $this->postJson("/api/v1/critical-alerts/{$id}/acknowledge", ['read_back' => ['patient_id' => self::PACIENTE, 'lab_test_id' => self::PRUEBA, 'value' => 31.7]])
            ->assertStatus(403)->assertJsonPath('error.code', 'NO_CARE_RELATION');
        TrasladosTestIdentity::como('medico');
        $this->getJson('/api/v1/critical-alerts')->assertOk()->assertJsonCount(1, 'data.alerts')->assertJsonPath('data.alerts.0.status', 'open');
    }

    public function test_body_cannot_choose_recipient_level_or_acknowledger(): void
    {
        TrasladosTestIdentity::como('bioquimico', self::OTRO_MEDICO);
        $id = $this->detectar(['recipient_user_ref_id' => '00000000-0000-4000-8000-0000000000f2', 'escalation_level' => 2, 'status' => 'acknowledged'])
            ->assertStatus(201)->assertJsonPath('data.alert.recipient_user_ref_id', self::MEDICA)->assertJsonPath('data.alert.escalation_level', 0)
            ->assertJsonPath('data.alert.status', 'open')->json('data.alert.critical_alert_id');
        TrasladosTestIdentity::como('medico');
        $this->postJson("/api/v1/critical-alerts/{$id}/acknowledge", ['acknowledged_by_user_ref_id' => '00000000-0000-4000-8000-0000000000f2',
            'read_back' => ['patient_id' => self::PACIENTE, 'lab_test_id' => self::PRUEBA, 'value' => 31.7]])
            ->assertOk()->assertJsonPath('data.alert.acknowledged_by_user_ref_id', self::MEDICA);
    }

    public function test_hostile_input_never_answers_500(): void
    {
        config()->set('app.debug', false);
        TrasladosTestIdentity::como('bioquimico', self::OTRO_MEDICO);
        $this->detectar(['result_version_id' => ['x']])->assertStatus(422)->assertJsonPath('error.field', 'result_version_id');
        $this->detectar(['patient_id' => ['a' => 1]])->assertStatus(422)->assertJsonPath('error.field', 'patient_id');
        $this->detectar(['value' => '1e400'])->assertStatus(422)->assertJsonPath('error.field', 'value');
        $id = $this->detectar()->assertStatus(201)->json('data.alert.critical_alert_id');
        TrasladosTestIdentity::como('medico');
        $this->postJson("/api/v1/critical-alerts/{$id}/acknowledge", ['read_back' => 'texto'])->assertStatus(422);
        $this->postJson("/api/v1/critical-alerts/{$id}/acknowledge", ['read_back' => ['patient_id' => ['x'], 'value' => ['y']]])->assertStatus(422);
        $this->getJson('/api/v1/critical-alerts')->assertOk()->assertJsonPath('data.alerts.0.status', 'open');
    }

    public function test_logs_never_contain_patient_or_value_and_untrusted_trace_is_replaced(): void
    {
        $registros = [];
        Event::listen(MessageLogged::class, function (MessageLogged $evento) use (&$registros): void {
            $registros[] = $evento->message.' '.json_encode($evento->context);
        });
        TrasladosTestIdentity::como('bioquimico', self::OTRO_MEDICO);
        $respuesta = $this->withHeaders(['X-Request-ID' => "<script>\r\nX-Inyectada: 1"])->detectar()->assertStatus(201)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertMatchesRegularExpression('/^req-[0-9a-f]{16}$/', $respuesta->json('meta.request_id'));

        $this->assertNotEmpty($registros);
        foreach ([self::PACIENTE, self::EPISODIO, '31.7'] as $sensible) {
            $this->assertStringNotContainsString($sensible, implode("\n", $registros));
        }
    }
}
