<?php

declare(strict_types=1);

namespace MicroHis\Rbac\Application;

use MicroHis\Rbac\Domain\AssignerPrivilegeRule;
use MicroHis\Rbac\Domain\Authorizer;
use MicroHis\Rbac\Domain\Membership;
use MicroHis\Rbac\Domain\RbacViolation;
use MicroHis\Rbac\Persistence\PdoRbacStore;

/** Flujo completo: asignación de rol, autorización de la operación y denegación trazable. */
final class AssignRole
{
    public function __construct(private readonly PdoRbacStore $store, private readonly Authorizer $authorizer)
    {
    }

    public function execute(string $assignerId, string $hospitalId, string $userId, string $roleCode): Membership
    {
        if ($userId === '' || !isset(AssignerPrivilegeRule::LEVELS[$roleCode])) {
            throw new RbacViolation('payload_invalido', 422);
        }
        $traceId = $this->authorizer->authorize(['user_id' => $assignerId, 'hospital_id' => $hospitalId, 'permission' => 'rbac.asignar', 'role_to_assign' => $roleCode, 'roles' => $this->store->rolesIn($assignerId, $hospitalId)]);
        if ($traceId !== null) {
            throw new RbacViolation('RBAC_DENIED', 403, $traceId);
        }
        if (in_array($roleCode, $this->store->rolesIn($userId, $hospitalId), true)) {
            throw new RbacViolation('rol_ya_asignado', 409);
        }
        $membership = new Membership($userId, $hospitalId, $roleCode);
        $this->store->assignWithAudit($membership, $assignerId);
        return $membership;
    }
}
