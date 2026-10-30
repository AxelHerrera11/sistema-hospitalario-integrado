<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Infrastructure\Persistence;

use App\Modules\Tenants\Domain\Identity\CanonicalRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class MembershipRepository
{
    public function __construct(private readonly string $connection = 'central') {}

    public function activeRoles(string $userId, string $hospitalId): array
    {
        $codes = DB::connection($this->connection)
            ->table('hospital_memberships as m')
            ->join('central_users as u', 'u.user_id', '=', 'm.user_id')
            ->join('hospitals as h', 'h.hospital_id', '=', 'm.hospital_id')
            ->where('m.user_id', $userId)
            ->where('m.hospital_id', $hospitalId)
            ->where('u.status', 'active')
            ->where('h.status', 'active')
            ->where('m.status', 'active')
            ->whereRaw('m.valid_from <= CURRENT_TIMESTAMP')
            ->where(fn ($query) => $query
                ->whereNull('m.valid_to')
                ->orWhereRaw('m.valid_to > CURRENT_TIMESTAMP'))
            ->distinct()
            ->pluck('m.role_code')
            ->all();

        return CanonicalRole::canonicalize($codes);
    }

    public function hasActiveMembership(string $userId, string $hospitalId): bool
    {
        return $this->activeRoles($userId, $hospitalId) !== [];
    }

    public function hasActiveRole(string $userId, string $hospitalId, string $roleCode): bool
    {
        return DB::connection($this->connection)
            ->table('hospital_memberships')
            ->where('user_id', $userId)
            ->where('hospital_id', $hospitalId)
            ->where('role_code', $roleCode)
            ->where('status', 'active')
            ->whereRaw('valid_from <= CURRENT_TIMESTAMP')
            ->where(fn ($query) => $query
                ->whereNull('valid_to')
                ->orWhereRaw('valid_to > CURRENT_TIMESTAMP'))
            ->exists();
    }

    public function assignRole(string $userId, string $hospitalId, string $roleCode): string
    {
        $membershipId = (string) Str::uuid();

        DB::connection($this->connection)->table('hospital_memberships')->insert([
            'membership_id' => $membershipId,
            'user_id' => $userId,
            'hospital_id' => $hospitalId,
            'role_code' => $roleCode,
            'status' => 'active',
            'valid_from' => now(),
            'valid_to' => null,
            'metadata' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $membershipId;
    }

    public function revokeRole(string $userId, string $hospitalId, string $roleCode): int
    {
        return DB::connection($this->connection)
            ->table('hospital_memberships')
            ->where('user_id', $userId)
            ->where('hospital_id', $hospitalId)
            ->where('role_code', $roleCode)
            ->where('status', 'active')
            ->whereRaw('valid_from <= CURRENT_TIMESTAMP')
            ->where(fn ($query) => $query
                ->whereNull('valid_to')
                ->orWhereRaw('valid_to > CURRENT_TIMESTAMP'))
            ->update([
                'status' => 'revoked',
                'valid_to' => now(),
                'updated_at' => now(),
            ]);
    }

    public function deactivateInHospital(string $userId, string $hospitalId): int
    {
        return DB::connection($this->connection)
            ->table('hospital_memberships')
            ->where('user_id', $userId)
            ->where('hospital_id', $hospitalId)
            ->where('status', 'active')
            ->update([
                'status' => 'inactive',
                'updated_at' => now(),
            ]);
    }
}
