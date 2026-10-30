<?php

declare(strict_types=1);

namespace Tests\Feature\Alergias;

use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use Closure;
use Illuminate\Http\Request;
use Tests\Feature\TrasladosAltas\TrasladosTestIdentity;

/** Sustituto de jwt.auth: deja como tenant el modelo Hospital real que exige EnsureHospitalDatabaseTenant (#1154). */
final class HospitalTenantIdentity
{
    public function handle(Request $request, Closure $next)
    {
        $request->attributes->set('tenant', new Hospital(['hospital_id' => TrasladosTestIdentity::$hospital]));

        return $next($request);
    }
}
