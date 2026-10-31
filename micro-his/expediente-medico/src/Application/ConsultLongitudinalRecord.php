<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Application;

use MicroHis\ExpedienteMedico\Domain\ExpedienteRuleViolation;
use MicroHis\ExpedienteMedico\Domain\LongitudinalRecord;
use MicroHis\ExpedienteMedico\Domain\PatientSummary;

/**
 * Caso de uso «consulta autorizada del expediente longitudinal» (RF-EXP-02..04).
 * Orden fijo: formato → permiso RBAC → hospital → paciente + vínculo → expediente → integridad → auditoría → datos.
 * Paciente inexistente y paciente sin vínculo responden igual (403) para no revelar qué pacientes existen.
 */
final class ConsultLongitudinalRecord
{
    public function __construct(
        private readonly AccessGuard $guard,
        private readonly HospitalStoresProvider $hospitals,
        private readonly ClinicalAccessAudit $audit,
    ) {
    }

    public function execute(RequestContext $request, string $patientCode): LongitudinalRecord
    {
        PatientSummary::assertCode($patientCode);
        $this->guard->requirePermission($request, ExpedientePermissions::CONSULTA);
        $stores = $this->hospitals->forHospital($request->hospitalId)
            ?? throw new ExpedienteRuleViolation('HOSPITAL_NO_DISPONIBLE', 503);

        $patient = $stores->patients->findByCode($patientCode);
        if ($patient === null || !$stores->relationships->hasActiveAssignment($request->localUserRef(), $patient->id)) {
            $this->guard->deny($request, 'sin_vinculo_asistencial');
            throw new ExpedienteRuleViolation('SIN_VINCULO_ASISTENCIAL', 403);
        }

        $record = $stores->records->findByPatient($patient->id)
            ?? throw new ExpedienteRuleViolation('EXPEDIENTE_NO_ABIERTO', 404);
        $longitudinal = new LongitudinalRecord($record, $patient, $stores->records->episodesOf($patient->id));

        // Sin evento de auditoría persistido no se entregan datos clínicos (AuditUnavailable → 503).
        $this->audit->record($request, ClinicalAccessAudit::CONSULTED, 'success', $record->id, null, [
            'episodios' => count($longitudinal->episodes),
        ]);

        return $longitudinal;
    }
}
