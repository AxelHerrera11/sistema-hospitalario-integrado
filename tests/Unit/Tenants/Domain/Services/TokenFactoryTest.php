<?php

namespace Tests\Unit\Tenants\Domain\Services;

use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Services\Argon2idHasher;
use App\Modules\Tenants\Domain\Services\TokenFactory;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\Password;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Infrastructure\Security\JwtTokenService;
use DateTimeImmutable;
use InvalidArgumentException;
use Lcobucci\Clock\FrozenClock;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Semana 15 · UT-004 (RF-06): el puerto TokenFactory con su implementación JwtTokenService
 * (HS256, lcobucci/jwt 4.0).
 */
#[Group('unitarias')]
class TokenFactoryTest extends TestCase
{
    private const SECRET = 'secreto-de-pruebas-semana-15-32-bytes!';

    public function test_creates_token_with_tenant_session_and_roles(): void
    {
        [$user, $tenant] = $this->userAndTenant();
        $tokens = $this->factory();
        $sessionId = SessionId::generate();

        $token = $tokens->createAccessToken($user, $tenant, $sessionId, $this->device());

        $this->assertCount(3, explode('.', $token->token()));
        $this->assertSame($tenant->id()->value(), $token->claims()->tenantId());
        $this->assertSame($sessionId->value(), $token->claims()->sessionId());
        $this->assertSame(['medico'], $token->claims()->roles());
        $this->assertSame(900, $token->expiresAt() - $token->claims()->issuedAt());
    }

    public function test_validates_its_own_token(): void
    {
        [$user, $tenant] = $this->userAndTenant();
        $tokens = $this->factory();
        $token = $tokens->createAccessToken($user, $tenant, SessionId::generate(), $this->device());

        $claims = $tokens->validateAccessToken($token->token());

        $this->assertSame($user->id()->value(), $claims->subject());
        $this->assertSame($token->jti(), $claims->jti());
    }

    #[Group('caja-blanca')]
    public function test_rejects_tampered_foreign_expired_and_malformed_tokens(): void
    {
        [$user, $tenant] = $this->userAndTenant();
        $valid = $this->factory()->createAccessToken($user, $tenant, SessionId::generate(), $this->device())->token();
        [$header, $payload] = explode('.', $valid);
        $expired = (new JwtTokenService(self::SECRET, clock: new FrozenClock(new DateTimeImmutable('-2 hours'))))
            ->createAccessToken($user, $tenant, SessionId::generate(), $this->device())->token();

        $cases = [
            'firma de otro secreto' => [new JwtTokenService(str_repeat('z', 40)), $valid],
            'sin firma (alg none)' => [$this->factory(), "{$header}.{$payload}."],
            'otra audiencia' => [new JwtTokenService(self::SECRET, audience: 'otra-api'), $valid],
            'otro emisor' => [new JwtTokenService(self::SECRET, issuer: 'otro-emisor'), $valid],
            'vencido' => [$this->factory(), $expired],
            'mal formado' => [$this->factory(), 'abc'],
        ];
        foreach ($cases as $case => [$validator, $candidate]) {
            try {
                $validator->validateAccessToken($candidate);
                $this->fail("Se aceptó un token: {$case}");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    private function factory(): TokenFactory
    {
        return new JwtTokenService(self::SECRET);
    }

    /** @return array{0: User, 1: Tenant} */
    private function userAndTenant(): array
    {
        $tenant = Tenant::create(TenantId::generate(), 'Hospital A');
        $user = User::create(new Email('medico@a.test'), Password::fromRaw('Clave-Segura-2026!', new Argon2idHasher(1024, 1, 1)), 'Dra. A', [new Role('medico')], $tenant->id());

        return [$user, $tenant];
    }

    private function device(): DeviceFingerprint
    {
        return DeviceFingerprint::createNew('equipo-01', '10.0.0.1', 'PHPUnit');
    }
}
