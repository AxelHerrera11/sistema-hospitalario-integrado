<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Presentation;

use MicroHis\ExpedienteMedico\Application\AuditUnavailable;
use MicroHis\ExpedienteMedico\Application\ConsultLongitudinalRecord;
use MicroHis\ExpedienteMedico\Application\OpenMedicalRecord;
use MicroHis\ExpedienteMedico\Application\PersistenceFailure;
use MicroHis\ExpedienteMedico\Domain\ExpedienteRuleViolation;

/**
 * Controlador MVC: lee la petición, invoca el caso de uso (modelo) y entrega el resultado a la vista.
 * Sin SQL ni reglas de negocio. Cada error termina aquí con un código estable y sin detalle interno.
 */
final class MedicalRecordController
{
    public function __construct(
        private readonly ConsultLongitudinalRecord $consult,
        private readonly OpenMedicalRecord $open,
        private readonly LongitudinalPresenter $presenter,
        private readonly JsonView $view,
    ) {
    }

    public function consult(Request $request, string $patientCode): array
    {
        return $this->respond(fn (): array => [200, $this->presenter->longitudinal($this->consult->execute($request->context(), $patientCode))]);
    }

    public function open(Request $request, string $patientCode): array
    {
        return $this->respond(function () use ($request, $patientCode): array {
            ['record' => $record, 'created' => $created] = $this->open->execute($request->context(), $patientCode);

            return [$created ? 201 : 200, $this->presenter->opened($record, $created)];
        });
    }

    /** @param callable(): array{0: int, 1: array} $action */
    private function respond(callable $action): array
    {
        try {
            [$status, $data] = $action();

            return $this->view->render($status, $data);
        } catch (ExpedienteRuleViolation $e) {
            return $this->view->render($e->status, array_filter(['code' => $e->getMessage(), 'trace_id' => $e->traceId]));
        } catch (AuditUnavailable) {
            return $this->view->render(503, ['code' => 'AUDITORIA_NO_DISPONIBLE']);
        } catch (PersistenceFailure) {
            return $this->view->render(503, ['code' => 'EXPEDIENTE_NO_DISPONIBLE']);
        } catch (\Throwable) {
            return $this->view->render(500, ['code' => 'ERROR_INTERNO']);
        }
    }
}
