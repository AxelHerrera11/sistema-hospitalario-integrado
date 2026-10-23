<?php

declare(strict_types=1);

namespace Tests\Feature\ExpedienteMedico;

use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;

/**
 * Doble de prueba: sustituye tenant y jwt.auth, las membresías de CENTRAL y el registro de denegaciones.
 * Lo demás es real: RequirePermission, Authorize, config/rbac.php, RecordAuditEvent y el micro-HIS.
 */
final class ExpedienteTestIdentity implements RoleResolver, DenialRecorder
{
    public static ?GenericUser $user = null;
    public static string $hospital = 'hospital-demo';
    /** @var list<string> */
    public static array $roles = [];

    public function handle(Request $request, Closure $next)
    {
        $request->setUserResolver(static fn () => self::$user);
        $request->attributes->set('tenant', new class {
            public function getKey(): string
            {
                return ExpedienteTestIdentity::$hospital;
            }
        });

        return $next($request);
    }

    public function rolesIn(string $userId, string $hospitalId): array
    {
        return self::$user !== null && $userId === self::$user->getAuthIdentifier() && $hospitalId === self::$hospital ? self::$roles : [];
    }

    public function record(array $denial): void
    {
    }

    public static function as(?string $role, string $centralUserId): void
    {
        self::$user = $role === null ? null : new GenericUser(['id' => $centralUserId]);
        self::$roles = $role === null ? [] : [$role];
    }
}
