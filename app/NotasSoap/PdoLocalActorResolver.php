<?php

declare(strict_types=1);

namespace App\NotasSoap;

use Closure;
use PDO;

/** Busca en local_user_refs de la base HOSPITAL la referencia activa y vigente del usuario CENTRAL. */
final class PdoLocalActorResolver implements LocalActorResolver
{
    /** @param Closure(): PDO $connect se invoca en el primer uso, no al construir el controlador */
    public function __construct(private readonly Closure $connect)
    {
    }

    public function reference(string $centralUserId): ?string
    {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $centralUserId) !== 1) {
            return null;
        }
        $statement = ($this->connect)()->prepare("SELECT local_user_ref_id FROM local_user_refs WHERE central_user_id = CAST(:u AS uuid)
            AND status = 'active' AND (valid_to IS NULL OR valid_to > now()) ORDER BY synced_at DESC LIMIT 1");
        $statement->execute(['u' => $centralUserId]);
        $reference = $statement->fetchColumn();

        return $reference === false ? null : (string) $reference;
    }
}
