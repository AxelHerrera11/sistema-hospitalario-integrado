<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Tenants\Domain\Identity\AuthFailure;
use App\Modules\Tenants\Domain\Identity\CanonicalRole;
use App\Modules\Tenants\Infrastructure\Persistence\CentralUser;
use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use App\Modules\Tenants\Infrastructure\Persistence\MembershipRepository;
use App\Modules\Tenants\Infrastructure\Persistence\SessionRepository;
use App\Modules\Tenants\Infrastructure\Security\JwtAccessTokenIssuer;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class JwtAuth
{
    public function __construct(
        private readonly MembershipRepository $memberships,
        private readonly SessionRepository $sessions,
        private readonly JwtAccessTokenIssuer $jwt,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $claims = $this->authenticate($request);
        } catch (AuthFailure $failure) {
            return $this->failure($failure);
        }

        $request->attributes->set('auth_claims', $claims);

        return $next($request);
    }

    private function authenticate(Request $request): array
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            throw AuthFailure::missingToken();
        }

        $claims = $this->jwt->decode($token);
        $userId = (string) $claims['sub'];

        $user = CentralUser::query()->find($userId);

        if ($user === null) {
            throw AuthFailure::userNotFound();
        }

        if ($user->status !== 'active') {
            throw AuthFailure::inactiveUser();
        }

        $hospital = $request->attributes->get('tenant');

        if (! $hospital instanceof Hospital) {
            throw AuthFailure::tenantRequired();
        }

        $hospitalId = (string) $hospital->getKey();

        if ((string) $claims['tenant_id'] !== $hospitalId) {
            throw AuthFailure::tenantMismatch();
        }

        $roles = $this->memberships->activeRoles($userId, $hospitalId);

        if ($roles === []) {
            throw AuthFailure::noActiveRoles();
        }

        if (! hash_equals(CanonicalRole::version($roles), (string) $claims['roles_version'])) {
            throw AuthFailure::rolesChanged();
        }

        $session = $this->sessions->findActive(
            (string) $claims['session_id'],
            (string) $claims['jti'],
            $userId,
            $hospitalId,
        );

        if ($session === null) {
            throw AuthFailure::sessionRevoked();
        }

        $request->attributes->set('auth_session', $session);
        $request->attributes->set('auth_roles', $roles);
        $request->setUserResolver(fn (): CentralUser => $user);
        auth('api')->setUser($user);

        return $claims;
    }

    private function failure(AuthFailure $failure): JsonResponse
    {
        return response()->json([
            'error' => $failure->errorCode,
            'message' => $failure->getMessage(),
        ], $failure->status);
    }
}
