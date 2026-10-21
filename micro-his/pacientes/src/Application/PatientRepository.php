<?php
declare(strict_types=1);

namespace MicroHis\Pacientes\Application;

use MicroHis\Pacientes\Domain\Patient;

interface PatientRepository
{
    public function findByCode(string $code): ?Patient;
    public function insert(Patient $patient): Patient;
    public function update(Patient $patient): Patient;
}