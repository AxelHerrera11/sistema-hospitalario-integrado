<?php

declare(strict_types=1);

namespace MicroHis\Alertas\Persistence;

use MicroHis\Alertas\Application\AlertRepository;
use MicroHis\Alertas\Application\PersistenceFailure;
use MicroHis\Alertas\Domain\CriticalAlert;

/** Adaptador en memoria: mismo contrato que PdoAlertRepository, para pruebas y para trabajar sin base. */
final class InMemoryAlertRepository implements AlertRepository
{
    /** @var array<string, array<string, mixed>> filas como en critical_alerts, por id */
    public array $rows = [];
    /** @var list<array{0: string, 1: string, 2: string}> evento, actor, id */
    public array $audit = [];
    public bool $failWrites = false; // simula una base caída

    /** @param array<string, array{critical_low: float, critical_high: float}> $thresholds @param list<array{0: string, 1: string, 2: string}> $care episodio, paciente, usuario (activas) */
    public function __construct(private readonly array $thresholds, private readonly array $care) {}

    public function thresholds(string $labTestId): ?array { return $this->thresholds[$labTestId] ?? null; }

    public function attending(string $episodeId): ?string
    {
        return array_values(array_filter($this->care, static fn (array $c): bool => $c[0] === $episodeId))[0][2] ?? null;
    }

    public function caresFor(string $user, string $patientId): bool { return in_array([$patientId, $user], array_map(static fn (array $c): array => [$c[1], $c[2]], $this->care), true); }

    public function find(string $alertId): ?CriticalAlert { return isset($this->rows[$alertId]) ? CriticalAlert::fromRow($this->rows[$alertId]) : null; }

    public function pendingFor(string $user): array
    {
        $rows = array_filter($this->rows, static fn (array $r): bool => $r['current_recipient'] === $user && $r['status'] === 'open');
        usort($rows, static fn (array $a, array $b): int => $a['due_at'] <=> $b['due_at']);
        return array_map([CriticalAlert::class, 'fromRow'], $rows);
    }

    public function save(CriticalAlert $a, string $event, string $actor): void
    {
        // Mismas garantías que el UNIQUE por resultado y el UPDATE … WHERE status = 'open'.
        $duplicate = $a->status === 'open' ? in_array($a->resultVersionId, array_column($this->rows, 'lab_result_version_id'), true)
            : ($this->rows[$a->id]['status'] ?? null) !== 'open';
        if ($this->failWrites || $duplicate) {
            throw new PersistenceFailure(!$this->failWrites);
        }
        $this->rows[$a->id] = ['critical_alert_id' => $a->id, 'lab_result_version_id' => $a->resultVersionId, 'local_patient_id' => $a->patientId,
            'lab_test_id' => $a->labTestId, 'measured_value' => $a->value, 'direction' => $a->direction, 'current_recipient' => $a->recipient,
            'escalation_level' => $a->level, 'due_at' => $a->dueAt, 'status' => $a->status, 'acknowledged_by_user_ref_id' => $a->acknowledgedBy];
        $this->audit[] = [$event, $actor, $a->id];
    }
}
