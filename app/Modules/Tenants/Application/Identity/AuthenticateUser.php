<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Application\Identity;

use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Identity\AuthFailure;
use App\Modules\Tenants\Domain\Identity\CanonicalRole;
use App\Modules\Tenants\Infrastructure\Persistence\CentralUser;
use App\Modules\Tenants\Infrastructure\Persistence\Credential;
use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use App\Modules\Tenants\Infrastructure\Persistence\MembershipRepository;
use App\Modules\Tenants\Infrastructure\Persistence\RefreshTokenRepository;
use App\Modules\Tenants\Infrastructure\Persistence\SessionRepository;
use App\Modules\Tenants\Infrastructure\Security\JwtAccessTokenIssuer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use MicroHis\Rbac\Domain\PermissionProvider;

final class AuthenticateUser
{
    public function __construct(
        private readonly MembershipRepository $memberships,
        private readonly SessionRepository $sessions,
        private readonly RefreshTokenRepository $refreshTokens,
        private readonly JwtAccessTokenIssuer $jwt,
        private readonly AuditLogRepositoryInterface $audit,
    ) {}

    public function login(string $email, string $password, Hospital $hospital): array
    {
        $hospitalId = (string) $hospital->getKey();
        $credential = Credential::query()
            ->where('email_normalized', mb_strtolower(trim($email)))
            ->first();

        if ($credential === null || ! Hash::check($password, (string) $credential->password_hash)) {
            $this->recordLoginFailure(null, $hospitalId, 'invalid_credentials');
            throw AuthFailure::invalidCredentials();
        }

        $user = CentralUser::query()->find((string) $credential->user_id);

        if ($user === null) {
            $this->recordLoginFailure(null, $hospitalId, 'invalid_credentials');
            throw AuthFailure::invalidCredentials();
        }

        if ($user->status !== 'active') {
            $this->recordLoginFailure((string) $user->getKey(), $hospitalId, 'user_inactive');
            throw AuthFailure::inactiveUser();
        }

        $roles = $this->memberships->activeRoles((string) $user->getKey(), $hospitalId);

        if ($roles === []) {
            $this->recordLoginFailure((string) $user->getKey(), $hospitalId, 'no_active_membership');
            throw AuthFailure::noActiveMembership();
        }

        $result = $this->issueSession($user, $hospitalId, $roles);

        return $result;
    }

    private function issueSession(CentralUser $user, string $hospitalId, array $roles): array
    {
        return DB::connection('central')->transaction(function () use ($user, $hospitalId, $roles): array {
            $sessionId = (string) Str::uuid();
            $jti = (string) Str::uuid();
            $issuedAt = now()->toImmutable();
            $token = $this->jwt->issue($user, $hospitalId, $roles, $sessionId, $jti);
            $expiresAt = $issuedAt->addSeconds((int) $token['expires_in']);

            $this->sessions->create(
                $sessionId,
                (string) $user->getKey(),
                $hospitalId,
                $jti,
                $issuedAt,
                $expiresAt,
            );
            $refreshToken = $this->refreshTokens->issue(
                $sessionId,
                (string) $user->getKey(),
                $hospitalId,
                (string) Str::uuid(),
                (int) config('tenants.refresh.ttl_seconds', 604800),
            );
            $user->forceFill(['last_authenticated_at' => $issuedAt])->save();
            $this->audit->logRaw('auth.login_succeeded', [
                'user_id' => (string) $user->getKey(),
                'hospital_id' => $hospitalId,
                'tenant_id' => $hospitalId,
                'reason' => 'credentials_verified',
                'result' => 'success',
                'trace_id' => $this->traceId(),
            ], 'info');

            return [
                'access_token' => $token['access_token'],
                'token_type' => $token['token_type'],
                'expires_in' => $token['expires_in'],
                'refresh_token' => $refreshToken,
                'user' => $this->userPayload($user),
                'hospital' => $this->hospitalPayload($hospitalId),
                'roles' => CanonicalRole::canonicalize($roles),
                'roles_version' => CanonicalRole::version($roles),
            ];
        });
    }

    private function recordLoginFailure(?string $userId, string $hospitalId, string $reason): void
    {
        $payload = [
            'hospital_id' => $hospitalId,
            'tenant_id' => $hospitalId,
            'reason' => $reason,
            'result' => 'denied',
            'trace_id' => $this->traceId(),
        ];

        if ($userId !== null) {
            $payload['user_id'] = $userId;
        }

        $this->audit->logRaw('auth.login_failed', $payload, 'warning');
    }

    private function traceId(): string
    {
        return 'trace-'.bin2hex(random_bytes(6));
    }

    public function userPayload(CentralUser $user): array
    {
        $permissions = [];
        $hospital = request()->attributes->get('tenant');
        if ($hospital instanceof Hospital) {
            $provider = app(PermissionProvider::class);
            foreach ($this->memberships->activeRoles((string) $user->getKey(), (string) $hospital->getKey()) as $role) {
                foreach ($provider->permissionsFor($role) as $permission) {
                    $permissions[$permission] = true;
                }
            }
        }
        ksort($permissions, SORT_STRING);
        return [
            'user_id' => (string) $user->getKey(),
            'email' => $user->email,
            'display_name' => $user->display_name,
            'permissions' => array_keys($permissions),
        ];
    }

    public function hospitalPayload(string $hospitalId): array
    {
        $hospital = Hospital::query()->find($hospitalId);

        return [
            'hospital_id' => $hospitalId,
            'name' => $hospital?->name,
        ];
    }
}
