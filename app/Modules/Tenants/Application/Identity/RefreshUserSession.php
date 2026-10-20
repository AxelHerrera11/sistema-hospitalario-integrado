<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Application\Identity;

use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Identity\AuthFailure;
use App\Modules\Tenants\Domain\Identity\CanonicalRole;
use App\Modules\Tenants\Infrastructure\Persistence\CentralUser;
use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use App\Modules\Tenants\Infrastructure\Persistence\MembershipRepository;
use App\Modules\Tenants\Infrastructure\Persistence\RefreshTokenRepository;
use App\Modules\Tenants\Infrastructure\Persistence\SessionRepository;
use App\Modules\Tenants\Infrastructure\Security\JwtAccessTokenIssuer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RefreshUserSession
{
    public function __construct(
        private readonly MembershipRepository $memberships,
        private readonly SessionRepository $sessions,
        private readonly RefreshTokenRepository $refreshTokens,
        private readonly JwtAccessTokenIssuer $jwt,
        private readonly AuditLogRepositoryInterface $audit,
    ) {}

    public function execute(string $opaqueToken, Hospital $hospital): array
    {
        $hospitalId = (string) $hospital->getKey();

        if ($opaqueToken === '') {
            $this->recordRefreshFailure($hospitalId, null, 'refresh_token_missing');
            throw AuthFailure::invalidRefreshToken();
        }

        $result = DB::connection('central')->transaction(function () use (
            $opaqueToken,
            $hospital,
            $hospitalId,
        ): array {
            $refresh = $this->refreshTokens->findForUpdate($opaqueToken);

            if ($refresh === null) {
                $this->recordRefreshFailure($hospitalId, null, 'refresh_token_unknown');

                return ['failure' => AuthFailure::invalidRefreshToken()];
            }

            if ((string) $refresh->hospital_id !== $hospitalId) {
                $this->recordRefreshFailure($hospitalId, null, 'hospital_mismatch');

                return ['failure' => AuthFailure::invalidRefreshToken()];
            }

            if ($refresh->consumed_at !== null && $refresh->revoked_reason !== 'logout') {
                $this->revokeReusedFamily($refresh, $hospitalId);

                return ['failure' => AuthFailure::refreshTokenReused()];
            }

            if ($refresh->revoked_at !== null || now()->greaterThanOrEqualTo($refresh->expires_at)) {
                $this->refreshTokens->revokeFamily((string) $refresh->family_id, 'refresh_expired');
                $this->recordRefreshFailure($hospitalId, (string) $refresh->user_id, 'refresh_expired');

                return ['failure' => AuthFailure::invalidRefreshToken()];
            }

            $user = CentralUser::query()->find((string) $refresh->user_id);

            if ($user === null || $user->status !== 'active') {
                $this->revokeFamilyAndSession($refresh, $hospitalId, 'account_inactive');
                $this->recordRefreshFailure($hospitalId, (string) $refresh->user_id, 'account_inactive');

                return ['failure' => AuthFailure::inactiveUser()];
            }

            $roles = $this->memberships->activeRoles((string) $user->getKey(), $hospitalId);

            if ($roles === []) {
                $this->revokeFamilyAndSession($refresh, $hospitalId, 'membership_revoked');
                $this->recordRefreshFailure($hospitalId, (string) $user->getKey(), 'membership_revoked');

                return ['failure' => AuthFailure::noActiveRoles()];
            }

            $session = $this->sessions->findUnrevokedForRefresh(
                (string) $refresh->session_id,
                (string) $user->getKey(),
                $hospitalId,
            );

            if ($session === null) {
                $this->refreshTokens->revokeFamily((string) $refresh->family_id, 'session_revoked');
                $this->recordRefreshFailure($hospitalId, (string) $user->getKey(), 'session_revoked');

                return ['failure' => AuthFailure::invalidRefreshToken()];
            }

            if ($this->refreshTokens->consume((string) $refresh->refresh_token_id) !== 1) {
                $this->revokeReusedFamily($refresh, $hospitalId);

                return ['failure' => AuthFailure::refreshTokenReused()];
            }

            $issuedAt = now()->toImmutable();
            $jti = (string) Str::uuid();
            $token = $this->jwt->issue($user, $hospitalId, $roles, (string) $session->session_id, $jti);
            $expiresAt = $issuedAt->addSeconds((int) $token['expires_in']);

            if ($this->sessions->rotate(
                (string) $session->session_id,
                (string) $session->jti,
                (string) $user->getKey(),
                $hospitalId,
                $jti,
                $issuedAt,
                $expiresAt,
            ) !== 1) {
                $this->revokeFamilyAndSession($refresh, $hospitalId, 'rotation_conflict');
                $this->recordRefreshFailure($hospitalId, (string) $user->getKey(), 'rotation_conflict');

                return ['failure' => AuthFailure::refreshTokenReused()];
            }

            $nextRefreshToken = $this->refreshTokens->issue(
                (string) $session->session_id,
                (string) $user->getKey(),
                $hospitalId,
                (string) $refresh->family_id,
                (int) config('tenants.refresh.ttl_seconds', 604800),
            );
            $traceId = 'trace-'.bin2hex(random_bytes(6));
            $this->audit->logRaw('auth.refresh_succeeded', [
                'user_id' => (string) $user->getKey(),
                'hospital_id' => $hospitalId,
                'tenant_id' => $hospitalId,
                'reason' => 'refresh_rotated',
                'result' => 'success',
                'trace_id' => $traceId,
            ], 'info');

            return [
                'access_token' => $token['access_token'],
                'token_type' => $token['token_type'],
                'expires_in' => $token['expires_in'],
                'refresh_token' => $nextRefreshToken,
                'user' => [
                    'user_id' => (string) $user->getKey(),
                    'email' => $user->email,
                    'display_name' => $user->display_name,
                ],
                'hospital' => [
                    'hospital_id' => $hospitalId,
                    'name' => $hospital->name,
                ],
                'roles' => CanonicalRole::canonicalize($roles),
                'roles_version' => CanonicalRole::version($roles),
            ];
        });

        if (isset($result['failure'])) {
            throw $result['failure'];
        }

        return $result;
    }

    private function revokeReusedFamily(object $refresh, string $hospitalId): void
    {
        $this->revokeFamilyAndSession($refresh, $hospitalId, 'refresh_token_reused');
        $this->audit->logRaw('auth.refresh_reuse_detected', [
            'user_id' => (string) $refresh->user_id,
            'hospital_id' => $hospitalId,
            'tenant_id' => $hospitalId,
            'reason' => 'refresh_token_reused',
            'result' => 'denied',
            'trace_id' => 'trace-'.bin2hex(random_bytes(6)),
        ], 'critical');
    }

    private function recordRefreshFailure(string $hospitalId, ?string $userId, string $reason): void
    {
        $payload = [
            'hospital_id' => $hospitalId,
            'tenant_id' => $hospitalId,
            'reason' => $reason,
            'result' => 'denied',
            'trace_id' => 'trace-'.bin2hex(random_bytes(6)),
        ];

        if ($userId !== null) {
            $payload['user_id'] = $userId;
        }

        $this->audit->logRaw('auth.refresh_failed', $payload, 'warning');
    }

    private function revokeFamilyAndSession(object $refresh, string $hospitalId, string $reason): void
    {
        $this->refreshTokens->revokeFamily((string) $refresh->family_id, $reason);
        $this->sessions->revokeById((string) $refresh->session_id, $hospitalId, $reason);
    }
}
