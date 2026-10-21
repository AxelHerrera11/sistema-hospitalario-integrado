<?php

declare(strict_types=1);

namespace App\Rbac;

use Illuminate\Support\Facades\DB;
use MicroHis\Rbac\Domain\RoleResolver;

/** Solo lectura del esquema existente; no crea tablas ni replica membresías. */
final class CentralRoles implements RoleResolver
{
    public function rolesIn(string $userId, string $hospitalId): array
    {
        return DB::connection('central')->table('hospital_memberships as m')
            ->join('central_users as u', 'u.user_id', '=', 'm.user_id')
            ->join('hospitals as h', 'h.hospital_id', '=', 'm.hospital_id')
            ->where('m.user_id', $userId)->where('m.hospital_id', $hospitalId)
            ->where('u.status', 'active')->where('h.status', 'active')->where('m.status', 'active')
            ->where('m.valid_from', '<=', now())
            ->where(fn ($q) => $q->whereNull('m.valid_to')->orWhere('m.valid_to', '>', now()))
            ->distinct()->pluck('m.role_code')->all();
    }
}
