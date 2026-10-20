<?php

declare(strict_types=1);

namespace Tests\Unit\Tenants;

use App\Modules\Tenants\Domain\Identity\CanonicalRole;
use PHPUnit\Framework\TestCase;

final class CanonicalRoleTest extends TestCase
{
    public function test_laboratory_roles_are_explicit_and_not_legacy_aliases(): void
    {
        $this->assertSame(['bioquimico', 'tecnico_laboratorio'], CanonicalRole::canonicalize([
            'tecnico_laboratorio', 'bioquimico', 'TecnicoLab', 'Bioquimico', 'tecnico_laboratorio',
        ]));
        $this->assertTrue(CanonicalRole::isCanonical('tecnico_laboratorio'));
        $this->assertTrue(CanonicalRole::isCanonical('bioquimico'));
        $this->assertNull(CanonicalRole::mapLegacyExplicitly('TecnicoLab'));
        $this->assertNull(CanonicalRole::mapLegacyExplicitly('Bioquimico'));
        $this->assertNotSame(CanonicalRole::version(['bioquimico']), CanonicalRole::version(['tecnico_laboratorio']));
    }

    public function test_only_exact_canonical_codes_survive_and_are_sorted(): void
    {
        $this->assertSame(
            ['admin_hospital', 'recepcion', 'superadmin'],
            CanonicalRole::canonicalize([
                'recepcion',
                'MEDICO',
                'Medico',
                'Recepcionista',
                'pending',
                'superadmin',
                'auditor',
                'admin_hospital',
            ]),
        );
    }

    public function test_version_is_stable_and_order_independent_but_role_sensitive(): void
    {
        $first = CanonicalRole::version(['medico', 'recepcion']);
        $second = CanonicalRole::version(['recepcion', 'medico']);

        $this->assertSame($first, $second);
        $this->assertNotSame($first, CanonicalRole::version(['medico']));
        $this->assertSame(CanonicalRole::version(['medico']), CanonicalRole::version(['medico', 'unknown']));
    }

    public function test_legacy_codes_require_an_explicit_mapping(): void
    {
        $this->assertSame('recepcion', CanonicalRole::mapLegacyExplicitly('Recepcionista'));
        $this->assertNull(CanonicalRole::mapLegacyExplicitly('RECEPCIONISTA'));
        $this->assertSame('medico', CanonicalRole::mapLegacyExplicitly('medico'));
    }
}
