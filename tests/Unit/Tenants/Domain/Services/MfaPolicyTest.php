<?php

namespace Tests\Unit\Tenants\Domain\Services;

use App\Modules\Tenants\Domain\Services\MfaPolicy;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Semana 15 · UT-002: política MFA del micro-HIS (Semana 3) con roles canónicos de RBAC.
 */
#[Group('unitarias')]
class MfaPolicyTest extends TestCase
{
    /** @return array<string, array{0: string, 1: bool}> */
    public static function rolesYExigencia(): array
    {
        return [
            'superadmin exige MFA' => ['superadmin', true],
            'admin_hospital exige MFA' => ['admin_hospital', true],
            'medico no lo exige' => ['medico', false],
            'enfermeria no lo exige' => ['enfermeria', false],
            'recepcion no lo exige' => ['recepcion', false],
            'jefatura_medica no lo exige' => ['jefatura_medica', false],
        ];
    }

    #[DataProvider('rolesYExigencia')]
    public function test_role_decides_mfa_when_no_policy_overrides(string $role, bool $required): void
    {
        $requirement = (new MfaPolicy())->evaluate([new Role($role)], ['mfa_required' => false], false);

        $this->assertSame($required, $requirement->required());
        $this->assertSame('TOTP', $requirement->type());
    }

    #[Group('caja-blanca')]
    public function test_hospital_policy_forces_mfa_for_any_role(): void
    {
        $requirement = (new MfaPolicy())->evaluate([new Role('medico')], ['mfa_required' => true], false);

        $this->assertTrue($requirement->required());
    }

    #[Group('caja-blanca')]
    public function test_user_flag_forces_mfa_for_any_role(): void
    {
        $requirement = (new MfaPolicy())->evaluate([new Role('enfermeria')], [], true);

        $this->assertTrue($requirement->required());
    }

    #[Group('caja-blanca')]
    public function test_one_privileged_role_among_several_is_enough(): void
    {
        $requirement = (new MfaPolicy())->evaluate([new Role('medico'), new Role('admin_hospital')], [], false);

        $this->assertTrue($requirement->required());
    }

    public function test_only_totp_is_offered_even_if_webauthn_is_requested(): void
    {
        // WebAuthn quedó fuera del micro-HIS: no había validación real de atestación.
        $requirement = (new MfaPolicy())->evaluate([new Role('superadmin')], ['mfa_webauthn_enabled' => true], false);

        $this->assertSame('TOTP', $requirement->type());
    }
}
