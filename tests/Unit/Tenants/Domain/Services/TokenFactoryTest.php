<?php
declare(strict_types=1);
namespace Tests\Unit\Tenants\Domain\Services;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Services\AccessToken;
use App\Modules\Tenants\Domain\Services\TokenFactory;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use PHPUnit\Framework\TestCase;
final class TokenFactoryTest extends TestCase
{
    private TokenFactory $factory;
    private User $user;
    private Tenant $tenant;
    private DeviceFingerprint $device;
    private SessionId $sessionId;
    protected function setUp(): void
    {
        $privateKey = <<<PEM
-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQD...
-----END PRIVATE KEY-----
PEM;
        $publicKey = <<<PEM
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA...
-----END PUBLIC KEY-----
PEM;
        $this->factory = new TokenFactory(
            $privateKey,
            $publicKey,
            'test-issuer',
            'test-audience',
            900,
            'test-key-1'
        );
        $this->user = User::create(
            new \App\Modules\Tenants\Domain\ValueObjects\Email('test@hospital.com'),
            \App\Modules\Tenants\Domain\ValueObjects\Password::fromHash('$argon2id$v=19$m=65536,t=4,p=3$test'),
            'Dr. Test',
            [Role::MEDICO],
            new TenantId('550e8400-e29b-41d4-a716-446655440000')
        );
        $this->tenant = Tenant::create(
            new TenantId('550e8400-e29b-41d4-a716-446655440000'),
            'Hospital Test'
        );
        $this->device = DeviceFingerprint::createNew(
            'device-123',
            '192.168.1.1',
            'Mozilla/5.0 Test'
        );
        $this->sessionId = SessionId::generate();
    }
    public function testCreateAccessToken(): void
    {
        $accessToken = $this->factory->createAccessToken(
            $this->user,
            $this->tenant,
            $this->sessionId,
            $this->device
        );
        $this->assertInstanceOf(AccessToken::class, $accessToken);
        $this->assertNotEmpty($accessToken->token());
        $this->assertEquals($this->user->id()->value(), $accessToken->claims()->subject());
        $this->assertEquals($this->user->roleValues(), $accessToken->claims()->roles());
        $this->assertEquals($this->tenant->id()->value(), $accessToken->claims()->tenantId());
        $this->assertEquals($this->sessionId->value(), $accessToken->claims()->sessionId());
        $this->assertEquals($this->device->deviceId(), $accessToken->claims()->deviceId());
    }
    public function testValidateValidToken(): void
    {
        $accessToken = $this->factory->createAccessToken(
            $this->user,
            $this->tenant,
            $this->sessionId,
            $this->device
        );
        $claims = $this->factory->validateAccessToken($accessToken->token());

        $this->assertEquals($this->user->id()->value(), $claims->subject());
        $this->assertEquals($this->user->roleValues(), $claims->roles());
    }
    public function testValidateInvalidTokenThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Token JWT inválido');
        $this->factory->validateAccessToken('invalid.token.string');
    }
}
