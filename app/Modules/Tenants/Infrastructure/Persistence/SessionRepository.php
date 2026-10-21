<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Infrastructure\Persistence;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use stdClass;

final class SessionRepository
{
    public function __construct(private readonly string $connection = 'central') {}

    public function create(
        string $sessionId,
        string $userId,
        string $hospitalId,
        string $jti,
        CarbonImmutable $issuedAt,
        CarbonImmutable $expiresAt,
    ): void {
        DB::connection($this->connection)->table('auth_sessions')->insert([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'hospital_id' => $hospitalId,
            'jti' => $jti,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
            'revoked_at' => null,
            'revoked_reason' => null,
            'created_at' => $issuedAt,
            'updated_at' => $issuedAt,
        ]);
    }

    public function findActive(
        string $sessionId,
        string $jti,
        string $userId,
        string $hospitalId,
    ): ?stdClass {
        return DB::connection($this->connection)
            ->table('auth_sessions')
            ->where('session_id', $sessionId)
            ->where('jti', $jti)
            ->where('user_id', $userId)
            ->where('hospital_id', $hospitalId)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function revoke(
        string $sessionId,
        string $jti,
        string $userId,
        string $hospitalId,
        string $reason,
    ): int {
        $revoked = DB::connection($this->connection)
            ->table('auth_sessions')
            ->where('session_id', $sessionId)
            ->where('jti', $jti)
            ->where('user_id', $userId)
            ->where('hospital_id', $hospitalId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => $reason,
                'updated_at' => now(),
            ]);

        if ($revoked === 1) {
            $this->revokeRefreshTokensForSession($sessionId, $reason);
        }

        return $revoked;
    }

    public function revokeAllForUserInHospital(string $userId, string $hospitalId, string $reason): int
    {
        $connection = DB::connection($this->connection);
        $revoked = $connection
            ->table('auth_sessions')
            ->where('user_id', $userId)
            ->where('hospital_id', $hospitalId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => $reason,
                'updated_at' => now(),
            ]);

        $connection->table('auth_refresh_tokens')
            ->where('user_id', $userId)
            ->where('hospital_id', $hospitalId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => $reason,
                'updated_at' => now(),
            ]);

        return $revoked;
    }

    public function revokeAllForUser(string $userId, string $reason): int
    {
        $connection = DB::connection($this->connection);
        $revoked = $connection
            ->table('auth_sessions')
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => $reason,
                'updated_at' => now(),
            ]);

        $connection->table('auth_refresh_tokens')
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => $reason,
                'updated_at' => now(),
            ]);

        return $revoked;
    }

    public function revokeForeignSession(
        string $sessionId,
        string $hospitalId,
        string $actorId,
        string $reason,
    ): ?string {
        $query = DB::connection($this->connection)
            ->table('auth_sessions')
            ->where('session_id', $sessionId)
            ->where('hospital_id', $hospitalId)
            ->where('user_id', '<>', $actorId)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());

        $session = (clone $query)->first(['user_id']);

        if ($session === null) {
            return null;
        }

        $updated = $query->update([
            'revoked_at' => now(),
            'revoked_reason' => $reason,
            'updated_at' => now(),
        ]);

        if ($updated === 1) {
            $this->revokeRefreshTokensForSession($sessionId, $reason);
        }

        return $updated === 1 ? (string) $session->user_id : null;
    }

    public function findUnrevokedForRefresh(
        string $sessionId,
        string $userId,
        string $hospitalId,
    ): ?stdClass {
        return DB::connection($this->connection)->table('auth_sessions')
            ->where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->where('hospital_id', $hospitalId)
            ->whereNull('revoked_at')
            ->lockForUpdate()
            ->first();
    }

    public function rotate(
        string $sessionId,
        string $oldJti,
        string $userId,
        string $hospitalId,
        string $newJti,
        CarbonImmutable $issuedAt,
        CarbonImmutable $expiresAt,
    ): int {
        return DB::connection($this->connection)->table('auth_sessions')
            ->where('session_id', $sessionId)
            ->where('jti', $oldJti)
            ->where('user_id', $userId)
            ->where('hospital_id', $hospitalId)
            ->whereNull('revoked_at')
            ->update([
                'jti' => $newJti,
                'issued_at' => $issuedAt,
                'expires_at' => $expiresAt,
                'updated_at' => $issuedAt,
            ]);
    }

    public function revokeById(string $sessionId, string $hospitalId, string $reason): int
    {
        $connection = DB::connection($this->connection);
        $revoked = $connection->table('auth_sessions')
            ->where('session_id', $sessionId)
            ->where('hospital_id', $hospitalId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => $reason,
                'updated_at' => now(),
            ]);

        $this->revokeRefreshTokensForSession($sessionId, $reason);

        return $revoked;
    }

    private function revokeRefreshTokensForSession(string $sessionId, string $reason): void
    {
        DB::connection($this->connection)->table('auth_refresh_tokens')
            ->where('session_id', $sessionId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => $reason,
                'updated_at' => now(),
            ]);
    }
}
