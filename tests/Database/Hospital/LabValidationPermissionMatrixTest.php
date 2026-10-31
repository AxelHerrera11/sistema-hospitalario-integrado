<?php

namespace Tests\Database\Hospital;

use PHPUnit\Framework\Attributes\Group;

/** Matriz de permisos de validación bioquímica (ASII-20, semana 17). */
#[Group('database-integrity')]
class LabValidationPermissionMatrixTest extends LabPermissionMatrixCase
{
    public function test_only_biochemist_permission_reaches_decisions_and_denials_write_nothing(): void
    {
        $validate = '/api/v1/lab-validation/results/'.self::RESULTADO.'/validate';
        $reject = '/api/v1/lab-validation/results/'.self::RESULTADO.'/reject';
        $this->assertMatrix([
            ['GET', '/api/v1/lab-validation/results', [], ['bioquimico' => 200, 'tecnico_laboratorio' => 200, 'recepcion' => 403, 'medico' => 403]],
            ['POST', $validate, [], ['tecnico_laboratorio' => 403, 'recepcion' => 403, 'medico' => 403]],
            ['POST', $reject, ['reason' => 'Muestra hemolizada'], ['tecnico_laboratorio' => 403, 'recepcion' => 403]],
        ], ['lab_results_for_review' => 0, 'local_access_audits' => 0]);
    }

    public function test_identity_and_hospital_fail_closed(): void
    {
        $this->assertIdentityAndHospitalFailClosed('/api/v1/lab-validation/results');
    }

    public function test_internal_errors_do_not_expose_details(): void
    {
        $this->assertInternalErrorIsGeneric('/api/v1/lab-validation/results', 'lab_results_for_review');
    }
}
