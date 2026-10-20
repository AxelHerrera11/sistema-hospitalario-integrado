<?php

declare(strict_types=1);

namespace MicroHis\Pacientes\Presentation;

use MicroHis\Pacientes\Application\PatientOperationFailure;
use MicroHis\Pacientes\Application\PatientService;
use MicroHis\Pacientes\Application\PersistenceFailure;

final class PatientController
{
    private readonly PatientView $view;

    public function __construct(
        private readonly PatientService $patients,
        ?PatientView $view = null
    ) {
        $this->view = $view ?? new PatientView();
    }

    public function register(array $data): array
    {
        return $this->respond(
            fn() => $this->patients->register($data),
            201
        );
    }

    public function search(string $code): array
    {
        return $this->respond(
            fn() => $this->patients->search($code)
        );
    }

    public function update(string $code, array $data): array
    {
        return $this->respond(
            fn() => $this->patients->update($code, $data)
        );
    }

    private function respond(callable $operation, int $successStatus = 200): array
    {
        try {
            return $this->view->patient($operation(), $successStatus);
        } catch (PatientOperationFailure $e) {
            return $this->view->error(
                $e->errorCode === 'PATIENT_NOT_FOUND' ? 404 : 422,
                $e->errorCode,
                $e->getMessage(),
                $e->field
            );
        } catch (PersistenceFailure) {
            return $this->view->error(
                500,
                'PERSISTENCE_FAILURE',
                'No se pudo completar la operación.'
            );
        }
    }
}
