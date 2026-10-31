<?php

declare(strict_types=1);

namespace Tests\Feature\TrasladosAltas;

use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;

/**
 * Identidad de prueba: sustituye tenant y jwt.auth, las membresías de CENTRAL y el registro de denegaciones.
 * Lo demás es real: RequirePermission, Authorize y config/rbac.php.
 */
final class TrasladosTestIdentity implements RoleResolver, DenialRecorder
{
    public static ?GenericUser $user = null;
    public static string $hospital = 'hospital-a';
    /** @var list<string> */
    public static array $roles = [];

    public function handle(Request $request, Closure $next)
    {
        $request->setUserResolver(static fn () => self::$user);
        $request->attributes->set('tenant', new Hospital(['hospital_id' => self::$hospital]));

        return $next($request);
    }

    public function rolesIn(string $userId, string $hospitalId): array
    {
        return self::$user !== null && $userId === self::$user->getAuthIdentifier() && $hospitalId === self::$hospital ? self::$roles : [];
    }

    public function record(array $denial): void
    {
    }

    public static function como(?string $rol, string $usuario = '00000000-0000-4000-8000-00000000c0a1'): void
    {
        self::$user = $rol === null ? null : new GenericUser(['id' => $usuario]);
        self::$roles = $rol === null ? [] : [$rol];
    }
}
