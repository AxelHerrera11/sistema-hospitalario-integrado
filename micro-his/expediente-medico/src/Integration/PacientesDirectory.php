<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Integration;

use MicroHis\ExpedienteMedico\Application\PatientDirectory;
use MicroHis\ExpedienteMedico\Application\PersistenceFailure;
use MicroHis\ExpedienteMedico\Domain\PatientSummary;
use MicroHis\Pacientes\Application\PatientRepository;
use MicroHis\Pacientes\Application\PersistenceFailure as PacientesPersistenceFailure;
use MicroHis\Pacientes\Domain\DomainRuleViolation;

/**
 * Adaptador al contrato real del módulo Pacientes (micro-his/pacientes, Madelin): PatientRepository::findByCode.
 * Con PdoPatientRepository lee local_patients; con InMemoryPatientRepository sirve para pruebas. Solo lectura.
 */
final class PacientesDirectory implements PatientDirectory
{
    public function __construct(private readonly PatientRepository $patients)
    {
    }

    public function findByCode(string $code): ?PatientSummary
    {
        try {
            $patient = $this->patients->findByCode($code);
        } catch (PacientesPersistenceFailure|DomainRuleViolation) {
            // DomainRuleViolation: una fila que el propio módulo Pacientes no puede reconstruir.
            throw new PersistenceFailure('patient_read_failed');
        }
        if ($patient === null) {
            return null;
        }

        return new PatientSummary(
            (string) $patient->id,
            $patient->code,
            trim($patient->givenName . ' ' . $patient->familyName),
            $patient->birthDate,
            $patient->sex,
            $patient->status,
        );
    }
}
