<?php

namespace Tests\Feature\Tenants;

use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Tenants\Concerns\MicroHisHttp;

/**
 * Semana 15 · NB-002 (RF-04, RF-05, RF-11): login con y sin MFA TOTP, credenciales inválidas,
 * cuenta inactiva y bloqueo progresivo.
 */
#[Group('caja-negra')]
class LoginMfaFeatureTest extends TestCase
{
    use MicroHisHttp;

    private array $admin;

    protected function setUp(): void
    {
        $this->bootMicroHis();
        $this->admin = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
    }

    public function test_login_without_mfa_returns_tokens(): void
    {
        $this->createUser($this->admin['token'], 'medico@a.test', ['medico']);

        $login = $this->login('medico@a.test', $this->admin['tenant']);

        $this->assertSame(200, $login['status']);
        $this->assertSame('Bearer', $login['body']['token_type']);
        $this->assertSame(900, $login['body']['expires_in']);
        $this->assertSame(['medico'], $login['body']['user']['roles']);
        $this->assertArrayNotHasKey('mfa_required', $login['body']);
    }

    public function test_login_with_totp_needs_a_valid_single_use_challenge(): void
    {
        $secret = $this->createUser($this->admin['token'], 'admin2@a.test', ['admin_hospital'])['body']['totp_secret'];

        $challenge = $this->login('admin2@a.test', $this->admin['tenant']);
        $this->assertSame(202, $challenge['status']);
        $this->assertSame('TOTP', $challenge['body']['method']);
        $this->assertArrayNotHasKey('access_token', $challenge['body']);

        $ok = $this->call('POST', '/auth/mfa', ['challenge_id' => $challenge['body']['challenge_id'], 'code' => $this->totp($secret)]);
        $this->assertSame(200, $ok['status']);
        $this->assertTrue($this->module->auditLog->findByEventName('LOGIN_SUCCESS')[1]['payload']['mfa_used']);

        $replay = $this->call('POST', '/auth/mfa', ['challenge_id' => $challenge['body']['challenge_id'], 'code' => $this->totp($secret)]);
        $this->assertSame(401, $replay['status']);
    }

    public function test_three_wrong_codes_lock_the_account(): void
    {
        $this->createUser($this->admin['token'], 'admin2@a.test', ['admin_hospital']);
        $challenge = $this->login('admin2@a.test', $this->admin['tenant'])['body']['challenge_id'];

        for ($i = 0; $i < 3; $i++) {
            $this->assertSame(401, $this->call('POST', '/auth/mfa', ['challenge_id' => $challenge, 'code' => '000000'])['status']);
        }

        $this->assertSame(423, $this->login('admin2@a.test', $this->admin['tenant'])['status']);
        $this->assertSame('mfa', $this->module->auditLog->findByEventName('ACCOUNT_LOCKED')[0]['payload']['lock_type']);
    }

    public function test_unknown_email_and_wrong_password_give_the_same_answer(): void
    {
        $this->createUser($this->admin['token'], 'medico@a.test', ['medico']);

        $unknown = $this->login('nadie@a.test', $this->admin['tenant']);
        $wrong = $this->login('medico@a.test', $this->admin['tenant'], 'incorrecta');

        $this->assertSame(401, $unknown['status']);
        $this->assertSame(401, $wrong['status']);
        $this->assertSame($unknown['body']['message'], $wrong['body']['message'], 'no revela si el correo existe');
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $userId = $this->createUser($this->admin['token'], 'medico@a.test', ['medico'])['body']['user_id'];
        $this->module->users->findByIdAndTenant(new UserId($userId), new TenantId($this->admin['tenant']))->deactivate();

        $this->assertSame(403, $this->login('medico@a.test', $this->admin['tenant'])['status']);
    }

    public function test_five_failures_lock_and_a_correct_password_is_still_refused(): void
    {
        $this->createUser($this->admin['token'], 'recepcion@a.test', ['recepcion']);

        for ($i = 0; $i < 5; $i++) {
            $this->assertSame(401, $this->login('recepcion@a.test', $this->admin['tenant'], 'incorrecta')['status']);
        }

        $this->assertSame(423, $this->login('recepcion@a.test', $this->admin['tenant'])['status']);
        $locked = $this->module->auditLog->findByEventName('ACCOUNT_LOCKED')[0]['payload'];
        $this->assertSame(['login', 900, 5], [$locked['lock_type'], $locked['duration_seconds'], $locked['failed_attempts']]);
    }

    public function test_login_requires_the_hospital_header(): void
    {
        $response = $this->call('POST', '/auth/login', ['email' => 'medico@a.test', 'password' => self::PASSWORD]);

        $this->assertSame(400, $response['status']);
    }
}
