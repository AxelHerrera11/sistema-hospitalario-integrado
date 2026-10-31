<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MicroHis\DashboardBase\Application\FuenteNoDisponible;
use MicroHis\DashboardBase\Application\ResumenTablero;
use MicroHis\Rbac\Domain\PermissionProvider;
use MicroHis\Rbac\Domain\RoleResolver;

/**
 * Resumen de la pantalla de inicio (ASII-26). Los permisos se calculan en el servidor desde los roles de la
 * sesión en el hospital del tenant, con la misma matriz que RequirePermission (config/rbac.php).
 */
final class DashboardController extends Controller
{
    public function __construct(
        private readonly ResumenTablero $tablero,
        private readonly RoleResolver $roles,
        private readonly PermissionProvider $permisos,
    ) {
    }

    public function resumen(Request $request): JsonResponse
    {
        $usuario = $request->user('api');
        if ($usuario === null) {
            return $this->error(401, 'unauthenticated', 'Inicie sesion para ver el tablero.');
        }
        $fecha = $request->query('fecha', now()->toDateString());
        if (!is_string($fecha) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) !== 1 || !checkdate((int) substr($fecha, 5, 2), (int) substr($fecha, 8, 2), (int) substr($fecha, 0, 4))) {
            return $this->error(422, 'validation_failed', 'La fecha debe tener el formato AAAA-MM-DD.');
        }
        $hospital = (string) $request->attributes->get('tenant')?->getKey();
        $permisos = [];
        foreach ($this->roles->rolesIn((string) $usuario->getAuthIdentifier(), $hospital) as $rol) {
            $permisos = [...$permisos, ...$this->permisos->permissionsFor($rol)];
        }

        try {
            $resumen = $this->tablero->ejecutar($fecha, array_values(array_unique($permisos)));
        } catch (FuenteNoDisponible) {
            return $this->error(503, 'dashboard_source_unavailable', 'No se pudo cargar el tablero; intente de nuevo.');
        }

        return response()->json(['data' => $resumen])->header('Cache-Control', 'no-store, private');
    }

    private function error(int $status, string $code, string $mensaje): JsonResponse
    {
        return response()->json(['message' => $mensaje, 'code' => $code, 'trace_id' => 'trace-'.bin2hex(random_bytes(6))], $status)
            ->header('Cache-Control', 'no-store, private');
    }
}
