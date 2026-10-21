<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class ProvisionFirstSuperadminTest extends TenantAuthTestCase
{
    public function test_first_superadmin_is_provisioned_only_with_interactive_secret_input(): void
    {
        $password = 'First-Admin-2026!';

        $this->artisan('auth:provision-first-superadmin')
            ->expectsQuestion('Referencia del operador o ticket de cambio', 'CHG-123')
            ->expectsQuestion('UUID del hospital CENTRAL activo', $this->hospitalA)
            ->expectsQuestion('Correo de la cuenta administrativa', 'admin@example.test')
            ->expectsQuestion('Nombre para mostrar', 'Initial Admin')
            ->expectsQuestion(
                'Contraseña (mínimo 12 caracteres, mayúsculas, minúsculas, número y símbolo)',
                $password,
            )
            ->expectsQuestion('Confirme la contraseña', $password)
            ->expectsOutput('Cuenta inicial creada. La contraseña no se ha mostrado ni almacenado en texto plano.')
            ->assertExitCode(0);

        $user = DB::connection('central')->table('central_users')
            ->where('email', 'admin@example.test')->first();
        $credential = DB::connection('central')->table('auth_credentials')
            ->where('user_id', $user->user_id)->first();

        $this->assertNotSame($password, $credential->password_hash);
        $this->assertTrue(Hash::check($password, $credential->password_hash));
        $this->assertDatabaseHas('hospital_memberships', [
            'user_id' => $user->user_id,
            'hospital_id' => $this->hospitalA,
            'role_code' => 'superadmin',
            'status' => 'active',
        ], 'central');
        $this->assertDatabaseHas('audit_log', [
            'event' => 'auth.first_superadmin_provisioned',
            'hospital_id' => $this->hospitalA,
            'result' => 'success',
        ], 'central');
    }
}
