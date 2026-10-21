<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
use App\Modules\Tenants\Domain\ValueObjects\Role;
final class MfaPolicy
{
    /** @param Role[] $userRoles */
    public function evaluate(array $userRoles, array $tenantPolicies, bool $userMfaRequired): MfaRequirement
    {
        if ($userMfaRequired || ($tenantPolicies['mfa_required'] ?? false)) {
            return new MfaRequirement(true);}
        foreach ($userRoles as $role) {
            if ($role->requiresMfa()) {
                return new MfaRequirement(true);}}
        return new MfaRequirement(false);}}
