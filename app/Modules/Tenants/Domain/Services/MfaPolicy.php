<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Entities\Tenant;
final class MfaPolicy
{
    public function evaluate(array $userRoles, array $tenantPolicies, bool $userMfaRequired): MfaRequirement
    {
        $required = false;
        $type = 'TOTP';
        if ($userMfaRequired) {
            $required = true;
        } elseif ($tenantPolicies['mfa_required'] ?? false) {
            $required = true;
        } else {
            foreach ($userRoles as $role) {
                if ($role->requiresMfa()) {
                    $required = true;
                    break;}}}
        if ($required && ($tenantPolicies['mfa_webauthn_enabled'] ?? false)) {
            $type = 'WEBAUTHN';
        } return new MfaRequirement($required, $type);}}
