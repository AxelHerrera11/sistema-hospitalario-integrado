<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;

/** Sustituye tenant, jwt.auth y las membresías de CENTRAL; RequirePermission y config/rbac.php son los reales. */
final class DashboardTestIdentity implements RoleResolver, DenialRecorder
{
    public static ?GenericUser $user = null;
    /** @var list<string> */
    public static array $roles = [];

    public function handle(Request $request, Closure $next)
    {
        $request->setUserResolver(static fn () => self::$user);
        $request->attributes->set('tenant', new class { public function getKey(): string { return 'hospital-a'; } });

        return $next($request);
    }

    public function rolesIn(string $userId, string $hospitalId): array
    {
        return self::$user !== null && $hospitalId === 'hospital-a' ? self::$roles : [];
    }

    public function record(array $denial): void {}

    public static function como(?string $rol): void
    {
        self::$user = $rol === null ? null : new GenericUser(['id' => '00000000-0000-4000-8000-00000000c26d']);
        self::$roles = $rol === null ? [] : [$rol];
    }
}
