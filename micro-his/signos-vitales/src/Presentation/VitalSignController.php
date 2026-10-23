<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Presentation;

use MicroHis\SignosVitales\Application\RecordVitalSigns;
use MicroHis\SignosVitales\Domain\VitalSignViolation;

/** Controlador MVC: lee la petición, invoca el caso de uso (modelo) y entrega el resultado a la vista. Sin SQL ni reglas de negocio. */
final class VitalSignController
{
    public function __construct(private readonly RecordVitalSigns $record, private readonly JsonView $view)
    {
    }

    public function record(Request $request, string $episodeId): array
    {
        try {
            return $this->view->render(201, $this->record->execute($request->actor(), $episodeId, $request->measurements(), $request->measuredAt()));
        } catch (VitalSignViolation $e) {
            return $this->view->render($e->status, ['code' => $e->getMessage()]);
        } catch (\RuntimeException) {
            return $this->view->render(500, ['code' => 'VITAL_SIGN_PERSISTENCE_ERROR']);
        }
    }
}
