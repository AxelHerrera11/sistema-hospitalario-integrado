<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants;

use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

final class SessionLifecycleTest extends TenantAuthTestCase
{
    public function test_logout_revokes_only_the_current_session(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico');

        $first = $this->tokenFrom($this->login()->assertOk());
        $second = $this->tokenFrom($this->login()->assertOk());

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($first)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($first)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error', 'SESSION_REVOKED');
        $this->assertDatabaseHas('audit_log', [
            'event' => 'auth.logout',
            'actor_id' => $this->userId,
            'hospital_id' => $this->hospitalA,
            'result' => 'success',
        ], 'central');

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($second)
            ->getJson('/api/v1/auth/me')
            ->assertOk();
    }

    public function test_refresh_rotates_the_session_and_invalidates_the_previous_token(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico');

        $login = $this->login()->assertOk();
        $oldToken = $this->tokenFrom($login);
        $refreshToken = $login->getCookie('refresh_token', false)->getValue();
        $this->assertDatabaseHas('auth_refresh_tokens', [
            'token_hash' => hash('sha256', $refreshToken),
        ], 'central');
        $this->assertDatabaseMissing('auth_refresh_tokens', [
            'token_hash' => $refreshToken,
        ], 'central');

        $refresh = $this->withCredentials()
            ->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withUnencryptedCookies(['refresh_token' => $refreshToken])
            ->postJson('/api/v1/auth/refresh');

        $refresh->assertOk()
            ->assertJsonPath('user.user_id', $this->userId)
            ->assertJsonPath('hospital.hospital_id', $this->hospitalA)
            ->assertJsonPath('roles', ['medico'])
            ->assertJsonPath('expires_in', 900)
            ->assertCookie('refresh_token');

        $newToken = $this->tokenFrom($refresh);
        $this->assertNotSame($oldToken, $newToken);

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($oldToken)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error', 'SESSION_REVOKED');

        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($newToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('roles', ['medico']);
    }

    public function test_refresh_token_cannot_be_used_from_another_hospital(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico');
        $login = $this->login()->assertOk();
        $refreshToken = $login->getCookie('refresh_token', false)->getValue();

        $this->withCredentials()
            ->withHeaders(['X-Tenant-ID' => $this->hospitalB])
            ->withUnencryptedCookies(['refresh_token' => $refreshToken])
            ->postJson('/api/v1/auth/refresh')
            ->assertUnauthorized()
            ->assertJsonPath('error', 'REFRESH_TOKEN_INVALID');
        $this->assertDatabaseHas('audit_log', [
            'event' => 'auth.refresh_failed',
            'hospital_id' => $this->hospitalB,
            'result' => 'denied',
        ], 'central');

        $this->withCredentials()
            ->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withUnencryptedCookies(['refresh_token' => $refreshToken])
            ->postJson('/api/v1/auth/refresh')
            ->assertOk();
    }

    public function test_expired_access_token_can_refresh_and_replay_revokes_the_family(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico');
        $login = $this->login()->assertOk();
        $oldAccessToken = $this->tokenFrom($login);
        $refreshToken = $login->getCookie('refresh_token', false)->getValue();
        $this->travel(16)->minutes();

        $refresh = $this->withCredentials()
            ->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withUnencryptedCookies(['refresh_token' => $refreshToken])
            ->postJson('/api/v1/auth/refresh')
            ->assertOk();
        $newAccessToken = $this->tokenFrom($refresh);
        $this->assertNotSame($oldAccessToken, $newAccessToken);
        $this->assertSame(1, DB::connection('central')->table('auth_sessions')->count());
        $this->assertSame(2, DB::connection('central')->table('auth_refresh_tokens')->count());

        $this->withCredentials()
            ->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withUnencryptedCookies(['refresh_token' => $refreshToken])
            ->postJson('/api/v1/auth/refresh')
            ->assertUnauthorized()
            ->assertJsonPath('error', 'REFRESH_TOKEN_REUSED');

        $this->assertSame(0, DB::connection('central')->table('auth_refresh_tokens')
            ->whereNull('revoked_at')->count());
        $this->assertDatabaseHas('auth_sessions', [
            'user_id' => $this->userId,
            'revoked_reason' => 'refresh_token_reused',
        ], 'central');
        $this->withHeaders(['X-Tenant-ID' => $this->hospitalA])
            ->withToken($newAccessToken)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    public function test_session_rows_are_scoped_to_issuer_audience_and_hospital_claims(): void
    {
        $this->addMembership($this->userId, $this->hospitalA, 'medico');
        $token = $this->tokenFrom($this->login()->assertOk());

        $payload = JWTAuth::setToken($token)->getPayload()->toArray();

        $this->assertSame(config('tenants.jwt.issuer'), $payload['iss']);
        $this->assertContains(config('tenants.jwt.audience'), (array) $payload['aud']);
        $this->assertSame($this->userId, $payload['sub']);
        $this->assertSame($this->hospitalA, $payload['tenant_id']);
        $this->assertSame(['medico'], $payload['roles']);
        $this->assertNotEmpty($payload['session_id']);
        $this->assertNotEmpty($payload['jti']);
    }
}
