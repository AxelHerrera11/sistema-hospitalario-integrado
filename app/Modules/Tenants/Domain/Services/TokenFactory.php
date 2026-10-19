<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\JwtClaims;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Validation\Validator;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\IdentifiedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
final class TokenFactory
{
    private InMemory $privateKey;
    private InMemory $publicKey;
    private string $issuer;
    private string $audience;
    private int $accessTokenTtl;
    private string $keyId;

    public function __construct(
        string $privateKeyPem,
        string $publicKeyPem,
        string $issuer = 'his-tenants-auth',
        string $audience = 'his-api',
        int $accessTokenTtl = 900,
        string $keyId = 'key-1'
    ) {
        $this->privateKey = InMemory::plainText($privateKeyPem);
        $this->publicKey = InMemory::plainText($publicKeyPem);
        $this->issuer = $issuer;
        $this->audience = $audience;
        $this->accessTokenTtl = $accessTokenTtl;
        $this->keyId = $keyId;}
    public function createAccessToken(
        User $user,
        Tenant $tenant,
        SessionId $sessionId,
        DeviceFingerprint $device
    ): AccessToken {
        $now = time();
        $claims = new JwtClaims(
            $user->id()->value(),
            $user->roleValues(),
            $tenant->id()->value(),
            $sessionId->value(),
            Uuid::uuid4()->toString(),
            $device->deviceId(),
            $now,
            $now + $this->accessTokenTtl,
            $now);
        $token = (new Builder())
            ->issuedBy($this->issuer)
            ->permittedFor($this->audience)
            ->identifiedBy($claims->jti())
            ->issuedAt($claims->issuedAt())
            ->expiresAt($claims->expiresAt())
            ->canOnlyBeUsedAfter($claims->notBefore())
            ->withClaim('sub', $claims->subject())
            ->withClaim('roles', $claims->roles())
            ->withClaim('tenant_id', $claims->tenantId())
            ->withClaim('session_id', $claims->sessionId())
            ->withClaim('device_id', $claims->deviceId())
            ->getToken($this->privateKey, new Sha256(), $this->keyId);

        return new AccessToken($token->toString(), $claims);}
    public function validateAccessToken(string $token): JwtClaims
    {
        $parser = new Parser();
        $parsed = $parser->parse($token);

        $validator = new Validator();
        $constraints = [
            new SignedWith($this->publicKey, new Sha256()),
            new IssuedBy($this->issuer),
            new PermittedFor($this->audience),
            new LooseValidAt(new \DateTimeImmutable()),];
        if (!$validator->validate($parsed, ...$constraints)) {
            throw new \InvalidArgumentException('Token JWT inválido');}
        $claims = $parsed->claims()->all();
        return JwtClaims::fromArray([
            'sub' => $claims['sub'],
            'roles' => $claims['roles'],
            'tenant_id' => $claims['tenant_id'],
            'session_id' => $claims['session_id'],
            'jti' => $claims['jti'],
            'device_id' => $claims['device_id'],
            'iat' => $claims['iat'],
            'exp' => $claims['exp'],
            'nbf' => $claims['nbf'] ?? $claims['iat'],]);}
    public function publicKey(): InMemory { return $this->publicKey; }
    public function keyId(): string { return $this->keyId; }}
