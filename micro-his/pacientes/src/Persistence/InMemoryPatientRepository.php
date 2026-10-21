<?php

declare(strict_types=1);

namespace MicroHis\Pacientes\Persistence;

use MicroHis\Pacientes\Application\PatientRepository;
use MicroHis\Pacientes\Application\PersistenceFailure;
use MicroHis\Pacientes\Domain\Patient;

final class InMemoryPatientRepository implements PatientRepository
{
    /** @var array<string, Patient> */
    private array $patients = [];

    private int $sequence = 0;

    public function findByCode(string $code): ?Patient
    {
        return $this->patients[$code] ?? null;
    }

    public function insert(Patient $patient): Patient
    {
        if (isset($this->patients[$patient->code])) {
            throw new PersistenceFailure('El paciente ya existe en el repositorio en memoria.');
        }

        $saved = new Patient(
            $patient->id ?? 'mem-' . (++$this->sequence),
            $patient->globalId,
            $patient->code,
            $patient->givenName,
            $patient->familyName,
            $patient->birthDate,
            $patient->sex,
            $patient->contact,
            $patient->status
        );

        $this->patients[$saved->code] = $saved;

        return $saved;
    }

    public function update(Patient $patient): Patient
    {
        if (!isset($this->patients[$patient->code])) {
            throw new PersistenceFailure('El paciente no existe en el repositorio en memoria.');
        }

        $this->patients[$patient->code] = $patient;

        return $patient;
    }
}
