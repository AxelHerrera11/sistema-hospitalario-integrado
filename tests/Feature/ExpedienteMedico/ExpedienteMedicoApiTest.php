<?php

declare(strict_types=1);

namespace Tests\Feature\ExpedienteMedico;

use App\ExpedienteMedico\ExpedienteMedicoServiceProvider;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Contracts\Http\Kernel;
use MicroHis\ExpedienteMedico\Application\HospitalStores;
use MicroHis\ExpedienteMedico\Application\HospitalStoresProvider;
use MicroHis\ExpedienteMedico\Persistence\HospitalRegistry;
use MicroHis\Gobernanza\Domain\AuditEvent;
use MicroHis\Gobernanza\Domain\AuditEventReader;
use MicroHis\Gobernanza\Domain\AuditFilter;
use MicroHis\Gobernanza\Domain\AuditLogger;
use MicroHis\Gobernanza\Persistence\InMemoryAuditLog;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use PDO;
use Tests\TestCase;

/**
 * API v1 del expediente con middlewares, matriz RBAC, Authorize y RecordAuditEvent REALES. La base HOSPITAL es
 * SQLite con el esquema de prueba del micro-HIS (mismos nombres que la migración). El provider se carga desde
 * bootstrap/providers.php, como en la aplicación real.
 */
final class ExpedienteMedicoApiTest extends TestCase
{
    private const MEDICA = 'c1111111-1111-4111-8111-111111111111';
    private const ENFERMERA = 'c3333333-3333-4333-8333-333333333333';
    private const SIN_REFERENCIA = 'c9999999-9999-4999-8999-999999999999';
    private const LOCALES = [self::MEDICA => '11111111-1111-4111-8111-111111111111', self::ENFERMERA => '33333333-3333-4333-8333-333333333333'];

    private InMemoryAuditLog $audit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', ExpedienteTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', ExpedienteTestIdentity::class);
        $this->app->instance(RoleResolver::class, new ExpedienteTestIdentity);
        $this->app->instance(DenialRecorder::class, new ExpedienteTestIdentity);
        $this->audit = new InMemoryAuditLog;
        $this->app->instance(AuditLogger::class, $this->audit);
        $this->app->instance(AuditEventReader::class, $this->audit);

        $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec((string) file_get_contents(base_path('micro-his/expediente-medico/database/schema.sql')));
        $pdo->exec((string) file_get_contents(base_path('micro-his/expediente-medico/database/demo.sql')));
        $this->app->instance(HospitalStoresProvider::class, new HospitalRegistry(['hospital-demo' => static fn (): HospitalStores => expediente_pdo_stores($pdo)]));
        $this->app->instance(ResolutorActorLocal::class, new class(self::LOCALES) implements ResolutorActorLocal
        {
            public function __construct(private readonly array $locales)
            {
            }

            public function referencia(string $centralUserId): ?string
            {
                return $this->locales[$centralUserId] ?? null;
            }
        });
        $this->withHeaders(['X-Tenant-ID' => 'hospital-demo']);
    }

    public function test_treating_physician_reads_the_longitudinal_record_and_the_access_is_audited(): void
    {
        $this->assertArrayHasKey(ExpedienteMedicoServiceProvider::class, $this->app->getLoadedProviders());
        ExpedienteTestIdentity::as('medico', self::MEDICA);
        $this->getJson('/api/v1/patients/P-0001/medical-record')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('record.record_number', 'EXP-P-0001')
            ->assertJsonPath('summary.episodes', 3)
            ->assertJsonPath('episodes.0.type', 'inpatient');
        $events = array_map(static fn (AuditEvent $e): string => $e->eventName() . ':' . $e->actorId, $this->audit->search('hospital-demo', new AuditFilter)->events);
        $this->assertSame(['expediente_medico.consultado:' . self::MEDICA], $events);
    }

    public function test_identity_tenant_and_permission_are_enforced_before_the_micro_his(): void
    {
        ExpedienteTestIdentity::as(null, self::MEDICA);
        $this->getJson('/api/v1/patients/P-0001/medical-record')->assertStatus(401);
        ExpedienteTestIdentity::as('medico', self::MEDICA);
        $this->withHeaders(['X-Tenant-ID' => 'hospital-norte'])->getJson('/api/v1/patients/P-0001/medical-record')
            ->assertStatus(403)->assertJsonPath('code', 'TENANT_MISMATCH');
        foreach (['recepcion', 'admin_hospital'] as $role) {
            ExpedienteTestIdentity::as($role, self::MEDICA);
            $this->withHeaders(['X-Tenant-ID' => 'hospital-demo'])->getJson('/api/v1/patients/P-0001/medical-record')
                ->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        }
        $this->assertSame(0, $this->audit->count());
    }

    public function test_clinical_relationship_and_local_reference_are_required(): void
    {
        ExpedienteTestIdentity::as('enfermeria', self::ENFERMERA);
        $denied = $this->getJson('/api/v1/patients/P-0001/medical-record')->assertStatus(403)->assertJsonPath('code', 'SIN_VINCULO_ASISTENCIAL');
        $this->assertStringNotContainsString('Gómez', (string) $denied->getContent());
        ExpedienteTestIdentity::as('medico', self::SIN_REFERENCIA);
        $this->getJson('/api/v1/patients/P-0001/medical-record')->assertStatus(403)->assertJsonPath('code', 'ACTOR_SIN_REFERENCIA_LOCAL');
        ExpedienteTestIdentity::as('medico', self::MEDICA);
        $this->getJson('/api/v1/patients/' . str_repeat('P', 41) . '/medical-record')->assertStatus(422)->assertJsonPath('code', 'CODIGO_PACIENTE_INVALIDO');
    }

    public function test_opening_is_not_published_until_rbac_catalogues_its_permission(): void
    {
        ExpedienteTestIdentity::as('medico', self::MEDICA);
        $this->postJson('/api/v1/patients/P-0002/medical-record')->assertStatus(405);
    }
}
