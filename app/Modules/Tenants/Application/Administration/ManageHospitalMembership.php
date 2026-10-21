<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Application\Administration;

use App\Modules\Tenants\Domain\Administration\AdminFailure;
use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Identity\CanonicalRole;
use App\Modules\Tenants\Infrastructure\Persistence\CentralUser;
use App\Modules\Tenants\Infrastructure\Persistence\MembershipRepository;
use App\Modules\Tenants\Infrastructure\Persistence\SessionRepository;
use Illuminate\Support\Facades\DB;

final class ManageHospitalMembership
{
    public function __construct(
        private readonly MembershipRepository $memberships,
        private readonly SessionRepository $sessions,
        private readonly AuditLogRepositoryInterface $audit,
    ) {}

    public function assign(
        string $actorId,
        array $actorRoles,
        string $hospitalId,
        string $targetUserId,
        string $roleCode,
        ?string $traceId = null,
    ): array {
        $roleCode = $this->validateChange($actorId, $actorRoles, $targetUserId, $roleCode);
        $this->activeTarget($targetUserId);

        if ($this->memberships->hasActiveRole($targetUserId, $hospitalId, $roleCode)) {
            throw AdminFailure::alreadyAssigned();
        }

        $traceId ??= $this->newTraceId();

        $membershipId = DB::connection('central')->transaction(function () use (
            $actorId, $hospitalId, $targetUserId, $roleCode, $traceId,
        ): string {
            $membershipId = $this->memberships->assignRole($targetUserId, $hospitalId, $roleCode);
            $this->sessions->revokeAllForUserInHospital($targetUserId, $hospitalId, 'roles_changed');
            $this->audit->logRaw('user.role_assigned', [
                'user_id' => $actorId,
                'target_user_id' => $targetUserId,
                'hospital_id' => $hospitalId,
                'tenant_id' => $hospitalId,
                'permission' => 'rbac.asignar',
                'reason' => 'role_assigned',
                'role_code' => $roleCode,
                'trace_id' => $traceId,
            ], 'info');

            return $membershipId;
        });

        return [
            'membership_id' => $membershipId,
            'user_id' => $targetUserId,
            'hospital_id' => $hospitalId,
            'role_code' => $roleCode,
            'roles' => $this->memberships->activeRoles($targetUserId, $hospitalId),
        ];
    }

    public function revoke(
        string $actorId,
        array $actorRoles,
        string $hospitalId,
        string $targetUserId,
        string $roleCode,
        ?string $traceId = null,
    ): array {
        $roleCode = $this->validateChange($actorId, $actorRoles, $targetUserId, $roleCode);
        $this->activeTarget($targetUserId);

        if (! $this->memberships->hasActiveRole($targetUserId, $hospitalId, $roleCode)) {
            throw AdminFailure::membershipNotFound();
        }

        $traceId ??= $this->newTraceId();

        DB::connection('central')->transaction(function () use (
            $actorId, $hospitalId, $targetUserId, $roleCode, $traceId,
        ): void {
            $this->memberships->revokeRole($targetUserId, $hospitalId, $roleCode);
            $this->sessions->revokeAllForUserInHospital($targetUserId, $hospitalId, 'roles_changed');
            $this->audit->logRaw('user.role_revoked', [
                'user_id' => $actorId,
                'target_user_id' => $targetUserId,
                'hospital_id' => $hospitalId,
                'tenant_id' => $hospitalId,
                'permission' => 'rbac.asignar',
                'reason' => 'role_revoked',
                'role_code' => $roleCode,
                'trace_id' => $traceId,
            ], 'warning');
        });

        return [
            'user_id' => $targetUserId,
            'hospital_id' => $hospitalId,
            'role_code' => $roleCode,
            'roles' => $this->memberships->activeRoles($targetUserId, $hospitalId),
        ];
    }

    public function deactivateInHospital(
        string $actorId,
        string $hospitalId,
        string $targetUserId,
        ?string $traceId = null,
    ): void {
        if (hash_equals($actorId, $targetUserId)) {
            throw AdminFailure::selfChange();
        }

        if (! $this->memberships->hasActiveMembership($actorId, $hospitalId)) {
            throw AdminFailure::actorNotMember();
        }

        $this->activeTarget($targetUserId);
        $traceId ??= $this->newTraceId();

        DB::connection('central')->transaction(function () use (
            $actorId, $hospitalId, $targetUserId, $traceId,
        ): void {
            $deactivated = $this->memberships->deactivateInHospital($targetUserId, $hospitalId);

            if ($deactivated === 0) {
                throw AdminFailure::activeMembershipNotFound();
            }

            $this->sessions->revokeAllForUserInHospital(
                $targetUserId,
                $hospitalId,
                'hospital_access_revoked',
            );
            $this->audit->logRaw('user.hospital_memberships_deactivated', [
                'user_id' => $actorId,
                'target_user_id' => $targetUserId,
                'hospital_id' => $hospitalId,
                'tenant_id' => $hospitalId,
                'reason' => 'hospital_access_revoked',
                'membership_count' => $deactivated,
                'trace_id' => $traceId,
            ], 'warning');
        });
    }

    public function revokeForeignSession(
        string $actorId,
        string $hospitalId,
        string $sessionId,
        ?string $traceId = null,
    ): void {
        $traceId ??= $this->newTraceId();

        DB::connection('central')->transaction(function () use (
            $actorId, $hospitalId, $sessionId, $traceId,
        ): void {
            $targetUserId = $this->sessions->revokeForeignSession(
                $sessionId,
                $hospitalId,
                $actorId,
                'admin_revoked',
            );

            if ($targetUserId === null) {
                throw AdminFailure::sessionNotFound();
            }

            $this->audit->logRaw('session.revoked_by_admin', [
                'user_id' => $actorId,
                'target_user_id' => $targetUserId,
                'hospital_id' => $hospitalId,
                'tenant_id' => $hospitalId,
                'permission' => 'sesiones.revocar_ajenas',
                'reason' => 'admin_revoked',
                'session_id' => $sessionId,
                'result' => 'success',
                'trace_id' => $traceId,
            ], 'warning');
        });
    }

    private function validateChange(
        string $actorId,
        array $actorRoles,
        string $targetUserId,
        string $roleCode,
    ): string {
        if (hash_equals($actorId, $targetUserId)) {
            throw AdminFailure::selfChange();
        }

        $roleCode = trim($roleCode);

        if (! CanonicalRole::isCanonical($roleCode)) {
            throw AdminFailure::unknownRole();
        }

        $actorRoles = CanonicalRole::canonicalize($actorRoles);

        if ($roleCode === 'superadmin' && ! in_array('superadmin', $actorRoles, true)) {
            throw AdminFailure::superadminRequiresSuperadmin();
        }

        return $roleCode;
    }

    private function activeTarget(string $targetUserId): CentralUser
    {
        $target = CentralUser::query()->find($targetUserId);

        if ($target === null) {
            throw AdminFailure::targetNotFound();
        }

        if ($target->status !== 'active') {
            throw AdminFailure::targetInactive();
        }

        return $target;
    }

    private function newTraceId(): string
    {
        return 'trace-'.bin2hex(random_bytes(6));
    }
}
