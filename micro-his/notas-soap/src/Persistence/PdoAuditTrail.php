<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Persistence;

use MicroHis\NotasSoap\Application\AuditTrail;
use PDO;

/** Auditoría en local_access_audits. Comparte la conexión del repositorio para entrar en su misma transacción. */
final class PdoAuditTrail implements AuditTrail
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function record(string $event, string $actorId, string $detail): void
    {
        $this->pdo->prepare('INSERT INTO local_access_audits (event, actor_id, detail) VALUES (?, ?, ?)')->execute([$event, $actorId, $detail]);
    }
}
