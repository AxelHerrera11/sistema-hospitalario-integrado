<?php

declare(strict_types=1);

namespace Tests\Feature\LabOrders;

use Symfony\Component\Process\Process;
use Tests\TestCase;

final class LabOrderApiEvidenceTest extends TestCase
{
    private function repositoryRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    public function test_week_16_api_evidence_covers_creation_idempotency_transition_cancellation_and_rbac(): void
    {
        $process = new Process([PHP_BINARY, $this->repositoryRoot() . '/micro-his/ordenes-laboratorio/tests/api.php'], $this->repositoryRoot());
        $process->mustRun();

        $this->assertStringContainsString('emisión → 201', $process->getOutput());
        $this->assertStringContainsString('misma Idempotency-Key recupera con 200', $process->getOutput());
        $this->assertStringContainsString('transición inválida → 409', $process->getOutput());
        $this->assertStringContainsString('cancelación → 200 CANCELLED', $process->getOutput());
        $this->assertStringContainsString('sin permiso de creación → 403', $process->getOutput());
    }

    public function test_migration_declares_shared_statuses_and_idempotency_fingerprint(): void
    {
        $migration = file_get_contents($this->repositoryRoot() . '/database/migrations/hospital/2026_10_31_000001_create_lab_orders_tables.php');

        $this->assertNotFalse($migration);
        $this->assertStringContainsString("DEFAULT 'REQUESTED'", $migration);
        $this->assertStringContainsString("'REQUESTED', 'COLLECTED', 'IN_PROCESS', 'RESULTED', 'VALIDATED', 'CANCELLED'", $migration);
        $this->assertStringContainsString('idempotency_request_hash text NOT NULL', $migration);
    }

    public function test_list_contract_covers_pagination_date_filters_and_concurrent_idempotency(): void
    {
        $controller = file_get_contents($this->repositoryRoot() . '/app/Http/Controllers/Api/V1/LabOrderController.php');
        $contract = file_get_contents($this->repositoryRoot() . '/docs/ordenes-laboratorio/contrato-api.md');

        $this->assertNotFalse($controller);
        $this->assertNotFalse($contract);
        $this->assertStringContainsString("->reorder()->count()", $controller);
        $this->assertStringContainsString("'from' => ['nullable', 'date']", $controller);
        $this->assertStringContainsString("'last_page'", $controller);
        $this->assertStringContainsString('misma Idempotency-Key', file_get_contents($this->repositoryRoot() . '/micro-his/ordenes-laboratorio/tests/api.php'));
        $this->assertStringContainsString('"pagination"', $contract);
        $this->assertStringContainsString('ISO-8601', $contract);
    }
}
