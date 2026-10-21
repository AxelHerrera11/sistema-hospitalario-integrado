<?php

declare(strict_types=1);

namespace Tests\Feature\MedicationCatalog;

use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\RoleResolver;

/**
 * Identidad de prueba: sustituye tenant y jwt.auth, la consulta de membresías a la base CENTRAL y el registro de
 * denegaciones y la auditoría de escrituras (ambas escriben en CENTRAL), que guarda en memoria para poder comprobarlas.
 * Lo demás es real: RequirePermission, Authorize y config/rbac.php.
 */
final class CatalogTestIdentity implements RoleResolver, DenialRecorder, AuditLogRepositoryInterface
{
    public static ?GenericUser $user = null;
    public static ?string $hospital = null;
    /** Hospital de la membresía; por defecto, el del contexto. */
    public static ?string $membershipHospital = null;
    /** @var list<string> */
    public static array $roles = [];
    /** @var list<array<string, mixed>> */
    public static array $denials = [];
    /** @var list<array{event: string, payload: array<string, mixed>}> */
    public static array $audited = [];
    /** Simula la caída de la auditoría CENTRAL. */
    public static bool $auditDown = false;

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

    public function logRaw(string $eventName, array $payload, ?string $severity = null): void
    {
        if (self::$auditDown) {
            throw new \RuntimeException('auditoría no disponible');
        }
        self::$audited[] = ['event' => $eventName, 'payload' => $payload];
    }

    /** Sustituye la identidad y los registros de CENTRAL en la aplicación de prueba. */
    public static function install(\Illuminate\Foundation\Application $app): void
    {
        $app->make(\Illuminate\Contracts\Http\Kernel::class);
        $app['router']->aliasMiddleware('tenant', self::class);
        $app['router']->aliasMiddleware('jwt.auth', self::class);
        $app->instance(RoleResolver::class, new self());
        $app->instance(DenialRecorder::class, new self());
        $app->instance(AuditLogRepositoryInterface::class, new self());
        self::$denials = [];
        self::$audited = [];
        self::$auditDown = false;
    }

    public static function as(?string $role, string $hospital = 'hospital-a', ?string $membershipHospital = null): void
    {
        self::$membershipHospital = $membershipHospital;
        self::$user = $role === null ? null : new GenericUser(['id' => "user-{$role}"]);
        self::$hospital = $hospital;
        self::$roles = $role === null ? [] : [$role];
    }
}
