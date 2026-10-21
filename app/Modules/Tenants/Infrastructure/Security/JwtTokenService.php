<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Security;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Services\AccessToken;
use App\Modules\Tenants\Domain\Services\TokenFactory;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\JwtClaims;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use DateTimeImmutable;
use InvalidArgumentException;
use Lcobucci\Clock\Clock;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Ramsey\Uuid\Uuid;
/**
 * Access tokens HS256 con lcobucci/jwt 4.0 (la versión que fija composer.lock vía tymon/jwt-auth).
 * El micro-HIS no comparte firma con el guard de Laravel: usa su propio secreto e issuer.
 */
final class JwtTokenService implements TokenFactory
{
    private Configuration $config;
    private string $issuer;
    private string $audience;
    private int $accessTokenTtl;
    private Clock $clock;
    public function __construct(string $secret, string $issuer = 'his-tenants-auth', string $audience = 'his-api', int $accessTokenTtl = 900, ?Clock $clock = null)
    {
        if (strlen($secret) < 32) {
            throw new InvalidArgumentException('El secreto JWT debe tener al menos 32 bytes');}
        $this->config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($secret));
        $this->issuer = $issuer;
        $this->audience = $audience;
        $this->accessTokenTtl = $accessTokenTtl;
        $this->clock = $clock ?? SystemClock::fromUTC();}
    public function createAccessToken(User $user, Tenant $tenant, SessionId $sessionId, DeviceFingerprint $device): AccessToken
    {
        $now = new DateTimeImmutable('@' . $this->clock->now()->getTimestamp());
        $claims = new JwtClaims(
            $user->id()->value(),
            $user->roleValues(),
            $tenant->id()->value(),
            $sessionId->value(),
            Uuid::uuid4()->toString(),
            $device->deviceId(),
            $now->getTimestamp(),
            $now->getTimestamp() + $this->accessTokenTtl,
            $now->getTimestamp());
        $token = $this->config->builder()
            ->issuedBy($this->issuer)
            ->permittedFor($this->audience)
            ->identifiedBy($claims->jti())
            ->relatedTo($claims->subject())
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify("+{$this->accessTokenTtl} seconds"))
            ->withClaim('roles', $claims->roles())
            ->withClaim('tenant_id', $claims->tenantId())
            ->withClaim('session_id', $claims->sessionId())
            ->withClaim('device_id', $claims->deviceId())
            ->getToken($this->config->signer(), $this->config->signingKey());
        return new AccessToken($token->toString(), $claims);}
    public function validateAccessToken(string $token): JwtClaims
    {
        try {
            $parsed = $this->config->parser()->parse($token);
        } catch (\Throwable) {
            throw new InvalidArgumentException('Token JWT mal formado');}
        $valid = $parsed instanceof Plain && $this->config->validator()->validate(
            $parsed,
            new SignedWith($this->config->signer(), $this->config->verificationKey()),
            new IssuedBy($this->issuer),
            new PermittedFor($this->audience),
            new ValidAt($this->clock));
        if (!$valid) {
            throw new InvalidArgumentException('Token JWT inválido o vencido');}
        $claims = $parsed->claims();
        return new JwtClaims(
            (string) $claims->get('sub'),
            (array) $claims->get('roles', []),
            (string) $claims->get('tenant_id'),
            (string) $claims->get('session_id'),
            (string) $claims->get('jti'),
            (string) $claims->get('device_id'),
            $claims->get('iat')->getTimestamp(),
            $claims->get('exp')->getTimestamp(),
            $claims->get('nbf')->getTimestamp());}
    public function accessTokenTtl(): int { return $this->accessTokenTtl; }}
