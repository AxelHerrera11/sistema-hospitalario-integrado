<?php
declare(strict_types=1);

namespace MicroHis\Pacientes\Application;

use MicroHis\Pacientes\Domain\DomainRuleViolation;
use MicroHis\Pacientes\Domain\Patient;

final class PatientService
{
    public function __construct(private readonly PatientRepository $patients) {}

    public function register(array $data): Patient
    {
        return $this->translateDomainFailure(function () use ($data): Patient {
            $code = trim((string)($data['code'] ?? ''));
            if ($code !== '' && $this->patients->findByCode($code)) {
                throw new DomainRuleViolation(
                    'PATIENT_CODE_EXISTS',
                    'Ya existe un paciente con ese código.',
                    'code'
                );
            }
            return $this->patients->insert(Patient::register($data));
        });
    }

    public function search(string $code): Patient
    {
        return $this->translateDomainFailure(fn(): Patient =>
            $this->patients->findByCode(trim($code))
                ?? throw new DomainRuleViolation('PATIENT_NOT_FOUND', 'Paciente no encontrado.')
        );
    }

    public function update(string $code, array $data): Patient
    {
        return $this->translateDomainFailure(fn(): Patient =>
            $this->patients->update($this->search($code)->update($data))
        );
    }

    private function translateDomainFailure(callable $operation): Patient
    {
        try {
            return $operation();
        } catch (DomainRuleViolation $e) {
            throw new PatientOperationFailure($e->errorCode, $e->getMessage(), $e->field, $e);
        }
    }
}