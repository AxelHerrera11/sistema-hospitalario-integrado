<?php

declare(strict_types=1);

namespace App\Medicos;

use Illuminate\Support\Facades\DB;

/**
 * Traduce el usuario autenticado (CENTRAL) a su referencia local en HOSPITAL.
 * `local_access_audits.actor_user_ref_id` y `doctor_profiles.local_user_ref_id` apuntan a `local_user_refs`, no al id del JWT.
 */
class HospitalActors
{
    public function localUserRef(string $centralUserId): ?string
    {
        $ref = DB::connection('hospital')->table('local_user_refs')
            ->where('central_user_id', $centralUserId)->where('status', 'active')->value('local_user_ref_id');

        return is_string($ref) ? $ref : null;
    }
}
