<?php

declare(strict_types=1);

namespace Tests\Feature\ExpedienteMedico;

use App\ExpedienteMedico\CareAssignments;
use App\TrasladosAltas\ResolutorActorLocal;
use Illuminate\Contracts\Http\Kernel;
use MicroHis\ExpedienteMedico\Application\HospitalStores;
use MicroHis\ExpedienteMedico\Application\HospitalStoresProvider;
use MicroHis\ExpedienteMedico\Persistence\HospitalRegistry;
use MicroHis\Gobernanza\Domain\AuditEvent;
use MicroHis\Gobernanza\Domain\AuditEventReader;
use MicroHis\Gobernanza\Domain\AuditFilter;
use MicroHis\Gobernanza\Domain\AuditLogger;
use MicroHis\Gobernanza\Domain\AuditStorageFailure;
use MicroHis\Gobernanza\Persistence\InMemoryAuditLog;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use PDO;
use Tests\TestCase;

/**
 * Médicos tratantes (expediente.asignar) con middlewares, matriz RBAC y RecordAuditEvent reales, sobre la base
 * HOSPITAL de prueba del micro-HIS más las tablas de especialidades que exige el trigger de la base real.
 */
final class CareAssignmentApiTest extends TestCase
{
    private const ADMIN = 'c5555555-5555-4555-8555-555555555555';
    private const PEREZ = 'c2222222-2222-4222-8222-222222222222';
    private const LOCALES = [self::ADMIN => '55555555-5555-4555-8555-555555555555', self::PEREZ => '22222222-2222-4222-8222-222222222222'];
    private const OPEN_EPISODE = '0e000000-0000-4000-8000-000000000003';
    private const PEREZ_DOCTOR = 'd2222222-2222-4222-8222-222222222222';

    private InMemoryAuditLog $audit;
    private PDO $pdo;

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

        $pdo = $this->pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec((string) file_get_contents(base_path('micro-his/expediente-medico/database/schema.sql')));
        $pdo->exec((string) file_get_contents(base_path('micro-his/expediente-medico/database/demo.sql')));
        $pdo->exec("CREATE TABLE specialties (specialty_id TEXT PRIMARY KEY, code TEXT NOT NULL, name TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'active');
            CREATE TABLE doctor_specialties (doctor_profile_id TEXT NOT NULL, specialty_id TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'active', valid_from TEXT NOT NULL, valid_to TEXT);
            INSERT INTO specialties VALUES ('sp-medicina-interna', 'MED-INT', 'Medicina interna', 'active'), ('sp-urgencias', 'URG', 'Urgencias', 'inactive');
            INSERT INTO doctor_specialties VALUES ('d2222222-2222-4222-8222-222222222222', 'sp-medicina-interna', 'active', '2026-01-01', NULL),
                ('d2222222-2222-4222-8222-222222222222', 'sp-urgencias', 'active', '2026-01-01', NULL);");
        $this->app->instance(HospitalStoresProvider::class, new HospitalRegistry(['hospital-demo' => static fn (): HospitalStores => expediente_pdo_stores($pdo)]));
        $this->app->instance(CareAssignments::class, new CareAssignments(static fn (): PDO => $pdo));
        $this->app->instance(ResolutorActorLocal::class, new class(self::LOCALES) implements ResolutorActorLocal
        {
            public function __construct(private readonly array $locales) {}

            public function referencia(string $centralUserId): ?string
            {
                return $this->locales[$centralUserId] ?? null;
            }
        });
        ExpedienteTestIdentity::$hospital = 'hospital-demo';
        config()->set('tenants.hospital_database_tenant_id', 'hospital-demo');
        $this->withHeaders(['X-Tenant-ID' => 'hospital-demo']);
    }

    public function test_admin_assigns_a_treating_physician_who_can_then_read_the_record(): void
    {
        ExpedienteTestIdentity::as('medico', self::PEREZ);
        $this->getJson('/api/v1/patients/P-0001/medical-record')->assertStatus(403)->assertJsonPath('code', 'SIN_VINCULO_ASISTENCIAL');

        ExpedienteTestIdentity::as('admin_hospital', self::ADMIN);
        $overview = $this->getJson('/api/v1/patients/P-0001/care-assignments')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('episodes.0.care_episode_id', self::OPEN_EPISODE)
            ->assertJsonCount(1, 'assignments')
            ->assertJsonCount(1, 'doctors');
        $this->assertArrayNotHasKey('reason', $overview->json('episodes.0'));
        $id = $this->assignPerez()->assertCreated()->json('care_assignment_id');

        ExpedienteTestIdentity::as('medico', self::PEREZ);
        $this->getJson('/api/v1/patients/P-0001/medical-record')->assertOk()->assertJsonPath('record.record_number', 'EXP-P-0001');

        ExpedienteTestIdentity::as('admin_hospital', self::ADMIN);
        $this->deleteJson('/api/v1/patients/P-0001/care-assignments/'.$id)->assertOk()->assertJsonPath('status', 'closed');
        ExpedienteTestIdentity::as('medico', self::PEREZ);
        $this->getJson('/api/v1/patients/P-0001/medical-record')->assertStatus(403);

        $events = array_map(static fn (AuditEvent $e): string => $e->eventName(), $this->audit->search('hospital-demo', new AuditFilter)->events);
        $this->assertContains('expediente_medico.medico_asignado', $events);
        $this->assertContains('expediente_medico.asignacion_finalizada', $events);
    }

    public function test_assignment_rules_are_enforced(): void
    {
        ExpedienteTestIdentity::as('admin_hospital', self::ADMIN);
        $this->assignPerez(['care_episode_id' => '0e000000-0000-4000-8000-000000000001'])->assertStatus(422)->assertJsonPath('code', 'EPISODIO_NO_DISPONIBLE');
        $this->assignPerez(['care_episode_id' => '0e000000-0000-4000-8000-000000000004'])->assertStatus(422)->assertJsonPath('code', 'EPISODIO_NO_DISPONIBLE');
        $this->assignPerez(['specialty_id' => 'sp-urgencias'])->assertStatus(422)->assertJsonPath('code', 'MEDICO_NO_ELEGIBLE');
        $this->assignPerez(['doctor_profile_id' => 'd1111111-1111-4111-8111-111111111111'])->assertStatus(422)->assertJsonPath('code', 'MEDICO_NO_ELEGIBLE');
        $this->assignPerez()->assertCreated();
        $this->assignPerez()->assertStatus(409)->assertJsonPath('code', 'ASIGNACION_DUPLICADA');
        $this->postJson('/api/v1/patients/P-9999/care-assignments', ['care_episode_id' => 'x', 'doctor_profile_id' => 'x', 'specialty_id' => 'x', 'reason' => 'Tratante'])
            ->assertNotFound()->assertJsonPath('code', 'PACIENTE_NO_ENCONTRADO');
        $this->getJson('/api/v1/patients/P%200001/care-assignments')->assertStatus(422)->assertJsonPath('code', 'CODIGO_PACIENTE_INVALIDO');
        $this->deleteJson('/api/v1/patients/P-0002/care-assignments/ca000000-0000-4000-8000-000000000001')
            ->assertNotFound()->assertJsonPath('code', 'ASIGNACION_NO_ENCONTRADA');
    }

    public function test_only_roles_with_expediente_asignar_and_a_local_reference_can_assign(): void
    {
        foreach (['medico', 'enfermeria', 'recepcion'] as $role) {
            ExpedienteTestIdentity::as($role, self::PEREZ);
            $this->getJson('/api/v1/patients/P-0001/care-assignments')->assertStatus(403)->assertJsonPath('code', 'RBAC_DENIED');
        }
        ExpedienteTestIdentity::as('jefatura_medica', 'c9999999-9999-4999-8999-999999999999');
        $this->getJson('/api/v1/patients/P-0001/care-assignments')->assertOk();
        $this->assignPerez()->assertStatus(403)->assertJsonPath('code', 'ACTOR_SIN_REFERENCIA_LOCAL');
    }

    public function test_assignment_is_rolled_back_when_the_audit_cannot_be_recorded(): void
    {
        $this->app->instance(AuditLogger::class, new class implements AuditLogger
        {
            public function append(AuditEvent $event): void
            {
                throw new AuditStorageFailure('down');
            }
        });
        ExpedienteTestIdentity::as('admin_hospital', self::ADMIN);
        $this->assignPerez()->assertStatus(503)->assertJsonPath('code', 'AUDITORIA_NO_DISPONIBLE');
        $this->assertSame(3, (int) $this->pdo->query('SELECT COUNT(*) FROM care_assignments')->fetchColumn());
    }

    private function assignPerez(array $overrides = [])
    {
        return $this->postJson('/api/v1/patients/P-0001/care-assignments', array_merge([
            'care_episode_id' => self::OPEN_EPISODE,
            'doctor_profile_id' => self::PEREZ_DOCTOR,
            'specialty_id' => 'sp-medicina-interna',
            'reason' => 'Interconsulta de medicina interna',
        ], $overrides));
    }
}
