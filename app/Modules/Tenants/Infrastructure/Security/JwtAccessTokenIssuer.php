<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Infrastructure\Security;

use App\Modules\Tenants\Domain\Identity\AuthFailure;
use App\Modules\Tenants\Domain\Identity\CanonicalRole;
use App\Modules\Tenants\Infrastructure\Persistence\CentralUser;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

final class JwtAccessTokenIssuer
{
    public function __construct(
        private readonly int $ttlMinutes,
        private readonly string $issuer,
        private readonly string $audience,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (int) config('jwt.ttl', 60),
            (string) config('tenants.jwt.issuer', 'his-tenants-auth'),
            (string) config('tenants.jwt.audience', 'his-api'),
        );
    }

    public function issue(
        CentralUser $user,
        string $hospitalId,
        array $roles,
        string $sessionId,
        string $jti,
    ): array {
        $issuedAt = now()->timestamp;
        $expiresIn = $this->ttlMinutes * 60;
        $roles = CanonicalRole::canonicalize($roles);

        $claims = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => (string) $user->getKey(),
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $issuedAt + $expiresIn,
            'jti' => $jti,
            'tenant_id' => $hospitalId,
            'roles' => $roles,
            'roles_version' => CanonicalRole::version($roles),
            'session_id' => $sessionId,
        ];

        $token = (string) JWTAuth::customClaims($claims)->fromUser($user);

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $expiresIn,
            'claims' => $claims,
        ];
    }

    public function decode(string $token): array
    {
        try {
            $claims = JWTAuth::setToken($token)->getPayload()->toArray();
        } catch (TokenExpiredException) {
            throw AuthFailure::expiredToken();
        } catch (TokenInvalidException) {
            throw AuthFailure::invalidToken();
        } catch (\Throwable) {
            throw AuthFailure::invalidToken();
        }

        if (($claims['iss'] ?? null) !== $this->issuer) {
            throw AuthFailure::issuerMismatch();
        }

        $audiences = $claims['aud'] ?? null;
        $audiences = is_array($audiences) ? $audiences : [$audiences];

        if (! in_array($this->audience, $audiences, true)) {
            throw AuthFailure::audienceMismatch();
        }

        foreach (['sub', 'tenant_id', 'session_id', 'jti', 'roles_version'] as $claim) {
            if (! isset($claims[$claim]) || ! is_string($claims[$claim]) || $claims[$claim] === '') {
                throw AuthFailure::invalidToken();
            }
        }

        return $claims;
    }
}
