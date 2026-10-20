<?php
declare(strict_types=1);
namespace Tests\Unit\Tenants\Domain\Services;
use App\Modules\Tenants\Domain\Services\MfaPolicy;
use App\Modules\Tenants\Domain\Services\MfaRequirement;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use PHPUnit\Framework\TestCase;
final class MfaPolicyTest extends TestCase
{
    private MfaPolicy $policy;
    protected function setUp(): void
    {
        $this->policy = new MfaPolicy();
    }
    public function testTenantAdminRequiresMfa(): void
    {
        $requirement = $this->policy->evaluate(
            [new Role(Role::TENANT_ADMIN)],
            ['mfa_required' => false],
            false
        );
        $this->assertTrue($requirement->required());
        $this->assertEquals('TOTP', $requirement->type());
    }
    public function testAuditorRequiresMfa(): void
    {
        $requirement = $this->policy->evaluate(
            [new Role(Role::AUDITOR)],
            ['mfa_required' => false],
            false
        );
        $this->assertTrue($requirement->required());
    }
    public function testPlatformAdminRequiresMfa(): void
    {
        $requirement = $this->policy->evaluate(
            [new Role(Role::PLATFORM_ADMIN)],
            ['mfa_required' => false],
            false
        );
        $this->assertTrue($requirement->required());
    }
    public function testMedicoDoesNotRequireMfaByDefault(): void
    {
        $requirement = $this->policy->evaluate(
            [new Role(Role::MEDICO)],
            ['mfa_required' => false],
            false
        );
        $this->assertFalse($requirement->required());
    }
    public function testEnfermeroDoesNotRequireMfaByDefault(): void
    {
        $requirement = $this->policy->evaluate(
            [new Role(Role::ENFERMERO)],
            ['mfa_required' => false],
            false
        );
        $this->assertFalse($requirement->required());
    }
    public function testTenantPolicyMfaRequiredOverrides(): void
    {
        $requirement = $this->policy->evaluate(
            [new Role(Role::MEDICO)],
            ['mfa_required' => true],
            false
        );
        $this->assertTrue($requirement->required());
    }
    public function testUserMfaRequiredOverrides(): void
    {
        $requirement = $this->policy->evaluate(
            [new Role(Role::MEDICO)],
            ['mfa_required' => false],
            true
        );
        $this->assertTrue($requirement->required());
    }
    public function testWebAuthnWhenEnabled(): void
    {
        $requirement = $this->policy->evaluate(
            [new Role(Role::TENANT_ADMIN)],
            ['mfa_required' => false, 'mfa_webauthn_enabled' => true],
            false
        );
        $this->assertTrue($requirement->required());
        $this->assertEquals('WEBAUTHN', $requirement->type());
    }
}
