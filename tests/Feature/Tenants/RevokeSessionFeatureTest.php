<?php

namespace Tests\Feature\Tenants;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Tenants\Concerns\MicroHisHttp;

/**
 * Semana 15 · NB-003 (RF-09): logout propio y revocación forzada por admin_hospital.
 * La revocación es inmediata e irreversible: no existe «Deshacer».
 */
#[Group('caja-negra')]
class RevokeSessionFeatureTest extends TestCase
{
    use MicroHisHttp;

    protected function setUp(): void
    {
        $this->bootMicroHis();
    }

    public function test_logout_revokes_own_session_immediately(): void
    {
        $admin = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        $this->createUser($admin['token'], 'medico@a.test', ['medico']);
        $session = $this->login('medico@a.test', $admin['tenant'])['body'];

        $logout = $this->call('POST', '/auth/logout', [], null, $session['access_token']);

        $this->assertSame(200, $logout['status']);
        $this->assertSame(1, $logout['body']['revoked_sessions']);
        $this->assertSame(401, $this->call('GET', '/auth/me', [], null, $session['access_token'])['status']);
        $this->assertSame(401, $this->call('POST', '/auth/refresh', ['refresh_token' => $session['refresh_token']])['status']);
    }

    public function test_admin_force_revokes_every_session_of_a_user(): void
    {
        $admin = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        $userId = $this->createUser($admin['token'], 'medico@a.test', ['medico'])['body']['user_id'];
        $phone = $this->login('medico@a.test', $admin['tenant'], device: 'telefono')['body'];
        $laptop = $this->login('medico@a.test', $admin['tenant'], device: 'portatil')['body'];

        $revoked = $this->call('DELETE', "/admin/users/{$userId}/sessions", [], null, $admin['token']);

        $this->assertSame(200, $revoked['status']);
        $this->assertSame(2, $revoked['body']['revoked_sessions']);
        foreach ([$phone, $laptop] as $session) {
            $this->assertSame(401, $this->call('GET', '/auth/me', [], null, $session['access_token'])['status']);
        }
        $event = $this->module->auditLog->findByEventName('SESSION_REVOKED')[0];
        $this->assertSame('admin_revoke', $event['payload']['reason']);
        $this->assertSame($admin['user'], $event['payload']['requester_id']);
        $this->assertSame(200, $this->call('GET', '/auth/me', [], null, $admin['token'])['status'], 'el admin conserva su sesión');
    }

    public function test_revoking_an_unknown_user_returns_404(): void
    {
        $admin = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');

        $response = $this->call('DELETE', '/admin/users/9b2f6a2e-1c1d-4f5e-9a7b-3c2d1e0f4a5b/sessions', [], null, $admin['token']);

        $this->assertSame(404, $response['status']);
    }

    public function test_non_admin_cannot_force_revoke(): void
    {
        $admin = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        $this->createUser($admin['token'], 'medico@a.test', ['medico']);
        $medico = $this->login('medico@a.test', $admin['tenant'])['body'];

        $response = $this->call('DELETE', "/admin/users/{$admin['user']}/sessions", [], null, $medico['access_token']);

        $this->assertSame(403, $response['status']);
        $this->assertSame(200, $this->call('GET', '/auth/me', [], null, $admin['token'])['status']);
    }
}
