<?php

declare(strict_types=1);

namespace Tests\Feature\MedicationCatalog;

use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use MicroHis\Rbac\Domain\RoleResolver;

/**
 * Identidad de prueba: sustituye tenant y jwt.auth (cuyos modelos User/Tenant no existen en develop) y la consulta de
 * membresías a la base CENTRAL. Lo demás es real: RequirePermission, Authorize y config/rbac.php.
 */
final class CatalogTestIdentity implements RoleResolver
{
    public static ?GenericUser $user = null;
    public static ?string $hospital = null;
    /** @var list<string> */
    public static array $roles = [];

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
        return self::$user !== null && $userId === self::$user->getAuthIdentifier() && $hospitalId === self::$hospital ? self::$roles : [];
    }

    public static function as(?string $role, string $hospital = 'hospital-a'): void
    {
        self::$user = $role === null ? null : new GenericUser(['id' => "user-{$role}"]);
        self::$hospital = $hospital;
        self::$roles = $role === null ? [] : [$role];
    }
}
