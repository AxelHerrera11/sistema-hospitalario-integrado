<?php

namespace Tests\Feature\Tenants;

use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Tenants\Concerns\MicroHisHttp;

/**
 * Semana 15 · NB-005 (RF-10): aislamiento entre hospitales con el mismo usuario y dispositivo.
 */
#[Group('caja-negra')]
class MultiTenantIsolationFeatureTest extends TestCase
{
    use MicroHisHttp;

    protected function setUp(): void
    {
        $this->bootMicroHis();
    }

    public function test_token_of_hospital_a_cannot_be_used_against_hospital_b(): void
    {
        $a = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        $b = $this->hospitalWithAdmin('Hospital B', 'admin@b.test');

        $response = $this->call('GET', '/admin/users', [], $b['tenant'], $a['token']);

        $this->assertSame(403, $response['status']);
        $attempt = $this->module->auditLog->findByEventName('CROSS_TENANT_ATTEMPT')[0];
        $this->assertSame($a['tenant'], $attempt['payload']['tenant_id']);
        $this->assertSame($b['tenant'], $attempt['payload']['target_tenant_id']);
    }

    public function test_user_list_only_shows_the_admin_hospital(): void
    {
        $a = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        $b = $this->hospitalWithAdmin('Hospital B', 'admin@b.test');
        $this->createUser($a['token'], 'solo-a@red.test', ['medico']);

        $emails = array_column($this->call('GET', '/admin/users', [], null, $b['token'])['body']['data'], 'email');

        $this->assertSame(['admin@b.test'], $emails);
    }

    public function test_same_user_and_device_keep_roles_and_sessions_per_hospital(): void
    {
        $a = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        $b = $this->hospitalWithAdmin('Hospital B', 'admin@b.test');
        $userId = $this->createUser($a['token'], 'ana@red.test', ['medico'])['body']['user_id'];
        $this->assertSame(201, $this->createUser($b['token'], 'ana@red.test', ['jefatura_medica'], $userId)['status']);

        $inA = $this->login('ana@red.test', $a['tenant'])['body'];
        $inB = $this->login('ana@red.test', $b['tenant'])['body'];

        $this->assertSame($inA['user']['id'], $inB['user']['id']);
        $this->assertSame(['medico'], $inA['user']['roles']);
        $this->assertSame(['jefatura_medica'], $inB['user']['roles']);

        // Revocar en A no toca la sesión del mismo usuario y dispositivo en B.
        $this->call('DELETE', "/admin/users/{$userId}/sessions", [], null, $a['token']);
        $this->assertSame(401, $this->call('GET', '/auth/me', [], null, $inA['access_token'])['status']);
        $this->assertSame(200, $this->call('GET', '/auth/me', [], null, $inB['access_token'])['status']);
        $this->assertSame(401, $this->call('POST', '/auth/refresh', ['refresh_token' => $inA['refresh_token']])['status']);
        $this->assertSame(200, $this->call('POST', '/auth/refresh', ['refresh_token' => $inB['refresh_token']])['status']);
    }

    public function test_failed_logins_lock_only_the_membership_of_that_hospital(): void
    {
        $a = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        $b = $this->hospitalWithAdmin('Hospital B', 'admin@b.test');
        $userId = $this->createUser($a['token'], 'ana@red.test', ['medico'])['body']['user_id'];
        $this->createUser($b['token'], 'ana@red.test', ['medico'], $userId);

        for ($i = 0; $i < 5; $i++) {
            $this->login('ana@red.test', $a['tenant'], 'incorrecta');
        }

        $this->assertSame(423, $this->login('ana@red.test', $a['tenant'])['status']);
        $this->assertSame(200, $this->login('ana@red.test', $b['tenant'])['status']);
    }

    public function test_suspended_hospital_blocks_login(): void
    {
        $a = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        $this->createUser($a['token'], 'medico@a.test', ['medico']);
        $this->module->tenants->findById(new TenantId($a['tenant']))->suspend();

        $this->assertSame(403, $this->login('medico@a.test', $a['tenant'])['status']);
        $this->assertCount(1, $this->module->auditLog->findByEventName('TENANT_INACTIVE'));
    }
}
