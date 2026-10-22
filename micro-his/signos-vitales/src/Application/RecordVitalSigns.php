<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Application;

use MicroHis\SignosVitales\Domain\ThresholdEvaluator;
use MicroHis\SignosVitales\Domain\VitalSigns;
use MicroHis\SignosVitales\Domain\VitalSignViolation;

/** UC-SV-01 a 05: captura, evaluación de umbrales y generación de alerta (RF-SV-01 a RF-SV-07). */
final class RecordVitalSigns
{
    public const ADULT_AGE = 18;

    public function __construct(
        private readonly CareEpisodeReader $episodes,
        private readonly ThresholdEvaluator $evaluator,
        private readonly VitalSignRepository $vitalSigns,
        private readonly AlertNotifier $notifier,
    ) {
    }

    /** @return array{vital_sign_id: string, code: string, severity: string, findings: array<string, string>} */
    public function execute(string $userId, string $episodeId, VitalSigns $vitals, string $measuredAt): array
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $measuredAt);
        if ($parsed === false || $parsed->format('Y-m-d H:i:s') !== $measuredAt) {
            throw new VitalSignViolation('MEASURED_AT_INVALID', 422);
        }
        $context = $this->episodes->contextFor($userId, $episodeId) ?? throw new VitalSignViolation('NOT_TREATING_PHYSICIAN', 403);
        $context->open || throw new VitalSignViolation('EPISODE_CLOSED', 409);
        // Solo existe el perfil adulto-2026.1: sin perfil aplicable se guarda sin evaluar (RF-SV-06).
        $assessment = $context->patientAge >= self::ADULT_AGE ? $this->evaluator->evaluate($vitals) : null;
        $id = $this->vitalSigns->record($episodeId, $userId, $vitals, $measuredAt, $assessment);
        if ($assessment?->requiresAlert()) {
            $this->notifier->notify($id, $assessment);
        }
        return [
            'vital_sign_id' => $id,
            'code' => $assessment === null ? 'THRESHOLDS_NOT_APPLICABLE' : ($assessment->requiresAlert() ? 'ALERT_GENERATED' : 'OK'),
            'severity' => strtolower(($assessment?->severity)?->name ?? 'none'),
            'findings' => array_map(static fn ($s): string => strtolower($s->name), $assessment?->findings ?? []),
        ];
    }
}
