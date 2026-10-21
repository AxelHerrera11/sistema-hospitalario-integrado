<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

final class RefreshTokenRepository
{
    public function __construct(private readonly string $connection = 'central') {}

    public function issue(
        string $sessionId,
        string $userId,
        string $hospitalId,
        string $familyId,
        int $ttlSeconds,
    ): string {
        $token = bin2hex(random_bytes(32));
        $issuedAt = now();

        DB::connection($this->connection)->table('auth_refresh_tokens')->insert([
            'refresh_token_id' => (string) Str::uuid(),
            'family_id' => $familyId,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'hospital_id' => $hospitalId,
            'token_hash' => hash('sha256', $token),
            'issued_at' => $issuedAt,
            'expires_at' => $issuedAt->copy()->addSeconds($ttlSeconds),
            'consumed_at' => null,
            'revoked_at' => null,
            'revoked_reason' => null,
            'created_at' => $issuedAt,
            'updated_at' => $issuedAt,
        ]);

        return $token;
    }

    public function findForUpdate(string $token): ?stdClass
    {
        return DB::connection($this->connection)
            ->table('auth_refresh_tokens')
            ->where('token_hash', hash('sha256', $token))
            ->lockForUpdate()
            ->first();
    }

    public function consume(string $tokenId): int
    {
        return DB::connection($this->connection)->table('auth_refresh_tokens')
            ->where('refresh_token_id', $tokenId)
            ->whereNull('consumed_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->update(['consumed_at' => now(), 'updated_at' => now()]);
    }

    public function revokeFamily(string $familyId, string $reason): int
    {
        return DB::connection($this->connection)->table('auth_refresh_tokens')
            ->where('family_id', $familyId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => $reason,
                'updated_at' => now(),
            ]);
    }

    public function revokeSession(string $sessionId, string $reason): int
    {
        return DB::connection($this->connection)->table('auth_refresh_tokens')
            ->where('session_id', $sessionId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => $reason,
                'updated_at' => now(),
            ]);
    }

    public function revokeForUserInHospital(string $userId, string $hospitalId, string $reason): int
    {
        return DB::connection($this->connection)->table('auth_refresh_tokens')
            ->where('user_id', $userId)
            ->where('hospital_id', $hospitalId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => $reason,
                'updated_at' => now(),
            ]);
    }
}
