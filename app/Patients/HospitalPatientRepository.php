<?php

declare(strict_types=1);

namespace App\Patients;

use Closure;
use MicroHis\Pacientes\Application\PatientRepository;
use MicroHis\Pacientes\Application\PersistenceFailure;
use MicroHis\Pacientes\Domain\Patient;
use MicroHis\Pacientes\Persistence\PdoPatientRepository;
use PDO;
use Throwable;

final class HospitalPatientRepository implements PatientRepository
{
    private ?PdoPatientRepository $adapter = null;

    /** @param Closure(): PDO $connect */
    public function __construct(private readonly Closure $connect)
    {
    }

    public function findByCode(string $code): ?Patient
    {
        return $this->adapter()->findByCode($code);
    }

    public function insert(Patient $patient): Patient
    {
        return $this->adapter()->insert($patient);
    }

    public function update(Patient $patient): Patient
    {
        return $this->adapter()->update($patient);
    }

    private function adapter(): PdoPatientRepository
    {
        if ($this->adapter !== null) {
            return $this->adapter;
        }

        try {
            return $this->adapter = new PdoPatientRepository(($this->connect)());
        } catch (Throwable $error) {
            throw new PersistenceFailure('La base HOSPITAL no está disponible.', 0, $error);
        }
    }
}
