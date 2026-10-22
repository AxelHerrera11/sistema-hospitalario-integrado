<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Persistence;

use MicroHis\TrasladosAltas\Application\Autorizador;
use MicroHis\TrasladosAltas\Application\Operacion;
use PDO;
use PDOException;

/**
 * Autorización MÍNIMA del micro-HIS independiente: el actor debe existir en local_user_refs, estar activo y vigente.
 * No decide por rol ni por operación: esa matriz es de RBAC (ASII-02) y se conecta al integrar con Laravel.
 * Falla cerrada: si HOSPITAL no responde, no autoriza.
 */
final class AutorizadorReferenciaActiva implements Autorizador
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function permite(string $actorRefId, Operacion $operacion): bool
    {
        try {
            $sentencia = $this->pdo->prepare("SELECT 1 FROM local_user_refs WHERE local_user_ref_id = CAST(:id AS uuid)
                AND status = 'active' AND (valid_to IS NULL OR valid_to > now())");
            $sentencia->execute(['id' => $actorRefId]);

            return $sentencia->fetchColumn() !== false;
        } catch (PDOException) {
            return false;
        }
    }
}
