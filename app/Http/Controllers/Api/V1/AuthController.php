<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Tenants\Application\Identity\AuthenticateUser;
use App\Modules\Tenants\Application\Identity\RefreshUserSession;
use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Identity\AuthFailure;
use App\Modules\Tenants\Domain\Identity\CanonicalRole;
use App\Modules\Tenants\Infrastructure\Persistence\CentralUser;
use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use App\Modules\Tenants\Infrastructure\Persistence\SessionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Cookie;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthenticateUser $authenticate,
        private readonly RefreshUserSession $refreshSession,
        private readonly SessionRepository $sessions,
        private readonly AuditLogRepositoryInterface $audit,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        /** @var Hospital $hospital */
        $hospital = $request->attributes->get('tenant');

        try {
            return $this->tokenResponse(
                $this->authenticate->login($validated['email'], $validated['password'], $hospital),
            );
        } catch (AuthFailure $failure) {
            return $this->failure($failure);
        }
    }

    public function me(Request $request): JsonResponse
    {
        /** @var CentralUser $user */
        $user = $request->user();

        /** @var Hospital $hospital */
        $hospital = $request->attributes->get('tenant');

        $roles = CanonicalRole::canonicalize($request->attributes->get('auth_roles', []));

        return response()->json([
            'user' => $this->authenticate->userPayload($user),
            'hospital' => $this->authenticate->hospitalPayload((string) $hospital->getKey()),
            'roles' => $roles,
            'roles_version' => CanonicalRole::version($roles),
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        /** @var Hospital $hospital */
        $hospital = $request->attributes->get('tenant');

        try {
            return $this->tokenResponse(
                $this->refreshSession->execute((string) $request->cookie('refresh_token', ''), $hospital),
            );
        } catch (AuthFailure $failure) {
            return $this->failure($failure);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var CentralUser $user */
        $user = $request->user();

        /** @var Hospital $hospital */
        $hospital = $request->attributes->get('tenant');

        $session = $request->attributes->get('auth_session');

        if ($session !== null) {
            DB::connection('central')->transaction(function () use ($hospital, $session, $user): void {
                $hospitalId = (string) $hospital->getKey();
                $revoked = $this->sessions->revoke(
                    (string) $session->session_id,
                    (string) $session->jti,
                    (string) $user->getKey(),
                    $hospitalId,
                    'logout',
                );
                $this->audit->logRaw('auth.logout', [
                    'user_id' => (string) $user->getKey(),
                    'hospital_id' => $hospitalId,
                    'tenant_id' => $hospitalId,
                    'reason' => $revoked === 1 ? 'user_logout' : 'session_already_revoked',
                    'result' => $revoked === 1 ? 'success' : 'already_revoked',
                    'trace_id' => 'trace-'.bin2hex(random_bytes(6)),
                ], $revoked === 1 ? 'info' : 'warning');
            });
        }

        return response()->json(['message' => 'Sesión cerrada correctamente.'])
            ->withCookie($this->refreshCookie('', -1));
    }

    private function tokenResponse(array $payload): JsonResponse
    {
        $refreshToken = (string) $payload['refresh_token'];
        unset($payload['refresh_token']);

        return response()->json($payload)->withCookie($this->refreshCookie(
            $refreshToken,
            (int) ceil(config('tenants.refresh.ttl_seconds', 604800) / 60),
        ));
    }

    private function refreshCookie(string $value, int $minutes): Cookie
    {
        return cookie(
            'refresh_token',
            $value,
            $minutes,
            '/api/v1/auth',
            null,
            (bool) config('tenants.refresh.cookie_secure', false),
            true,
            false,
            'strict',
        );
    }

    private function failure(AuthFailure $failure): JsonResponse
    {
        return response()->json([
            'error' => $failure->errorCode,
            'message' => $failure->getMessage(),
        ], $failure->status);
    }
}
