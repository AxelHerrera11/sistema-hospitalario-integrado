<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Domain\Identity;

final class CanonicalRole
{
    public const ALL = [
        'superadmin',
        'admin_hospital',
        'medico',
        'enfermeria',
        'recepcion',
    ];

    private const LEGACY_EXPLICIT = [
        'SuperAdmin' => 'superadmin',
        'AdminHospital' => 'admin_hospital',
        'Medico' => 'medico',
        'Enfermeria' => 'enfermeria',
        'Recepcionista' => 'recepcion',
    ];

    public static function isCanonical(string $role): bool
    {
        return in_array($role, self::ALL, true);
    }

    /**
     * Only exact canonical codes survive. Unknown, legacy or differently-cased
     * values are discarded instead of being silently translated at login.
     */
    public static function canonicalize(array $roles): array
    {
        $canonical = [];

        foreach ($roles as $role) {
            if (is_string($role) && self::isCanonical($role)) {
                $canonical[$role] = true;
            }
        }

        $canonical = array_keys($canonical);
        sort($canonical, SORT_STRING);

        return $canonical;
    }

    public static function version(array $roles): string
    {
        return hash('sha256', implode("\n", self::canonicalize($roles)));
    }

    public static function mapLegacyExplicitly(string $role): ?string
    {
        return self::LEGACY_EXPLICIT[$role] ?? (self::isCanonical($role) ? $role : null);
    }
}
