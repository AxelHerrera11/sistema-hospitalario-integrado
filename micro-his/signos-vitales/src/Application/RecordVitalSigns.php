<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Application;

use MicroHis\SignosVitales\Domain\Assessment;
use MicroHis\SignosVitales\Domain\ThresholdEvaluator;
use MicroHis\SignosVitales\Domain\VitalSigns;
use MicroHis\SignosVitales\Domain\VitalSignViolation;

/** UC-SV-01 a 05: captura, evaluación de umbrales y generación de alerta (RF-SV-01 a RF-SV-07). */
final class RecordVitalSigns
{
    public const ADULT_AGE = 18;
    /** Tolerancia entre el reloj del cliente y el del servidor para aceptar una `measured_at` casi actual. */
    public const CLOCK_SKEW = '+5 minutes';

    /** @var \Closure(): \DateTimeImmutable */
    private readonly \Closure $clock;

    /**
     * @param (\Closure(): \DateTimeImmutable)|null $clock hora del servidor; por defecto, la actual
     * @param (\Closure(string, \Throwable): void)|null $onNotificationFailure registra el fallo del canal (id de la medición y causa), sin valores clínicos
     */
    public function __construct(
        private readonly CareEpisodeReader $episodes,
        private readonly ThresholdEvaluator $evaluator,
        private readonly VitalSignRepository $vitalSigns,
        private readonly AlertNotifier $notifier,
        ?\Closure $clock = null,
        private readonly ?\Closure $onNotificationFailure = null,
    ) {
        $this->clock = $clock ?? static fn (): \DateTimeImmutable => new \DateTimeImmutable();
    }

    /** @param array<string, float> $measurements signos medidos; el dominio los valida (VitalSigns)
     * @return array{vital_sign_id: string, code: string, severity: string, findings: array<string, string>, notification: string} notification: not_required, queued o failed */
    public function execute(string $userId, string $episodeId, array $measurements, string $measuredAt): array
    {
        $vitals = new VitalSigns($measurements);
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $measuredAt);
        if ($parsed === false || $parsed->format('Y-m-d H:i:s') !== $measuredAt || $parsed > ($this->clock)()->modify(self::CLOCK_SKEW)) {
            throw new VitalSignViolation('MEASURED_AT_INVALID', 422);
        }
        $context = $this->episodes->contextFor($userId, $episodeId) ?? throw new VitalSignViolation('NOT_TREATING_PHYSICIAN', 403);
        $context->open || throw new VitalSignViolation('EPISODE_CLOSED', 409);
        // Solo existe el perfil adulto-2026.1: sin perfil aplicable se guarda sin evaluar (RF-SV-06).
        $assessment = $context->patientAge >= self::ADULT_AGE ? $this->evaluator->evaluate($vitals) : null;
        $id = $this->vitalSigns->record($episodeId, $userId, $vitals, $measuredAt, $assessment);
        return [
            'vital_sign_id' => $id,
            'code' => $assessment === null ? 'THRESHOLDS_NOT_APPLICABLE' : ($assessment->requiresAlert() ? 'ALERT_GENERATED' : 'OK'),
            'severity' => strtolower(($assessment?->severity)?->name ?? 'none'),
            'findings' => array_map(static fn ($s): string => strtolower($s->name), $assessment?->findings ?? []),
            'notification' => $assessment?->requiresAlert() ? $this->notify($id, $assessment) : 'not_required',
        ];
    }

    /** La medición ya está confirmada: un fallo del canal no la deshace ni se informa como fallo de persistencia (RF-SV-07). */
    private function notify(string $id, Assessment $assessment): string
    {
        try {
            $this->notifier->notify($id, $assessment);
            return 'queued';
        } catch (\Throwable $e) {
            $this->onNotificationFailure === null || ($this->onNotificationFailure)($id, $e);
            return 'failed';
        }
    }
}
