<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Persistence;

use MicroHis\NotasSoap\Application\CareAssignmentReader;
use PDO;

/** Lectura de care_assignments, tabla de Episodios: este módulo nunca escribe en ella. */
final class PdoCareAssignmentReader implements CareAssignmentReader
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function isTreating(string $userId, string $episodeId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM care_assignments WHERE user_ref_id = ? AND care_episode_id = ? AND status = 'active'");
        $stmt->execute([$userId, $episodeId]);
        return $stmt->fetchColumn() !== false;
    }
}
