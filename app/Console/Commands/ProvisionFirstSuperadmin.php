<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

final class ProvisionFirstSuperadmin extends Command
{
    protected $signature = 'auth:provision-first-superadmin';

    protected $description = 'Provisiona interactivamente la primera cuenta superadmin.';

    public function handle(AuditLogRepositoryInterface $audit): int
    {
        if (DB::connection('central')->table('hospital_memberships')
            ->where('role_code', 'superadmin')->exists()) {
            $this->error('Ya existe una membresía superadmin; el bootstrap inicial no está disponible.');

            return self::FAILURE;
        }

        $operator = trim((string) $this->ask('Referencia del operador o ticket de cambio'));
        $hospitalId = trim((string) $this->ask('UUID del hospital CENTRAL activo'));
        $email = mb_strtolower(trim((string) $this->ask('Correo de la cuenta administrativa')));
        $displayName = trim((string) $this->ask('Nombre para mostrar'));

        $hospital = DB::connection('central')->table('hospitals')
            ->where('hospital_id', $hospitalId)
            ->where('status', 'active')
            ->first();

        if ($hospital === null) {
            $this->error('El hospital no existe o no está activo.');

            return self::FAILURE;
        }

        $password = $this->secret('Contraseña (mínimo 12 caracteres, mayúsculas, minúsculas, número y símbolo)');
        $confirmation = $this->secret('Confirme la contraseña');

        if (! is_string($password) || $password === '' || $password !== $confirmation) {
            $this->error('La contraseña no coincide con la confirmación.');

            return self::FAILURE;
        }

        $validator = Validator::make([
            'operator' => $operator,
            'email' => $email,
            'display_name' => $displayName,
            'password' => $password,
        ], [
            'operator' => ['required', 'string', 'max:128'],
            'email' => ['required', 'email', 'max:255'],
            'display_name' => ['required', 'string', 'max:255'],
            'password' => [
                'required',
                'string',
                'min:12',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $userId = (string) Str::uuid();
        $now = now();
        DB::connection('central')->transaction(function () use (
            $audit,
            $displayName,
            $email,
            $hospitalId,
            $now,
            $operator,
            $password,
            $userId,
        ): void {
            $central = DB::connection('central');

            if ($central->getDriverName() === 'pgsql') {
                $central->select("SELECT pg_advisory_xact_lock(hashtext('auth.first_superadmin'))");
            }

            if ($central->table('hospital_memberships')->where('role_code', 'superadmin')->exists()
                || $central->table('auth_credentials')->where('email_normalized', $email)->exists()
                || $central->table('central_users')->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                throw new \RuntimeException('Ya existe una cuenta o membresía superadmin.');
            }

            $central->table('central_users')->insert([
                'user_id' => $userId,
                'subject' => 'bootstrap:'.$userId,
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
            $central->table('hospital_memberships')->insert([
                'membership_id' => (string) Str::uuid(),
                'user_id' => $userId,
                'hospital_id' => $hospitalId,
                'role_code' => 'superadmin',
                'status' => 'active',
                'valid_from' => $now,
                'valid_to' => null,
                'metadata' => '{}',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $audit->logRaw('auth.first_superadmin_provisioned', [
                'actor_id' => 'bootstrap:'.$operator,
                'hospital_id' => $hospitalId,
                'tenant_id' => $hospitalId,
                'target_user_id' => $userId,
                'reason' => 'controlled_initial_provisioning',
                'result' => 'success',
                'trace_id' => 'trace-'.bin2hex(random_bytes(6)),
            ], 'critical');
        });

        $this->info('Cuenta inicial creada. La contraseña no se ha mostrado ni almacenado en texto plano.');

        return self::SUCCESS;
    }
}
