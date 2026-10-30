<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Application;

use MicroHis\ExpedienteMedico\Domain\ExpedienteRuleViolation;
use MicroHis\ExpedienteMedico\Domain\MedicalRecord;
use MicroHis\ExpedienteMedico\Domain\PatientSummary;
use MicroHis\ExpedienteMedico\Domain\Uuid;

/**
 * Caso de uso «apertura del expediente longitudinal» (RF-EXP-01). Idempotente: si el paciente ya tiene expediente,
 * lo devuelve con created=false, también cuando otro proceso lo abrió al mismo tiempo. El INSERT y su evento de
 * auditoría van juntos: si la bitácora falla, la apertura se revierte.
 */
final class OpenMedicalRecord
{
    /** @param string $permissionName permiso de apertura; hoy la propuesta ExpedientePermissions::APERTURA_PROPUESTA */
    public function __construct(
        private readonly AccessGuard $guard,
        private readonly HospitalStoresProvider $hospitals,
        private readonly ClinicalAccessAudit $audit,
        private readonly Clock $clock,
        private readonly string $permissionName = ExpedientePermissions::APERTURA_PROPUESTA,
    ) {
    }

    /** @return array{record: MedicalRecord, created: bool} */
    public function execute(RequestContext $request, string $patientCode): array
    {
        PatientSummary::assertCode($patientCode);
        $this->guard->requirePermission($request, $this->permissionName);
        $stores = $this->hospitals->forHospital($request->hospitalId)
            ?? throw new ExpedienteRuleViolation('HOSPITAL_NO_DISPONIBLE', 503);

        $patient = $stores->patients->findByCode($patientCode)
            ?? throw new ExpedienteRuleViolation('PACIENTE_NO_ENCONTRADO', 404);
        $existing = $stores->records->findByPatient($patient->id);
        if ($existing !== null) {
            return ['record' => $existing, 'created' => false];
        }

        $candidate = MedicalRecord::open(Uuid::v4(), $patient, $this->clock->now());
        $stored = $stores->records->open($candidate, function (MedicalRecord $record) use ($request): void {
            $this->audit->record($request, ClinicalAccessAudit::OPENED, 'success', $record->id);
        });

        return ['record' => $stored, 'created' => $stored->id === $candidate->id];
    }
}
