<?php

declare(strict_types=1);

namespace MicroHis\Pacientes\Presentation;

use MicroHis\Pacientes\Application\PatientOperationFailure;
use MicroHis\Pacientes\Application\PersistenceFailure;

final class PatientResponsePresenter
{
    private readonly PatientView $view;

    public function __construct(?PatientView $view = null)
    {
        $this->view = $view ?? new PatientView();
    }

    public function present(callable $operation, int $successStatus = 200): array
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
