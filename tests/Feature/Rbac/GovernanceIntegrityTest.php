<?php

namespace Tests\Feature\Rbac;

use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Tenants\TenantAuthTestCase;

/** Verificación de la cadena y catálogo de filtros con kernel HTTP, JWT, RBAC y ambos escritores reales. */
class GovernanceIntegrityTest extends TenantAuthTestCase
{
    private function authenticateAs(string $role = 'superadmin'): void
    {
        $this->addMembership($this->userId, $this->hospitalA, $role);
        $this->withToken($this->tokenFrom($this->login()->assertOk()))->withHeaders(['X-Tenant-ID' => $this->hospitalA]);
    }

    private function writeEvents(): void
    {
        $writer = $this->app->make(AuditLogRepositoryInterface::class);
        foreach ([$this->hospitalA, $this->hospitalB] as $hospital) {
            $writer->logRaw('rbac.access_denied', [
                'actor_id' => $this->userId, 'hospital_id' => $hospital, 'result' => 'denied',
                'resource' => ['type' => 'permission', 'id' => 'rbac.permisos.ver'], 'trace_id' => 'trace-integrity',
            ]);
        }
        $this->getJson('/api/v1/audit-events')->assertOk();
    }

    public function test_chain_written_by_both_writers_verifies_and_the_check_is_audited(): void
    {
        $this->authenticateAs();
        $this->writeEvents();
        $total = DB::connection('central')->table('audit_log')->count();

        $this->getJson('/api/v1/audit-events/integrity')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('valid', true)
            ->assertJsonPath('checked', $total)
            ->assertJsonPath('total', $total)
            ->assertJsonPath('broken', null);
        $this->assertSame(1, DB::connection('central')->table('audit_log')->where('event', 'gobernanza.integridad_verificada')->count());
        // El propio registro de la verificación extiende la cadena sin romperla.
        $this->getJson('/api/v1/audit-events/integrity')->assertOk()->assertJsonPath('valid', true);
    }

    public function test_tampering_is_detected_without_revealing_foreign_events(): void
    {
        $this->authenticateAs();
        $this->writeEvents();
        $central = DB::connection('central');
        $foreign = $central->table('audit_log')->where('hospital_id', $this->hospitalB)->first();
        $central->table('audit_log')->where('event_id', $foreign->event_id)->update(['result' => 'success']);

        $this->getJson('/api/v1/audit-events/integrity')
            ->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('broken.cause', 'hash_no_coincide')
            ->assertJsonPath('broken.event_id', null);

        $central->table('audit_log')->where('event_id', $foreign->event_id)->update(['result' => $foreign->result]);
        $own = $central->table('audit_log')->where('hospital_id', $this->hospitalA)->where('event', 'rbac.access_denied')->first();
        $central->table('audit_log')->where('event_id', $own->event_id)->update(['actor_id' => 'otro-actor']);
        $this->getJson('/api/v1/audit-events/integrity')
            ->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('broken.event_id', $own->event_id);
    }

    public function test_catalog_lists_only_staff_and_modules_of_the_session_hospital(): void
    {
        $this->authenticateAs();
        $foreign = (string) \Illuminate\Support\Str::uuid();
        $this->createUser($foreign, 'remoto@example.test');
        $this->addMembership($foreign, $this->hospitalB, 'medico');
        $this->writeEvents();
        $this->app->make(AuditLogRepositoryInterface::class)->logRaw('expediente_medico.consultado', [
            'actor_id' => $this->userId, 'hospital_id' => $this->hospitalB, 'result' => 'success',
        ]);

        $catalog = $this->getJson('/api/v1/audit-events/catalog')->assertOk();
        $this->assertSame([$this->userId], array_column($catalog->json('actors'), 'user_id'));
        $this->assertContains('rbac', $catalog->json('modules'));
        $this->assertNotContains('expediente_medico', $catalog->json('modules'));
    }

    public function test_roles_without_auditoria_ver_are_denied(): void
    {
        $this->authenticateAs('admin_hospital');
        foreach (['integrity', 'catalog'] as $endpoint) {
            $this->getJson('/api/v1/audit-events/'.$endpoint)->assertForbidden()->assertJsonPath('code', 'RBAC_DENIED');
        }
    }
}
