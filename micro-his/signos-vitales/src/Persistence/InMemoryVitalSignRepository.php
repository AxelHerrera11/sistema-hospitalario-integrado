<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Persistence;

use MicroHis\SignosVitales\Application\StoredVitalSign;
use MicroHis\SignosVitales\Application\VitalSignRepository;
use MicroHis\SignosVitales\Domain\Assessment;
use MicroHis\SignosVitales\Domain\Uuid;
use MicroHis\SignosVitales\Domain\VitalSigns;

/** Adaptador InMemory del Repository: mismo contrato que PDO, para pruebas y para correr el MVC sin base de datos. */
final class InMemoryVitalSignRepository implements VitalSignRepository
{
    /** @var array<string, StoredVitalSign> */
    private array $rows = [];
    /** @var list<array{event: string, actor_id: string, detail: string}> */
    public array $audits = [];

    public function __construct(private readonly bool $available = true)
    {
    }

    public function record(string $episodeId, string $userId, VitalSigns $vitals, string $measuredAt, ?Assessment $assessment): string
    {
        $this->available || throw new \RuntimeException('persistencia_no_disponible');
        $id = Uuid::v4();
        $this->rows[$id] = new StoredVitalSign($id, $episodeId, $userId, $measuredAt, $vitals);
        $this->audits[] = ['event' => 'signo_vital.registrado', 'actor_id' => $userId, 'detail' => $id];
        if ($assessment === null) {
            $this->audits[] = ['event' => 'signo_vital.sin_evaluacion', 'actor_id' => $userId, 'detail' => $id . ':THRESHOLDS_NOT_APPLICABLE'];
        } elseif ($assessment->requiresAlert()) {
            $this->audits[] = ['event' => 'signo_vital.alerta_generada', 'actor_id' => $userId, 'detail' => $id . ':' . strtolower($assessment->severity->name) . ':' . implode(',', array_keys($assessment->findings))];
        }
        return $id;
    }

    public function find(string $vitalSignId): ?StoredVitalSign
    {
        return $this->rows[$vitalSignId] ?? null;
    }
}
