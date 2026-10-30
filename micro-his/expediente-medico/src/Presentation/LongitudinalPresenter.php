<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Presentation;

use MicroHis\ExpedienteMedico\Domain\CareEpisode;
use MicroHis\ExpedienteMedico\Domain\LongitudinalRecord;
use MicroHis\ExpedienteMedico\Domain\MedicalRecord;

/**
 * Da forma al JSON del contrato (openapi.yaml). Minimización: la apertura no devuelve datos del paciente; la
 * consulta solo devuelve lo que muestra la pantalla (sin contacto, global_id ni identificadores de otros módulos).
 */
final class LongitudinalPresenter
{
    public function opened(MedicalRecord $record, bool $created): array
    {
        return ['created' => $created] + $this->record($record);
    }

    public function longitudinal(LongitudinalRecord $view): array
    {
        return [
            'record' => $this->record($view->record),
            'patient' => [
                'code' => $view->patient->code,
                'display_name' => $view->patient->displayName,
                'birth_date' => $view->patient->birthDate,
                'sex' => $view->patient->sex,
                'status' => $view->patient->status,
            ],
            'summary' => ['episodes' => count($view->episodes), 'open_episodes' => $view->openEpisodes()],
            'episodes' => array_map(static fn (CareEpisode $e): array => [
                'care_episode_id' => $e->id,
                'type' => $e->type,
                'status' => $e->status,
                'started_at' => $e->startedAt->format(DATE_ATOM),
                'ended_at' => $e->endedAt?->format(DATE_ATOM),
                'reason' => $e->reason,
                'service_code' => $e->serviceCode,
            ], $view->episodes),
        ];
    }

    private function record(MedicalRecord $record): array
    {
        return [
            'medical_record_id' => $record->id,
            'record_number' => $record->recordNumber,
            'status' => $record->status,
            'opened_at' => $record->openedAt->format(DATE_ATOM),
            'closed_at' => $record->closedAt?->format(DATE_ATOM),
        ];
    }
}
