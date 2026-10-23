<?php

declare(strict_types=1);

namespace App\TrasladosAltas;

use Closure;
use PDO;

/** Busca en local_user_refs de la base HOSPITAL la referencia activa y vigente del usuario CENTRAL. */
final class PdoResolutorActorLocal implements ResolutorActorLocal
{
    /** @param Closure(): PDO $conectar se invoca en el primer uso, no al construir el controller */
    public function __construct(private readonly Closure $conectar)
    {
    }

    public function referencia(string $centralUserId): ?string
    {
        $sentencia = ($this->conectar)()->prepare("SELECT local_user_ref_id FROM local_user_refs WHERE central_user_id = CAST(:u AS uuid)
            AND status = 'active' AND (valid_to IS NULL OR valid_to > now()) ORDER BY synced_at DESC LIMIT 1");
        $sentencia->execute(['u' => $centralUserId]);
        $referencia = $sentencia->fetchColumn();

        return $referencia === false ? null : (string) $referencia;
    }
}
