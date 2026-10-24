<?php

declare(strict_types=1);

namespace Tests\Feature\Alertas;

use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Testing\TestResponse;
use MicroHis\Alertas\Application\AlertRepository;
use MicroHis\Alertas\Persistence\InMemoryAlertRepository;
use MicroHis\Alertas\Persistence\PdoAlertRepository;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use PDOException;
use Tests\Feature\TrasladosAltas\TrasladosTestIdentity;
use Tests\TestCase;

/** API v1 de alertas críticas con middlewares, matriz RBAC y guard de #1154 reales; HOSPITAL en memoria (PostgreSQL: AlertasPostgresTest). */
final class CriticalAlertApiTest extends TestCase
{
    private const MEDICA = '00000000-0000-4000-8000-0000000000f1'; // referencia local del usuario c0a1

    private const PRUEBA = '00000000-0000-4000-8000-0000000000d1';

    private const PACIENTE = '00000000-0000-4000-8000-0000000000a1';

    private const EPISODIO = '00000000-0000-4000-8000-0000000000c1';

    private InMemoryAlertRepository $hospital;

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
            'lab_test_id' => self::PRUEBA, 'patient_id' => self::PACIENTE, 'episode_id' => self::EPISODIO, 'value' => 32]);
    }

    public function test_identity_tenant_permission_and_hospital_guard_are_enforced(): void
    {
        TrasladosTestIdentity::como(null);
        $this->getJson('/api/v1/critical-alerts')->assertStatus(401);
        TrasladosTestIdentity::como('bioquimico');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])->detectar()->assertStatus(403)->assertJsonPath('code', 'TENANT_MISMATCH');
        // El médico ve y acusa pero no publica resultados; el bioquímico publica pero no tiene bandeja; enfermería sigue en pending.
        TrasladosTestIdentity::como('medico');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-a'])->detectar()->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        $this->getJson('/api/v1/critical-alerts/service-head')->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        TrasladosTestIdentity::como('enfermeria');
        $this->getJson('/api/v1/critical-alerts')->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        TrasladosTestIdentity::como('bioquimico');
        TrasladosTestIdentity::$hospital = 'hospital-b';
        $this->withHeaders(['X-Tenant-ID' => 'hospital-b'])->detectar()->assertStatus(403)->assertJsonPath('error', 'HOSPITAL_DATABASE_TENANT_MISMATCH');
        $this->assertSame([], $this->hospital->rows);
    }

    public function test_detection_inbox_and_acknowledgement_with_read_back(): void
    {
        TrasladosTestIdentity::como('bioquimico', '00000000-0000-4000-8000-00000000c0b2');
        $id = $this->detectar()->assertStatus(201)->assertJsonPath('data.alert.direction', 'low')
            ->assertJsonPath('data.alert.recipient_user_ref_id', self::MEDICA)->json('data.alert.critical_alert_id');
        $this->detectar()->assertOk()->assertJsonPath('data.outcome', 'duplicate');
        $this->detectar(['result_version_id' => '00000000-0000-4000-8000-0000000000e2', 'value' => 95])->assertOk()->assertJsonPath('data.outcome', 'not_critical');
        TrasladosTestIdentity::como('medico');
        $this->getJson('/api/v1/critical-alerts')->assertOk()->assertJsonCount(1, 'data.alerts')->assertJsonPath('data.alerts.0.critical_alert_id', $id);
        $lectura = ['patient_id' => self::PACIENTE, 'lab_test_id' => self::PRUEBA, 'value' => 32];
        $this->postJson("/api/v1/critical-alerts/{$id}/acknowledge", ['read_back' => ['value' => 23] + $lectura])->assertStatus(422)->assertJsonPath('error.code', 'READ_BACK_MISMATCH');
        $this->postJson("/api/v1/critical-alerts/{$id}/acknowledge", ['read_back' => $lectura])->assertOk()->assertJsonPath('data.alert.status', 'acknowledged')
            ->assertJsonPath('data.alert.acknowledged_by_user_ref_id', self::MEDICA);
        $this->postJson("/api/v1/critical-alerts/{$id}/acknowledge", ['read_back' => $lectura])->assertStatus(409)->assertJsonPath('error.code', 'ALERT_ALREADY_ACKNOWLEDGED');
        $this->getJson('/api/v1/critical-alerts')->assertOk()->assertJsonCount(0, 'data.alerts');
    }

    public function test_alert_without_attending_goes_to_the_service_head_queue(): void
    {
        TrasladosTestIdentity::como('bioquimico', '00000000-0000-4000-8000-00000000c0b2');
        $this->detectar(['episode_id' => '00000000-0000-4000-8000-0000000000c9', 'value' => 500])->assertStatus(201)
            ->assertJsonPath('data.alert.recipient_user_ref_id', null)->assertJsonPath('data.alert.escalation_level', 1);
        TrasladosTestIdentity::como('jefatura_medica', '00000000-0000-4000-8000-00000000c0b2'); // no es la médica tratante
        $id = $this->getJson('/api/v1/critical-alerts/service-head')->assertOk()->assertJsonCount(1, 'data.alerts')->assertJsonPath('data.alerts.0.direction', 'high')
            ->json('data.alerts.0.critical_alert_id');
        $lectura = ['read_back' => ['patient_id' => self::PACIENTE, 'lab_test_id' => self::PRUEBA, 'value' => 500]];
        // Por la ruta común la jefatura no es destinataria ni cuida al paciente; por la de supervisión sí puede acusar.
        $this->postJson("/api/v1/critical-alerts/{$id}/acknowledge", $lectura)->assertStatus(403)->assertJsonPath('error.code', 'NO_CARE_RELATION');
        $this->postJson("/api/v1/critical-alerts/{$id}/acknowledge-as-service-head", $lectura)->assertOk()->assertJsonPath('data.alert.status', 'acknowledged');
        TrasladosTestIdentity::como('medico');
        $this->postJson("/api/v1/critical-alerts/{$id}/acknowledge-as-service-head", $lectura)->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
    }

    public function test_invalid_events_and_unknown_alerts_are_rejected(): void
    {
        TrasladosTestIdentity::como('bioquimico', '00000000-0000-4000-8000-00000000c0b2');
        $this->detectar(['episode_id' => 'EP-1'])->assertStatus(422)->assertJsonPath('error.field', 'episode_id');
        $this->detectar(['value' => 'alto'])->assertStatus(422)->assertJsonPath('error.code', 'EVENT_INVALID');
        TrasladosTestIdentity::como('medico', '00000000-0000-4000-8000-00000000c0ff');
        $this->getJson('/api/v1/critical-alerts')->assertStatus(403)->assertJsonPath('error.code', 'ACTOR_WITHOUT_LOCAL_REFERENCE');
        TrasladosTestIdentity::como('medico');
        $this->postJson('/api/v1/critical-alerts/no-es-uuid/acknowledge', [])->assertStatus(404)->assertJsonPath('error.code', 'ALERT_NOT_FOUND');
        $this->postJson('/api/v1/critical-alerts/00000000-0000-4000-8000-0000000000ff/acknowledge', [])->assertStatus(404);
    }

    public function test_unavailable_hospital_answers_503_without_details(): void
    {
        config()->set('app.debug', false);
        TrasladosTestIdentity::como('bioquimico', '00000000-0000-4000-8000-00000000c0b2');
        // Adaptador real con la conexión perezosa del provider: el fallo ocurre dentro del caso de uso, no al resolver el controller.
        $this->app->instance(AlertRepository::class, new PdoAlertRepository(static fn () => throw new PDOException('SQLSTATE[08006] password=secreto')));
        $respuesta = $this->detectar()->assertStatus(503)->assertJsonPath('error.code', 'CRITICAL_ALERTS_UNAVAILABLE')->assertHeader('Retry-After', '5');
        $this->assertStringNotContainsString('secreto', $respuesta->getContent());
    }
}
