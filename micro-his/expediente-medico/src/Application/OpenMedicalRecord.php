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
            return $this->repeated($request, $existing);
        }

        $candidate = MedicalRecord::open(Uuid::v4(), $patient, $this->clock->now());
        $stored = $stores->records->open($candidate, function (MedicalRecord $record) use ($request): void {
            $this->audit->record($request, ClinicalAccessAudit::OPENED, 'success', $record->id);
        });

        return $stored->id === $candidate->id ? ['record' => $stored, 'created' => true] : $this->repeated($request, $stored);
    }

    /**
     * RF-EXP-05 (hallazgo S-07 de la Semana 17): repetir la apertura también entrega el número del expediente, así que
     * queda en la bitácora; si no se puede registrar, no se devuelve nada (503, como en la consulta).
     *
     * @return array{record: MedicalRecord, created: false}
     */
    private function repeated(RequestContext $request, MedicalRecord $record): array
    {
        $this->audit->record($request, ClinicalAccessAudit::REPEATED_OPENING, 'success', $record->id);

        return ['record' => $record, 'created' => false];
    }
}
