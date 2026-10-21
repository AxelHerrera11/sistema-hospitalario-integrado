<?php

declare(strict_types=1);

namespace Tests\Feature\MedicationCatalog;

use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;

/**
 * Identidad de prueba: sustituye tenant y jwt.auth, la consulta de membresías a la base CENTRAL y el registro de
 * denegaciones (AuditDenials escribe en la auditoría CENTRAL), que guarda en memoria para poder comprobarlo.
 * Lo demás es real: RequirePermission, Authorize y config/rbac.php.
 */
final class CatalogTestIdentity implements RoleResolver, DenialRecorder
{
    public static ?GenericUser $user = null;
    public static ?string $hospital = null;
    /** Hospital de la membresía; por defecto, el del contexto. */
    public static ?string $membershipHospital = null;
    /** @var list<string> */
    public static array $roles = [];
    /** @var list<array<string, mixed>> */
    public static array $denials = [];

    public function handle(Request $request, Closure $next)
    {
        $request->setUserResolver(static fn () => self::$user);
        $request->attributes->set('tenant', new class {
            public function getKey(): ?string
            {
                return CatalogTestIdentity::$hospital;
            }
        });

        return $next($request);
    }

    public function rolesIn(string $userId, string $hospitalId): array
    {
        return self::$user !== null && $userId === self::$user->getAuthIdentifier() && $hospitalId === (self::$membershipHospital ?? self::$hospital) ? self::$roles : [];
    }

    public function record(array $denial): void
    {
        self::$denials[] = $denial;
    }

    public static function as(?string $role, string $hospital = 'hospital-a', ?string $membershipHospital = null): void
    {
        self::$membershipHospital = $membershipHospital;
        self::$user = $role === null ? null : new GenericUser(['id' => "user-{$role}"]);
        self::$hospital = $hospital;
        self::$roles = $role === null ? [] : [$role];
    }
}
