<?php

namespace Tests\Database\Hospital;

use PHPUnit\Framework\Attributes\Group;

/** Matriz de permisos de ingreso de resultados (ASII-19, semana 17). */
#[Group('database-integrity')]
class LabResultsPermissionMatrixTest extends LabPermissionMatrixCase
{
    public function test_roles_without_permission_are_denied_and_write_nothing(): void
    {
        $capture = '/api/v1/lab-results/'.self::RESULTADO.'/capture';
        $correct = '/api/v1/lab-results/'.self::RESULTADO.'/correct';
        $this->assertMatrix([
            ['GET', '/api/v1/lab-results', [], ['tecnico_laboratorio' => 200, 'bioquimico' => 200, 'recepcion' => 403, 'medico' => 403]],
            ['POST', $capture, ['value' => '110', 'unit' => 'mg/dL'], ['bioquimico' => 403, 'recepcion' => 403, 'medico' => 403]],
            ['POST', $correct, ['value' => '112', 'unit' => 'mg/dL', 'reason' => 'Transcripción'], ['bioquimico' => 403, 'recepcion' => 403]],
        ], ['lab_results_for_review' => 0, 'local_access_audits' => 0]);
    }

    public function test_identity_and_hospital_fail_closed(): void
    {
        $this->assertIdentityAndHospitalFailClosed('/api/v1/lab-results');
    }

    public function test_internal_errors_do_not_expose_details(): void
    {
        $this->assertInternalErrorIsGeneric('/api/v1/lab-results', 'lab_pending_results');
    }
}
