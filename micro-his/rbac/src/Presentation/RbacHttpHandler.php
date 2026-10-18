<?php

declare(strict_types=1);

namespace MicroHis\Rbac\Presentation;

use MicroHis\Rbac\Application\AssignRole;
use MicroHis\Rbac\Domain\RbacViolation;

/** Traduce la petición HTTP al caso de uso y el resultado a estado + cuerpo JSON. Sin SQL ni reglas de negocio. */
final class RbacHttpHandler
{
    public function __construct(private readonly AssignRole $assignRole)
    {
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

    /** Cadena recortada si la entrada es un string no vacío; si no, null (evita la coerción "Array" y el TypeError). */
    private static function str(mixed $v): ?string
    {
        return is_string($v) && trim($v) !== '' ? trim($v) : null;
    }
}
