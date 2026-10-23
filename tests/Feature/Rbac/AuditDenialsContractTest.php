<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use App\Rbac\AuditDenials;
use Tests\TestCase;

/**
 * Contrato del evento de denegación RBAC contra el lector de gobernanza
 * (docs/gobernanza-auditoria/semana-05/contrato-api.md): resultado `denied`,
 * criticidad `security` y recurso `permission`.
 */
class AuditDenialsContractTest extends TestCase
{
    public function test_denial_carries_the_governance_contract_fields(): void
    {
        $repository = $this->auditSpy();
        (new AuditDenials($repository))->record([
            'user_id' => 'reader-1',
            'hospital_id' => 'hospital-a',
            'permission' => 'rbac.permisos.ver',
            'reason' => 'permiso_faltante',
            'trace_id' => 'trace-1a2b3c',
        ]);

        $this->assertCount(1, $repository->calls);
        $call = $repository->calls[0];
        $this->assertSame('rbac.access_denied', $call['eventName']);
        $this->assertArrayHasKey('result', $call['payload']);
        $this->assertArrayHasKey('criticality', $call['payload']);
        $this->assertArrayHasKey('resource', $call['payload']);
        $this->assertSame('denied', $call['payload']['result']);
        $this->assertSame('security', $call['payload']['criticality']);
        $this->assertSame(['type' => 'permission', 'id' => 'rbac.permisos.ver'], $call['payload']['resource']);
        $this->assertSame('permiso_faltante', $call['payload']['reason']);
        $this->assertSame('trace-1a2b3c', $call['payload']['trace_id']);
        $this->assertSame('reader-1', $call['payload']['actor_id']);
        $this->assertSame('hospital-a', $call['payload']['tenant_id']);
    }

    public function test_denial_without_permission_does_not_invent_a_resource(): void
    {
        $repository = $this->auditSpy();
        (new AuditDenials($repository))->record([
            'user_id' => 'reader-1',
            'hospital_id' => 'hospital-a',
            'reason' => 'resolucion_no_disponible',
            'trace_id' => 'trace-1a2b3c',
        ]);

        $call = $repository->calls[0];
        $this->assertSame('denied', $call['payload']['result']);
        $this->assertNull($call['payload']['resource']);
    }

    private function auditSpy(): object
    {
        return new class implements AuditLogRepositoryInterface {
            /** @var list<array{eventName: string, payload: array<string, mixed>, severity: ?string}> */
            public array $calls = [];

            public function logRaw(string $eventName, array $payload, ?string $severity = null): void
            {
                $this->calls[] = ['eventName' => $eventName, 'payload' => $payload, 'severity' => $severity];
            }
        };
    }
}
