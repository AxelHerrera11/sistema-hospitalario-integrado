<?php

declare(strict_types=1);

namespace MicroHis\Rbac\Presentation;

use MicroHis\Rbac\Application\AssignRole;
use MicroHis\Rbac\Application\Authorize;
use MicroHis\Rbac\Domain\RbacViolation;

/** Traduce la petición HTTP al caso de uso y el resultado a estado + cuerpo JSON. Sin SQL ni reglas de negocio. */
final class RbacHttpHandler
{
    /** Motivos que corresponden a un fallo de dependencia (HTTP 503), no a una denegación ordinaria. */
    private const DEPENDENCY_FAILURES = ['resolucion_no_disponible', 'registro_no_disponible'];

    public function __construct(
        private readonly AssignRole $assignRole,
        private readonly ?Authorize $authorizeUseCase = null,
        private readonly ?string $endpointPermission = null,
    ) {
    }

    public function assignRole(array $headers, array $body): array
    {
        $assigner = self::str($headers['X-User-ID'] ?? null);
        $tenant   = self::str($headers['X-Tenant-ID'] ?? null);
        $userId   = self::str($body['user_id'] ?? null);
        $roleCode = self::str($body['role_code'] ?? null);
        if ($assigner === null || $tenant === null || $userId === null || $roleCode === null) {
            return ['status' => 422, 'body' => ['code' => 'payload_invalido']];
        }
        try {
            $m = $this->assignRole->execute($assigner, $tenant, $userId, $roleCode);
            return ['status' => 201, 'body' => ['user_id' => $m->userId, 'hospital_id' => $m->hospitalId, 'role_code' => $m->roleCode]];
        } catch (RbacViolation $e) {
            $body = $e->status === 403 ? ['message' => 'Operación no autorizada.', 'trace_id' => $e->traceId] : [];
            return ['status' => $e->status, 'body' => ['code' => $e->getMessage()] + $body];
        } catch (\RuntimeException) {
            return ['status' => 500, 'body' => ['code' => 'RBAC_PERSISTENCE_ERROR']];
        }
    }

    /**
     * Evalúa si un usuario puede ejecutar una acción en su hospital.
     *
     * Identidades —el handler PHP vanilla las simula con cabeceras; en un
     * controlador Laravel provendrían de la sesión autenticada y del middleware
     * de tenancy, NUNCA de cabeceras enviadas por el cliente—:
     *  - solicitante autenticado (cabecera X-User-ID),
     *  - hospital validado (cabecera X-Tenant-ID),
     *  - usuario evaluado (body user_id; por defecto el solicitante; si esta
     *    presente pero es invalido —[], null, "", false— se responde 422, sin
     *    sustituirlo por el solicitante).
     *
     * `$endpointPermission` es obligatorio: si es null el handler falla cerrado
     * (503 "endpoint_no_configurado"). Códigos: 401 sin autenticar; 403 solicitante
     * sin permiso para invocar el endpoint (distinto de la operación evaluada); 422
     * entrada inválida; 200 decisión (allowed true/false); 503 dependencia caída o
     * configuración incompleta. Todo 503 expone allowed=false, code, reason y trace_id.
     */
    public function authorize(array $headers, array $body): array
    {
        if (!array_key_exists('X-User-ID', $headers)) {
            return self::error(401, 'no_autenticado');
        }
        $caller = self::str($headers['X-User-ID']);
        $tenant = self::str($headers['X-Tenant-ID'] ?? null);
        $action = self::str($body['action'] ?? null);
        if ($caller === null || $tenant === null || $action === null) {
            return self::error(422, 'payload_invalido');
        }
        $userId = $caller;
        if (array_key_exists('user_id', $body)) {
            $userId = self::str($body['user_id']);
            if ($userId === null) {
                return self::error(422, 'payload_invalido');
            }
        }
        if ($this->authorizeUseCase === null) {
            return self::dependency('servicio_no_disponible');
        }
        if ($this->endpointPermission === null) {
            return self::dependency('endpoint_no_configurado');
        }

        $check = $this->authorizeUseCase->execute($caller, $tenant, $this->endpointPermission);
        if ($check['allowed'] !== true) {
            return in_array($check['reason'], self::DEPENDENCY_FAILURES, true)
                ? self::dependency((string) $check['reason'], $check['trace_id'])
                : self::error(403, 'operacion_no_autorizada', $check['trace_id']);
        }

        $result = $this->authorizeUseCase->execute($userId, $tenant, $action);
        if ($result['allowed'] === true) {
            return ['status' => 200, 'body' => ['allowed' => true, 'trace_id' => $result['trace_id']]];
        }
        if ($result['reason'] === 'permiso_faltante') {
            return ['status' => 200, 'body' => ['allowed' => false, 'reason' => 'permiso_faltante', 'trace_id' => $result['trace_id']]];
        }

        return self::dependency((string) $result['reason'], $result['trace_id']);
    }

    /** Fallo de dependencia o configuración: 503 con allowed=false, code, reason y trace_id (nunca permite). */
    private static function dependency(string $reason, ?string $traceId = null): array
    {
        $traceId ??= 'trace-' . bin2hex(random_bytes(6));

        return ['status' => 503, 'body' => ['allowed' => false, 'code' => $reason, 'reason' => $reason, 'trace_id' => $traceId]];
    }

    /** Error de entrada/autenticación/permiso de endpoint: 401/403/422 con code y trace_id (SD-RBAC-16). */
    private static function error(int $status, string $code, ?string $traceId = null): array
    {
        return ['status' => $status, 'body' => ['code' => $code, 'trace_id' => $traceId ?? 'trace-' . bin2hex(random_bytes(6))]];
    }

    /** Cadena recortada si la entrada es un string no vacío; si no, null (evita la coerción "Array" y el TypeError). */
    private static function str(mixed $v): ?string
    {
        return is_string($v) && trim($v) !== '' ? trim($v) : null;
    }
}
