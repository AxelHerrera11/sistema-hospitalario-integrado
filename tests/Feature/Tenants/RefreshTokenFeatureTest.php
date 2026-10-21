<?php

namespace Tests\Feature\Tenants;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Tenants\Concerns\MicroHisHttp;

/**
 * Semana 15 · NB-004 (RF-06, RF-07): rotación de refresh y detección de reutilización vía
 * POST /auth/refresh.
 */
#[Group('caja-negra')]
class RefreshTokenFeatureTest extends TestCase
{
    use MicroHisHttp;

    private array $session;

    protected function setUp(): void
    {
        $this->bootMicroHis();
        $admin = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        $this->createUser($admin['token'], 'medico@a.test', ['medico']);
        $this->session = $this->login('medico@a.test', $admin['tenant'])['body'];
    }

    public function test_refresh_rotates_both_tokens_and_keeps_the_session(): void
    {
        $rotated = $this->call('POST', '/auth/refresh', ['refresh_token' => $this->session['refresh_token']]);

        $this->assertSame(200, $rotated['status']);
        $this->assertNotSame($this->session['refresh_token'], $rotated['body']['refresh_token']);
        $this->assertNotSame($this->session['access_token'], $rotated['body']['access_token']);
        $me = $this->call('GET', '/auth/me', [], null, $rotated['body']['access_token'])['body'];
        $this->assertSame($this->session['session_id'], $me['session_id']);
        $this->assertCount(1, $this->module->auditLog->findByEventName('TOKEN_REFRESHED'));
    }

    public function test_invalid_or_missing_refresh_token_is_rejected(): void
    {
        $this->assertSame(401, $this->call('POST', '/auth/refresh', ['refresh_token' => 'inventado'])['status']);
        $this->assertSame(422, $this->call('POST', '/auth/refresh', [])['status']);
    }

    public function test_reusing_a_rotated_refresh_token_revokes_the_session(): void
    {
        $rotated = $this->call('POST', '/auth/refresh', ['refresh_token' => $this->session['refresh_token']])['body'];

        $reuse = $this->call('POST', '/auth/refresh', ['refresh_token' => $this->session['refresh_token']]);

        $this->assertSame(401, $reuse['status']);
        $this->assertArrayNotHasKey('revoked_sessions', $reuse['body'], 'no se informa al atacante qué se revocó');
        $this->assertSame(401, $this->call('GET', '/auth/me', [], null, $rotated['access_token'])['status']);
        $this->assertSame(401, $this->call('POST', '/auth/refresh', ['refresh_token' => $rotated['refresh_token']])['status']);
        $alert = $this->module->auditLog->findByEventName('REFRESH_REUSE_DETECTED')[0];
        $this->assertSame('CRITICAL', $alert['severity']);
        $this->assertTrue($this->module->auditLog->verifyHashChain());
    }

    public function test_access_token_stays_valid_only_while_its_session_is_alive(): void
    {
        $this->call('POST', '/auth/logout', [], null, $this->session['access_token']);

        $this->assertSame(401, $this->call('POST', '/auth/refresh', ['refresh_token' => $this->session['refresh_token']])['status']);
    }
}
