<?php

namespace Tests\Database\Hospital;

use PHPUnit\Framework\Attributes\Group;

/** Matriz de permisos del prototipo NativePHP (ASII-29, semana 17). */
#[Group('database-integrity')]
class NativePhpPermissionMatrixTest extends LabPermissionMatrixCase
{
    public function test_roles_without_permission_cannot_queue_or_sync_and_write_nothing(): void
    {
        $this->assertMatrix([
            ['GET', '/api/v1/nativephp/actions', [], ['tecnico_laboratorio' => 200, 'bioquimico' => 200, 'recepcion' => 403, 'medico' => 403]],
            ['POST', '/api/v1/nativephp/actions', ['kind' => 'lab_result_preview', 'payload' => '{}'], ['bioquimico' => 403, 'recepcion' => 403, 'medico' => 403]],
            ['POST', '/api/v1/nativephp/sync', ['connectivity' => 'online'], ['bioquimico' => 403, 'recepcion' => 403]],
        ], ['nativephp_local_actions' => 0]);
    }

    public function test_identity_and_hospital_fail_closed(): void
    {
        $this->assertIdentityAndHospitalFailClosed('/api/v1/nativephp/actions');
    }

    public function test_internal_errors_do_not_expose_details(): void
    {
        $this->assertInternalErrorIsGeneric('/api/v1/nativephp/actions', 'nativephp_local_actions');
    }
}
