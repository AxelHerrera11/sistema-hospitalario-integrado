<?php

declare(strict_types=1);

namespace App\TrasladosAltas;

/**
 * Traduce el usuario CENTRAL autenticado (JWT) a su referencia local en HOSPITAL (local_user_refs), que es la que
 * bed_stays.assigned_by_user_ref_id exige. Solo lee: crear o sincronizar esas referencias no es responsabilidad de ASII-08.
 */
interface ResolutorActorLocal
{
    /** null si el usuario no tiene una referencia local activa y vigente en este hospital. */
    public function referencia(string $centralUserId): ?string;
}
