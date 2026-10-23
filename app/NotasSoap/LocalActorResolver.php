<?php

declare(strict_types=1);

namespace App\NotasSoap;

/**
 * Traduce el usuario CENTRAL autenticado (el `sub` del JWT) a su referencia local en HOSPITAL (local_user_refs), que es la
 * que clinical_notes.author_user_ref_id y local_access_audits.actor_user_ref_id exigen. Solo lee: sincronizar esas
 * referencias no es responsabilidad de ASII-11.
 */
interface LocalActorResolver
{
    /** null si el usuario no tiene una referencia local activa y vigente en este hospital. */
    public function reference(string $centralUserId): ?string;
}
