<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Presentation;

use MicroHis\SignosVitales\Application\GetVitalSign;
use MicroHis\SignosVitales\Application\RecordVitalSigns;
use MicroHis\SignosVitales\Domain\VitalSignViolation;

/** Controlador MVC: lee la petición, invoca el caso de uso (modelo) y entrega el resultado a la vista. Sin SQL ni reglas de negocio. */
final class VitalSignController
{
    public function __construct(private readonly RecordVitalSigns $record, private readonly GetVitalSign $get, private readonly JsonView $view)
    {
    }

    public function record(Request $request, string $episodeId): array
    {
        return $this->respond(201, fn () => $this->record->execute($request->actor(), $episodeId, $request->measurements(), $request->measuredAt()));
    }

    public function show(Request $request, string $vitalSignId): array
    {
        return $this->respond(200, fn () => $this->get->execute($request->actor(), $vitalSignId));
    }

    /** Traduce resultado o excepción a estado + JSON; cada error termina la petición aquí. */
    private function respond(int $status, callable $action): array
    {
        try {
            return $this->view->render($status, $action());
        } catch (VitalSignViolation $e) {
            return $this->view->render($e->status, ['code' => $e->getMessage()]);
        } catch (\RuntimeException) {
            return $this->view->render(500, ['code' => 'VITAL_SIGN_PERSISTENCE_ERROR']);
        }
    }
}
