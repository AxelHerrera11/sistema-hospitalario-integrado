<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Application;

use MicroHis\SignosVitales\Domain\VitalSignViolation;

/** Consulta de una medición. Es dato clínico: además del permiso RBAC exige asignación activa en el episodio de la medición. */
final class GetVitalSign
{
    public function __construct(private readonly CareEpisodeReader $episodes, private readonly VitalSignRepository $vitalSigns)
    {
    }

    /** @return array{vital_sign_id: string, care_episode_id: string, measured_at: string, values: array<string, float>} */
    public function execute(string $userId, string $vitalSignId): array
    {
        $stored = $this->vitalSigns->find($vitalSignId) ?? throw new VitalSignViolation('VITAL_SIGN_NOT_FOUND', 404);
        $this->episodes->contextFor($userId, $stored->episodeId) ?? throw new VitalSignViolation('NOT_TREATING_PHYSICIAN', 403);
        return ['vital_sign_id' => $stored->id, 'care_episode_id' => $stored->episodeId, 'measured_at' => $stored->measuredAt, 'values' => $stored->vitals->values];
    }
}
