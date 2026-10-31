<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Application\Administration;

use App\Modules\Tenants\Domain\Administration\AdminFailure;
use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Identity\CanonicalRole;
use App\Modules\Tenants\Infrastructure\Persistence\MembershipRepository;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Alta de usuario por tenant: crea la identidad CENTRAL, su credencial local y
 * la primera membresía en el hospital de la sesión, en una sola transacción.
 * La contraseña llega ya validada por la política del controlador.
 */
final class CreateHospitalUser
{
    public function __construct(
        private readonly MembershipRepository $memberships,
        private readonly AuditLogRepositoryInterface $audit,
    ) {}

    public function execute(
        string $actorId,
        array $actorRoles,
        string $hospitalId,
        string $email,
        string $displayName,
        string $password,
        string $roleCode,
        ?string $traceId = null,
    ): array {
        $email = mb_strtolower(trim($email));
        $displayName = trim($displayName);
        $roleCode = trim($roleCode);

        if (! CanonicalRole::isCanonical($roleCode)) {
            throw AdminFailure::unknownRole();
        }

        if ($roleCode === 'superadmin' && ! in_array('superadmin', CanonicalRole::canonicalize($actorRoles), true)) {
            throw AdminFailure::superadminRequiresSuperadmin();
        }

        if (! $this->memberships->hasActiveMembership($actorId, $hospitalId)) {
            throw AdminFailure::actorNotMember();
        }

        $central = DB::connection('central');
        $userId = (string) Str::uuid();
        $traceId ??= 'trace-'.bin2hex(random_bytes(6));

        try {
            $central->transaction(function () use (
                $central, $actorId, $hospitalId, $userId, $email, $displayName, $password, $roleCode, $traceId,
            ): void {
                if ($central->table('auth_credentials')->where('email_normalized', $email)->exists()
                    || $central->table('central_users')->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                    throw AdminFailure::emailTaken();
                }

                $now = now();
                $central->table('central_users')->insert([
                    'user_id' => $userId,
                    'subject' => 'local:'.$userId,
                    'email' => $email,
                    'display_name' => $displayName,
                    'status' => 'active',
                    'metadata' => '{}',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $central->table('auth_credentials')->insert([
                    'user_id' => $userId,
                    'email_normalized' => $email,
                    'password_hash' => Hash::make($password),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->memberships->assignRole($userId, $hospitalId, $roleCode);

                // Sin correo ni nombre en el contexto: la auditoría identifica al usuario por su id.
                $this->audit->logRaw('user.created', [
                    'user_id' => $actorId,
                    'target_user_id' => $userId,
                    'hospital_id' => $hospitalId,
                    'tenant_id' => $hospitalId,
                    'permission' => 'usuarios.crear',
                    'reason' => 'user_created',
                    'role_code' => $roleCode,
                    'result' => 'success',
                    'trace_id' => $traceId,
                ], 'info');
            });
        } catch (UniqueConstraintViolationException) {
            // Dos altas simultáneas con el mismo correo: la restricción única decide.
            throw AdminFailure::emailTaken();
        }

        return [
            'user_id' => $userId,
            'email' => $email,
            'display_name' => $displayName,
            'status' => 'active',
            'hospital_id' => $hospitalId,
            'roles' => [$roleCode],
        ];
    }
}
