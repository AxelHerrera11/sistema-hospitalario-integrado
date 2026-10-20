<?php
declare(strict_types=1);
namespace Tests\Unit\Tenants\Domain\Services;
use App\Modules\Tenants\Domain\Services\Argon2idHasher;
use App\Modules\Tenants\Domain\Services\HaveIBeenPwnedClient;
use App\Modules\Tenants\Domain\Services\HibpResult;
use App\Modules\Tenants\Domain\Services\PasswordPolicy;
use App\Modules\Tenants\Domain\Services\ValidationResult;
use PHPUnit\Framework\TestCase;
final class PasswordPolicyTest extends TestCase
{
    private PasswordPolicy $policy;
    private Argon2idHasher $hasher;
    private HaveIBeenPwnedClient $hibpClient;
    protected function setUp(): void
    {
        $this->hasher = new Argon2idHasher();
        $this->hibpClient = $this->createMock(HaveIBeenPwnedClient::class);
        $this->policy = new PasswordPolicy(
            $this->hasher,
            $this->hibpClient,
            12,
            true,
            true,
            5
        );
    }
    public function testValidPasswordPasses(): void
    {
        $this->hibpClient->method('check')->willReturn(new HibpResult(false, 0));
        $result = $this->policy->validate('SecurePass123!');
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->errors());
    }
    public function testTooShortPasswordFails(): void
    {
        $result = $this->policy->validate('Short1!');
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('12 caracteres', $result->firstError());
    }
    public function testMissingUppercaseFails(): void
    {
        $result = $this->policy->validate('lowercase123!');
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('mayúscula', $result->firstError());
    }
    public function testMissingLowercaseFails(): void
    {
        $result = $this->policy->validate('UPPERCASE123!');
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('minúscula', $result->firstError());
    }
    public function testMissingNumberFails(): void
    {
        $result = $this->policy->validate('NoNumbers!');
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('número', $result->firstError());
    }
    public function testMissingSymbolFails(): void
    {
        $result = $this->policy->validate('NoSymbols123');
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('símbolo', $result->firstError());
    }
    public function testCompromisedPasswordFails(): void
    {
        $this->hibpClient->method('check')->willReturn(new HibpResult(true, 100));
        $result = $this->policy->validate('CompromisedPass123!');
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('comprometida', $result->firstError());
    }
    public function testPasswordInHistoryFails(): void
    {
        $oldHash = $this->hasher->hash('OldPassword123!');
        $result = $this->policy->validate('OldPassword123!', [$oldHash]);
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('últimas 5', $result->firstError());
    }
    public function testHashProducesValidArgon2id(): void
    {
        $password = $this->policy->hash('TestPassword123!');
        $hash = $password->hash();
        $this->assertStringStartsWith('$argon2id$', $hash);
        $this->assertTrue($this->hasher->verify('TestPassword123!', $hash));
    }
}
