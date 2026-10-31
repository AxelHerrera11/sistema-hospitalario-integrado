<?php

declare(strict_types=1);

namespace Tests\Feature\QaCalidad;

use App\QaCalidad\QaCalidadServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use MicroHis\Gobernanza\Domain\AuditEvent;
use MicroHis\Gobernanza\Domain\AuditEventReader;
use MicroHis\Gobernanza\Domain\AuditFilter;
use MicroHis\Gobernanza\Domain\AuditLogger;
use MicroHis\Gobernanza\Persistence\InMemoryAuditLog;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;
use Tests\TestCase;

/**
 * API v1 de calidad de versiones con middlewares, matriz RBAC, Authorize y RecordAuditEvent REALES.
 * El almacén es el SQLite del módulo en memoria; tenant y jwt.auth los sustituye QaTestIdentity.
 */
final class QaCalidadApiTest extends TestCase
{
    private const LIDER = 'd1111111-1111-4111-8111-111111111111';
    private const SHA = '4f2c9a1be07d3c6a8e5b9d0f1a2b3c4d5e6f7a8b';

    private InMemoryAuditLog $audit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(Kernel::class);
        $this->app['router']->aliasMiddleware('tenant', QaTestIdentity::class);
        $this->app['router']->aliasMiddleware('jwt.auth', QaTestIdentity::class);
        $this->app->instance(RoleResolver::class, new QaTestIdentity);
        $this->app->instance(DenialRecorder::class, new QaTestIdentity);
        $this->audit = new InMemoryAuditLog;
        $this->app->instance(AuditLogger::class, $this->audit);
        $this->app->instance(AuditEventReader::class, $this->audit);
        config()->set('qa.runs_sqlite', ':memory:');
        QaTestIdentity::$hospital = 'hospital-demo';
        $this->withHeaders(['X-Tenant-ID' => 'hospital-demo']);
    }

    /** @return array<string, mixed> */
    private static function plan(): array
    {
        return ['release_version' => '1.4.0-rc.1', 'commit_sha' => self::SHA, 'environment' => 'ci', 'suites' => [
            ['name' => 'phpunit-unit', 'kind' => 'unit', 'required' => true],
            ['name' => 'e2e-auth-rbac', 'kind' => 'e2e', 'required' => true],
        ]];
    }

    /** @return array<string, mixed> */
    private static function suiteResult(string $suite, int $failed): array
    {
        return ['suite' => $suite, 'total' => 10, 'failed' => $failed, 'skipped' => 0, 'duration_ms' => 1500,
            'evidence' => [['kind' => 'junit', 'path' => "{$suite}.xml", 'sha256' => str_repeat('a', 64), 'bytes' => 512]]];
    }

    private function runThrough(int $failed): string
    {
        $id = $this->postJson('/api/v1/qa/runs', self::plan())->assertCreated()->json('data.id');
        $this->postJson("/api/v1/qa/runs/{$id}/transitions", ['transition' => 'start'])->assertOk()->assertJsonPath('data.status', 'running');
        $this->postJson("/api/v1/qa/runs/{$id}/results", self::suiteResult('phpunit-unit', $failed))->assertCreated();
        $this->postJson("/api/v1/qa/runs/{$id}/results", self::suiteResult('e2e-auth-rbac', 0))->assertCreated();
        $this->postJson("/api/v1/qa/runs/{$id}/transitions", ['transition' => 'complete'])->assertOk();

        return $id;
    }

    public function test_hospital_admin_plans_runs_records_results_and_gets_a_gate_verdict(): void
    {
        $this->assertArrayHasKey(QaCalidadServiceProvider::class, $this->app->getLoadedProviders());
        QaTestIdentity::as('admin_hospital', self::LIDER);
        $green = $this->runThrough(0);
        $this->postJson("/api/v1/qa/runs/{$green}/gate-decisions")->assertCreated()->assertJsonPath('data.verdict', 'approved');
        $red = $this->runThrough(2);
        $this->postJson("/api/v1/qa/runs/{$red}/gate-decisions")->assertCreated()->assertJsonPath('data.verdict', 'rejected');

        $this->getJson("/api/v1/qa/runs/{$green}")->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('data.gate.verdict', 'approved')
            ->assertJsonPath('data.suites.0.result.passed', 10);
        $this->assertCount(2, $this->getJson('/api/v1/qa/runs')->assertOk()->json('data'));
        $events = array_map(static fn (AuditEvent $e): string => $e->eventName(), $this->audit->search('hospital-demo', new AuditFilter)->events);
        $this->assertContains('qa_calidad.qa.gate.evaluado', $events);
    }

    public function test_permissions_are_enforced_per_operation_and_role(): void
    {
        foreach (['medico', 'enfermeria', 'recepcion'] as $role) {
            QaTestIdentity::as($role, self::LIDER);
            $this->getJson('/api/v1/qa/runs')->assertForbidden()->assertJsonPath('code', 'RBAC_DENIED');
            $this->postJson('/api/v1/qa/runs', self::plan())->assertForbidden();
        }
        QaTestIdentity::as(null, self::LIDER);
        $this->getJson('/api/v1/qa/runs')->assertUnauthorized();
    }

    public function test_runs_are_isolated_by_hospital_and_invalid_input_is_rejected(): void
    {
        QaTestIdentity::as('superadmin', self::LIDER);
        $id = $this->postJson('/api/v1/qa/runs', self::plan())->assertCreated()->json('data.id');
        $this->postJson('/api/v1/qa/runs', ['commit_sha' => 'corto'] + self::plan())->assertStatus(422)->assertJsonPath('code', 'QA_VALIDATION_FAILED');
        $this->postJson("/api/v1/qa/runs/{$id}/transitions", ['transition' => 'complete'])->assertStatus(409);

        QaTestIdentity::$hospital = 'hospital-norte';
        $this->withHeaders(['X-Tenant-ID' => 'hospital-norte'])->getJson("/api/v1/qa/runs/{$id}")->assertNotFound();
        $this->withHeaders(['X-Tenant-ID' => 'hospital-demo'])->getJson('/api/v1/qa/runs')->assertForbidden();
    }
}
