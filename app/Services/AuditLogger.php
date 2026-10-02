<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class AuditLogger
{
    public function log(
        string $action,
        Model $entity,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null,
    ): AuditLog {
        $tenantId = $entity->getAttribute('tenant_id');
        $entityId = $entity->getKey();

        if (! $tenantId || ! $entity->exists || ! $entityId) {
            throw new InvalidArgumentException(
                'La entidad debe estar guardada y pertenecer a un hospital.'
            );
        }

        $currentTenant = app()->bound('currentTenant')
            ? app('currentTenant')
            : null;

        if ($currentTenant && (string) $currentTenant->getKey() !== (string) $tenantId) {
            throw new InvalidArgumentException(
                'La entidad no pertenece al hospital actual.'
            );
        }

        $user ??= Auth::guard('api')->user();

        if ($user && (string) $user->tenant_id !== (string) $tenantId) {
            throw new InvalidArgumentException(
                'El usuario no pertenece al hospital de la entidad.'
            );
        }

        if (
            trim($action) === '' ||
            mb_strlen($action) > 100 ||
            mb_strlen(class_basename($entity)) > 100
        ) {
            throw new InvalidArgumentException(
                'La acción y el nombre de la entidad deben tener entre 1 y 100 caracteres.'
            );
        }

        $request = app()->bound('request') ? app('request') : null;

        return AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $user?->getKey(),
            'action' => $action,
            'entity' => class_basename($entity),
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request instanceof Request ? $request->ip() : null,
            'user_agent' => $request instanceof Request
                ? mb_substr($request->userAgent() ?? '', 0, 255)
                : null,
        ]);
    }
}
