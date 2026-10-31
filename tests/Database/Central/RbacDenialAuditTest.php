<?php

declare(strict_types=1);

namespace Tests\Database\Central;

use App\Modules\Tenants\Infrastructure\Audit\AuditLogRepository;
use App\Rbac\AuditDenials;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use MicroHis\Gobernanza\Domain\{AuditCriticality, AuditFilter, AuditOutcome};
use MicroHis\Gobernanza\Persistence\PdoAuditLog;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * La denegación RBAC escrita en PostgreSQL debe ser legible por el lector de
 * gobernanza como denegación (resultado `denied`), no como resultado desconocido.
 */
#[Group('database-integrity')]
class RbacDenialAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once dirname(__DIR__, 3).'/micro-his/gobernanza-auditoria/bootstrap.php';
        $this->assertSame('pgsql', DB::connection('central')->getDriverName());
        $this->assertSame(0, Artisan::call('migrate:fresh', [
            '--database' => 'central', '--path' => 'database/migrations/central', '--force' => true,
        ]));
    }

    public function test_stored_denial_is_read_back_as_a_denied_outcome(): void
    {
        (new AuditDenials(new AuditLogRepository))->record([
            'user_id' => 'reader-1',
            'hospital_id' => 'hospital-a',
            'permission' => 'rbac.permisos.ver',
            'reason' => 'permiso_faltante',
            'trace_id' => 'trace-1a2b3c',
        ]);

        $row = DB::connection('central')->table('audit_log')->where('event', 'rbac.access_denied')->first();
        $this->assertNotNull($row);
        $this->assertSame('denied', $row->result);
        $detail = json_decode($row->detail, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('security', $detail['criticality']);
        $this->assertEquals(['type' => 'permission', 'id' => 'rbac.permisos.ver'], $detail['resource']);

        $page = (new PdoAuditLog(DB::connection('central')->getPdo()))
            ->search('hospital-a', new AuditFilter(outcome: AuditOutcome::Denied));

        $this->assertSame(1, $page->total);
        $event = $page->events[0];
        $this->assertSame(AuditOutcome::Denied, $event->outcome);
        $this->assertSame(AuditCriticality::Security, $event->criticality);
        $this->assertSame('permission', $event->resourceType);
        $this->assertSame('rbac.permisos.ver', $event->resourceId);
        $this->assertSame('permiso_faltante', $event->reason);
        $this->assertSame('trace-1a2b3c', $event->correlationId);
        $this->assertSame(['permission' => 'rbac.permisos.ver'], $event->context);
    }
}
