<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Persistence;

use MicroHis\SignosVitales\Application\CareEpisodeReader;
use MicroHis\SignosVitales\Application\EpisodeContext;
use PDO;

/** Adaptador PDO de solo lectura sobre care_episodes y care_assignments (tablas de Admisión). */
final class PdoCareEpisodeReader implements CareEpisodeReader
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function contextFor(string $userId, string $episodeId): ?EpisodeContext
    {
        $stmt = $this->pdo->prepare("SELECT e.status, e.patient_birth_date FROM care_episodes e JOIN care_assignments a ON a.care_episode_id = e.care_episode_id
            WHERE a.user_ref_id = ? AND e.care_episode_id = ? AND a.status = 'active'");
        $stmt->execute([$userId, $episodeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : new EpisodeContext($row['status'] === 'open', (new \DateTimeImmutable($row['patient_birth_date']))->diff(new \DateTimeImmutable())->y);
    }
}
