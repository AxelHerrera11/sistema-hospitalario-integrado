<?php

namespace Tests\Feature\Tenants;

use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Tenants\Concerns\MicroHisHttp;

/**
 * Semana 15 · NB-001 (RF-01, RF-02, RF-03): alta de usuario por hospital vía POST /admin/users.
 */
#[Group('caja-negra')]
class RegisterUserFeatureTest extends TestCase
{
    use MicroHisHttp;

    protected function setUp(): void
    {
        $this->bootMicroHis();
    }

    public function test_admin_registers_a_user_in_its_own_hospital(): void
    {
        $admin = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');

        $created = $this->createUser($admin['token'], 'medico@a.test', ['medico']);

        $this->assertSame(201, $created['status']);
        $this->assertSame($admin['tenant'], $created['body']['tenant_id']);
        $this->assertNull($created['body']['totp_secret'], 'medico no requiere MFA');
        $this->assertSame(200, $this->login('medico@a.test', $admin['tenant'])['status']);
        $this->assertCount(2, $this->module->auditLog->findByEventName('USER_CREATED'), 'admin sembrado + médico');
    }

    public function test_privileged_role_gets_totp_enrollment_once(): void
    {
        $admin = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');

        $created = $this->createUser($admin['token'], 'admin2@a.test', ['admin_hospital']);

        $this->assertSame(201, $created['status']);
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $created['body']['totp_secret']);
    }

    public function test_inactive_hospital_rejects_registration(): void
    {
        $admin = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        $this->module->tenants->findById(new TenantId($admin['tenant']))->suspend();

        $this->assertSame(403, $this->createUser($admin['token'], 'x@a.test', ['medico'])['status']);
    }

    public function test_quota_exceeded_rejects_registration(): void
    {
        $admin = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        $tenant = $this->module->tenants->findById(new TenantId($admin['tenant']));
        while ($tenant->hasQuota()) {
            $tenant->incrementUsersCount();
        }

        $response = $this->createUser($admin['token'], 'x@a.test', ['medico']);

        $this->assertSame(409, $response['status']);
        $this->assertCount(1, $this->module->auditLog->findByEventName('TENANT_QUOTA_EXCEEDED'));
    }

    public function test_duplicate_email_in_same_hospital_is_rejected(): void
    {
        $admin = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        $this->createUser($admin['token'], 'medico@a.test', ['medico']);

        $this->assertSame(409, $this->createUser($admin['token'], 'MEDICO@a.test', ['enfermeria'])['status']);
    }

    public function test_weak_password_and_non_canonical_roles_are_rejected(): void
    {
        $admin = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');

        $weak = $this->call('POST', '/admin/users', ['email' => 'y@a.test', 'name' => 'Y', 'password' => 'debil', 'roles' => ['medico']], null, $admin['token']);
        $upper = $this->createUser($admin['token'], 'z@a.test', ['MEDICO']);

        $this->assertSame(422, $weak['status']);
        $this->assertStringContainsString('12 caracteres', $weak['body']['message']);
        $this->assertSame(422, $upper['status']);
    }

    public function test_only_admin_hospital_can_register_users(): void
    {
        $admin = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        $this->createUser($admin['token'], 'medico@a.test', ['medico']);
        $medico = $this->login('medico@a.test', $admin['tenant'])['body'];

        $this->assertSame(403, $this->createUser($medico['access_token'], 'otro@a.test', ['medico'])['status']);
        $this->assertSame(401, $this->createUser('token-falso', 'otro@a.test', ['medico'])['status']);
    }
}
