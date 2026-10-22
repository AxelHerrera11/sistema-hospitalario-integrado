<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Ci;

/**
 * Control de contrato sobre `php artisan route:list --json --path=api`:
 *   - toda ruta API pasa por el middleware de tenant;
 *   - fuera de la lista pública, exige JwtAuth;
 *   - fuera de la lista "solo autenticada", exige RequirePermission:<permiso>
 *     con un permiso que exista en config/rbac.php (catalog).
 * No cambia rutas: solo informa. Las listas viven en config/route-guard.json.
 */
final class RouteGuard
{
    private const JWT = 'App\\Http\\Middleware\\JwtAuth';

    private const TENANT = ['tenant', 'App\\Http\\Middleware\\TenantMiddleware'];

    private const RBAC_PREFIX = 'App\\Rbac\\RequirePermission:';

    /**
     * @param  list<string>  $catalog
     * @param  array{public?: list<string>, authenticated_only?: list<string>}  $allow  "MÉTODO uri"
     */
    public function __construct(private readonly array $catalog, private readonly array $allow) {}

    /**
     * @param  list<array{method: string, uri: string, middleware: list<string>}>  $routes
     * @return list<array{route: string, ok: bool, message: string}>
     */
    public function check(array $routes): array
    {
        $out = [];
        foreach ($routes as $route) {
            $method = str_replace('|HEAD', '', (string) $route['method']);
            $key = $method.' '.$route['uri'];
            $out[] = ['route' => $key] + $this->verdict($key, $route['middleware'] ?? []);
        }

        return $out;
    }

    /** @return array{ok: bool, message: string} */
    private function verdict(string $key, array $middleware): array
    {
        if (array_intersect(self::TENANT, $middleware) === []) {
            return ['ok' => false, 'message' => 'Sin middleware de tenant.'];
        }
        if (in_array($key, $this->allow['public'] ?? [], true)) {
            return ['ok' => true, 'message' => 'Pública por lista explícita.'];
        }
        if (! in_array(self::JWT, $middleware, true)) {
            return ['ok' => false, 'message' => 'Ruta protegida sin JwtAuth.'];
        }
        $permissions = [];
        foreach ($middleware as $m) {
            if (str_starts_with($m, self::RBAC_PREFIX)) {
                array_push($permissions, ...explode(',', substr($m, strlen(self::RBAC_PREFIX))));
            }
        }
        if ($permissions === []) {
            return in_array($key, $this->allow['authenticated_only'] ?? [], true)
                ? ['ok' => true, 'message' => 'Solo autenticación, por lista explícita.']
                : ['ok' => false, 'message' => 'Ruta protegida sin middleware RBAC.'];
        }
        $unknown = array_values(array_diff($permissions, $this->catalog));

        return $unknown === []
            ? ['ok' => true, 'message' => 'RBAC: '.implode(', ', $permissions)]
            : ['ok' => false, 'message' => 'Permisos fuera del catálogo: '.implode(', ', $unknown)];
    }
}
