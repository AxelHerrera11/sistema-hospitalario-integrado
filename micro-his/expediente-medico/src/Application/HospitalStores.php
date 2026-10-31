<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Application;

/** Almacenes de UN hospital (tenant). Con la base federada, cada hospital tiene su propia base HOSPITAL. */
final readonly class HospitalStores
{
    public function __construct(
        public MedicalRecordRepository $records,
        public PatientDirectory $patients,
        public CareRelationship $relationships,
    ) {
    }
}
