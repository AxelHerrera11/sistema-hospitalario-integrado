<?php

declare(strict_types=1);

namespace Tests\Feature\QaCalidad;

use MicroHis\Gobernanza\Domain\AuditEvent;
use MicroHis\Gobernanza\Domain\AuditEventReader;
use MicroHis\Gobernanza\Domain\AuditFilter;
use MicroHis\Gobernanza\Domain\AuditLogger;
use MicroHis\Gobernanza\Persistence\InMemoryAuditLog;
use MicroHis\QaCalidad\Domain\TestRunRepository;
use Tests\TestCase;

/** qa:import-evidence: la evidencia de CI llega a /calidad-versiones con los casos de uso y la bitácora reales. */
final class QaImportEvidenceTest extends TestCase
{
    private const HOSPITAL = '11111111-1111-4111-8111-111111111111';

    private const SHA = 'a24bfb36c0ffee0000000000000000000000beef';

    private InMemoryAuditLog $audit;

    private string $evidence;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('qa.runs_sqlite', ':memory:');
        config()->set('tenants.hospital_database_tenant_id', self::HOSPITAL);
        $this->audit = new InMemoryAuditLog;
        $this->app->instance(AuditLogger::class, $this->audit);
        $this->app->instance(AuditEventReader::class, $this->audit);

        $this->evidence = sys_get_temp_dir().'/qa-import-'.bin2hex(random_bytes(4));
        foreach (['phpunit/unit.xml', 'e2e/auth-rbac.xml'] as $report) {
            @mkdir(dirname($this->evidence.'/'.$report), 0775, true);
            copy(base_path('micro-his/qa-calidad-versiones/tests/fixtures/phpunit-unit.xml'), $this->evidence.'/'.$report);
        }
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->evidence.'/*/*') ?: []);
        @unlink($this->evidence.'/manifest.json');
        array_map('rmdir', glob($this->evidence.'/*', GLOB_ONLYDIR) ?: []);
        @rmdir($this->evidence);
        parent::tearDown();
    }

    public function test_evidence_becomes_an_approved_run_of_the_instance_hospital_and_is_audited(): void
    {
        $this->artisan('qa:import-evidence', [
            'evidence-root' => $this->evidence, '--sha' => self::SHA, '--release' => '1.5.0-rc.1',
            '--manifest' => $this->manifest([['phpunit-unit', true, 'phpunit/unit.xml'], ['e2e-auth-rbac', true, 'e2e/auth-rbac.xml']]),
        ])->assertExitCode(0);

        $runs = $this->app->make(TestRunRepository::class)->listByTenant(self::HOSPITAL, 10);
        $this->assertCount(1, $runs);
        $run = $runs[0];
        $this->assertSame('1.5.0-rc.1', $run->plan->releaseVersion);
        $this->assertSame('completed', $run->status()->value);
        $this->assertSame('approved', $this->app->make(TestRunRepository::class)->latestDecision(self::HOSPITAL, $run->id)->verdict->value);
        $this->assertSame([], $this->app->make(TestRunRepository::class)->listByTenant('hospital-pruebas-e2e', 10));

        $events = array_map(static fn (AuditEvent $e): string => $e->eventName().':'.$e->actorId, $this->audit->search(self::HOSPITAL, new AuditFilter)->events);
        $this->assertNotEmpty($events);
        foreach ($events as $event) {
            $this->assertStringEndsWith(':ci-bot', $event);
        }
    }

    public function test_a_missing_required_report_blocks_the_release_instead_of_being_invented(): void
    {
        $this->artisan('qa:import-evidence', [
            'evidence-root' => $this->evidence, '--sha' => self::SHA, '--release' => '1.5.0-rc.2',
            '--manifest' => $this->manifest([['phpunit-unit', true, 'phpunit/unit.xml'], ['e2e-auth-rbac', true, 'e2e/no-publicado.xml']]),
        ])->assertExitCode(2);
    }

    public function test_invalid_input_is_a_configuration_error_and_nothing_is_stored(): void
    {
        $this->artisan('qa:import-evidence', ['evidence-root' => $this->evidence.'/no-existe', '--sha' => self::SHA, '--release' => '1.5.0'])
            ->assertExitCode(3);
        $this->artisan('qa:import-evidence', ['evidence-root' => $this->evidence, '--sha' => 'corto', '--release' => '1.5.0'])
            ->assertExitCode(3);
        $this->assertSame([], $this->app->make(TestRunRepository::class)->listByTenant(self::HOSPITAL, 10));
    }

    /** @param list<array{0: string, 1: bool, 2: string}> $suites nombre, obligatoria, reporte JUnit */
    private function manifest(array $suites): string
    {
        $file = $this->evidence.'/manifest.json';
        file_put_contents($file, json_encode([
            'tenant_id' => 'hospital-pruebas-e2e',
            'ci_actor' => 'ci-bot',
            'suites' => array_map(static fn (array $s): array => [
                'name' => $s[0], 'kind' => str_starts_with($s[0], 'e2e') ? 'e2e' : 'unit', 'required' => $s[1],
                'junit' => $s[2],
            ], $suites),
        ]));

        return $file;
    }
}
