<?php
declare(strict_types=1);

namespace MicroHis\Pacientes\Presentation;

use MicroHis\Pacientes\Application\{PatientOperationFailure, PatientService, PersistenceFailure};

final class PatientController
{
    public function __construct(private readonly PatientService $patients) {}

    public function register(array $data): array
    {
        return $this->respond(fn() => $this->patients->register($data), 201);
    }

    public function search(string $code): array
    {
        return $this->respond(fn() => $this->patients->search($code));
    }

    public function update(string $code, array $data): array
    {
        return $this->respond(fn() => $this->patients->update($code, $data));
    }

    private function respond(callable $operation, int $successStatus = 200): array
    {
        try {
            return ['status' => $successStatus, 'body' => ['patient' => $operation()]];
        } catch (PatientOperationFailure $e) {
            return [
                'status' => $e->errorCode === 'PATIENT_NOT_FOUND' ? 404 : 422,
                'body' => ['error' => ['code' => $e->errorCode, 'message' => $e->getMessage(), 'field' => $e->field]],
            ];
        } catch (PersistenceFailure) {
            return ['status' => 500, 'body' => ['error' => ['code' => 'PERSISTENCE_FAILURE', 'message' => 'No se pudo completar la operación.']]];
        }
    }
}
