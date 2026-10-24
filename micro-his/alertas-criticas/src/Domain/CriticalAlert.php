<?php

declare(strict_types=1);

namespace MicroHis\Alertas\Domain;

/** Alerta crítica de laboratorio (UC-ALR-01, 02 y 04). */
final class CriticalAlert
{
    public const SERVICE_HEAD = 'jefatura.servicio';

    private function __construct(
        public readonly string $id, public readonly string $resultVersionId, public readonly string $patientId,
        public readonly string $labTestId, public readonly float $value, public readonly string $direction,
        public readonly string $recipient, public readonly int $level, public readonly string $dueAt,
        public string $status = 'open', public ?string $acknowledgedBy = null, public readonly ?string $episodeId = null,
    ) {}

    /** Null si la prueba no define umbrales o el valor no los supera. Sin médico tratante, va a la jefatura (nivel 1). */
    public static function detect(string $id, array $event, ?array $thresholds, ?string $attending, \DateTimeImmutable $now): ?self
    {
        $direction = match (true) {
            $thresholds === null => null,
            $event['value'] < $thresholds['critical_low'] => 'low',
            $event['value'] > $thresholds['critical_high'] => 'high',
            default => null,
        };
        return $direction === null ? null : new self($id, $event['result_version_id'], $event['patient_id'], $event['lab_test_id'],
            (float) $event['value'], $direction, $attending ?? self::SERVICE_HEAD, $attending === null ? 1 : 0, $now->modify('+15 minutes')->format(DATE_ATOM),
            episodeId: $event['episode_id']);
    }

    public static function fromRow(array $r): self
    {
        return new self($r['critical_alert_id'], $r['lab_result_version_id'], $r['local_patient_id'], $r['lab_test_id'], (float) $r['measured_value'],
            $r['direction'], $r['current_recipient'], (int) $r['escalation_level'], $r['due_at'], $r['status'], $r['acknowledged_by_user_ref_id'],
            $r['care_episode_id'] ?? null);
    }

    /** Acuse con lectura de verificación de paciente, prueba y valor. @param array<string, mixed> $readBack */
    public function acknowledge(string $user, array $readBack, bool $caresForPatient): void
    {
        $rule = match (true) {
            $this->status !== 'open' => ['ALERT_ALREADY_ACKNOWLEDGED', 'status'],
            $user !== $this->recipient && !$caresForPatient => ['NO_CARE_RELATION', 'user'],
            ($readBack['patient_id'] ?? null) !== $this->patientId || ($readBack['lab_test_id'] ?? null) !== $this->labTestId
                || !is_numeric($readBack['value'] ?? null) || (float) $readBack['value'] !== $this->value => ['READ_BACK_MISMATCH', 'read_back'],
            default => null,
        };
        if ($rule !== null) {
            throw new DomainRuleViolation(...$rule);
        }
        [$this->status, $this->acknowledgedBy] = ['acknowledged', $user];
    }
}
