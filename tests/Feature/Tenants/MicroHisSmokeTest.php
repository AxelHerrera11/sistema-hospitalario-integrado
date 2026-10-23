<?php

namespace Tests\Feature\Tenants;

use App\Modules\Tenants\Infrastructure\Security\TotpVerifier;
use App\Modules\Tenants\ModuleBootstrap;
use App\Modules\Tenants\Presentation\Router;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

/**
 * Smoke test HTTP del micro-HIS vanilla de Semana 3: recorre el router con peticiones PSR-7
 * reales, sin Laravel ni base de datos (php -S app/Modules/Tenants/micro-his.php usa el mismo router).
 */
class MicroHisSmokeTest extends TestCase
{
    private const PASSWORD = 'Clave-Segura-2026!';

    private ModuleBootstrap $module;

    private Router $router;

    private array $config = ['jwt_secret' => 'secreto-de-smoke-test-de-32-bytes!!', 'argon2' => ['memory' => 1024, 'time' => 1, 'threads' => 1]];

    protected function setUp(): void
    {
        $this->module = ModuleBootstrap::create();
        $this->router = $this->module->router($this->config);
    }

    public function test_admin_needs_totp_and_each_challenge_is_single_use(): void
    {
        $admin = $this->module->seedHospital($this->config, 'Hospital A', 'admin@a.test', self::PASSWORD);
        $tenant = $admin->tenantId()->value();

        $challenge = $this->call('POST', '/auth/login', ['email' => 'admin@a.test', 'password' => self::PASSWORD], $tenant);
        $this->assertSame(202, $challenge['status']);
        $this->assertArrayNotHasKey('access_token', $challenge['body']);

        $wrong = $this->call('POST', '/auth/mfa', ['challenge_id' => $challenge['body']['challenge_id'], 'code' => '000000']);
        $this->assertSame(401, $wrong['status']);

        $code = $this->totp($admin->totpSecret());
        $ok = $this->call('POST', '/auth/mfa', ['challenge_id' => $challenge['body']['challenge_id'], 'code' => $code]);
        $this->assertSame(200, $ok['status']);
        $this->assertSame(['admin_hospital'], $ok['body']['user']['roles']);

        $replay = $this->call('POST', '/auth/mfa', ['challenge_id' => $challenge['body']['challenge_id'], 'code' => $code]);
        $this->assertSame(401, $replay['status']);
    }

    public function test_refresh_rotates_and_reusing_an_old_refresh_revokes_the_session(): void
    {
        [$tenant, $adminToken] = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        $this->createUser($adminToken, 'medico@a.test', ['medico']);
        $login = $this->login('medico@a.test', $tenant);

        $this->assertSame(200, $this->call('GET', '/auth/me', [], null, $login['access_token'])['status']);
        $rotated = $this->call('POST', '/auth/refresh', ['refresh_token' => $login['refresh_token']]);
        $this->assertSame(200, $rotated['status']);
        $this->assertNotSame($login['refresh_token'], $rotated['body']['refresh_token']);

        $reuse = $this->call('POST', '/auth/refresh', ['refresh_token' => $login['refresh_token']]);
        $this->assertSame(401, $reuse['status']);
        $this->assertSame(401, $this->call('GET', '/auth/me', [], null, $rotated['body']['access_token'])['status'], 'la sesión quedó revocada');
        $this->assertSame(401, $this->call('POST', '/auth/refresh', ['refresh_token' => $rotated['body']['refresh_token']])['status']);
        $this->assertCount(1, $this->module->auditLog->findByEventName('REFRESH_REUSE_DETECTED'));
    }

    public function test_logout_invalidates_the_access_token_immediately(): void
    {
        [$tenant, $adminToken] = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        $this->createUser($adminToken, 'enfermeria@a.test', ['enfermeria']);
        $login = $this->login('enfermeria@a.test', $tenant);

        $this->assertSame(200, $this->call('POST', '/auth/logout', [], null, $login['access_token'])['status']);
        $this->assertSame(401, $this->call('GET', '/auth/me', [], null, $login['access_token'])['status']);
        $this->assertSame(401, $this->call('POST', '/auth/refresh', ['refresh_token' => $login['refresh_token']])['status']);
    }

    public function test_same_user_in_two_hospitals_keeps_roles_and_revocations_isolated(): void
    {
        [$tenantA, $adminA] = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        [$tenantB, $adminB] = $this->hospitalWithAdmin('Hospital B', 'admin@b.test');
        $userId = $this->createUser($adminA, 'ana@red.test', ['medico']);
        $this->createUser($adminB, 'ana@red.test', ['jefatura_medica'], $userId);

        $inA = $this->login('ana@red.test', $tenantA);
        $inB = $this->login('ana@red.test', $tenantB);
        $this->assertSame(['medico'], $inA['user']['roles']);
        $this->assertSame(['jefatura_medica'], $inB['user']['roles']);
        $this->assertSame($inA['user']['id'], $inB['user']['id']);

        $revoked = $this->call('DELETE', "/admin/users/{$userId}/sessions", [], null, $adminA);
        $this->assertSame(1, $revoked['body']['revoked_sessions']);
        $this->assertSame(401, $this->call('GET', '/auth/me', [], null, $inA['access_token'])['status']);
        $this->assertSame(200, $this->call('GET', '/auth/me', [], null, $inB['access_token'])['status'], 'el otro hospital no se ve afectado');

        $this->assertSame(403, $this->call('GET', '/auth/me', [], $tenantA, $inB['access_token'])['status'], 'token de B con cabecera de A');
        $this->assertCount(1, $this->module->auditLog->findByEventName('CROSS_TENANT_ATTEMPT'));
    }

    public function test_failed_logins_lock_the_membership_and_errors_use_real_http_status(): void
    {
        [$tenant, $adminToken] = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        $this->createUser($adminToken, 'recepcion@a.test', ['recepcion']);

        for ($i = 0; $i < 5; $i++) {
            $this->assertSame(401, $this->call('POST', '/auth/login', ['email' => 'recepcion@a.test', 'password' => 'incorrecta'], $tenant)['status']);
        }
        $this->assertSame(423, $this->call('POST', '/auth/login', ['email' => 'recepcion@a.test', 'password' => self::PASSWORD], $tenant)['status']);

        $medico = $this->login($this->createUserAndReturnEmail($adminToken, 'medico@a.test'), $tenant);
        $this->assertSame(403, $this->call('GET', '/admin/users', [], null, $medico['access_token'])['status']);
        $this->assertSame(403, $this->call('POST', '/admin/tenants', ['name' => 'X'], null, $adminToken)['status'], 'requiere superadmin');
        $this->assertSame(422, $this->call('POST', '/admin/users', ['email' => 'x@a.test', 'name' => 'X', 'password' => 'debil', 'roles' => ['medico']], null, $adminToken)['status']);
        $this->assertSame(422, $this->call('POST', '/admin/users', ['email' => 'y@a.test', 'name' => 'Y', 'password' => self::PASSWORD, 'roles' => ['MEDICO']], null, $adminToken)['status']);
        $this->assertSame(404, $this->call('GET', '/no-existe', [], null, $adminToken)['status']);
        $this->assertSame(405, $this->call('PUT', '/auth/me', [], null, $adminToken)['status']);
        $this->assertSame(401, $this->call('GET', '/admin/users')['status']);
        $this->assertTrue($this->module->auditLog->verifyHashChain());
    }

    public function test_state_survives_between_requests_through_the_state_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'micro-his');
        [$tenant, $adminToken] = $this->hospitalWithAdmin('Hospital A', 'admin@a.test');
        $this->module->saveTo($path);

        $restored = ModuleBootstrap::fromFile($path)->router($this->config);
        $request = (new ServerRequest('GET', '/auth/me', ['Authorization' => "Bearer {$adminToken}"], null, '1.1', ['REMOTE_ADDR' => '10.0.0.1']));
        $response = $restored->dispatch($request);
        unlink($path);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($tenant, json_decode((string) $response->getBody(), true)['tenant_id']);
    }

    /** @return array{0: string, 1: string} [tenant_id, access token del admin_hospital] */
    private function hospitalWithAdmin(string $hospital, string $email): array
    {
        $admin = $this->module->seedHospital($this->config, $hospital, $email, self::PASSWORD);
        $tenant = $admin->tenantId()->value();
        $challenge = $this->call('POST', '/auth/login', ['email' => $email, 'password' => self::PASSWORD], $tenant)['body'];
        $tokens = $this->call('POST', '/auth/mfa', ['challenge_id' => $challenge['challenge_id'], 'code' => $this->totp($admin->totpSecret())])['body'];

        return [$tenant, $tokens['access_token']];
    }

    private function createUser(string $adminToken, string $email, array $roles, ?string $userId = null): string
    {
        $body = ['email' => $email, 'name' => 'Usuario ' . $email, 'password' => self::PASSWORD, 'roles' => $roles] + ($userId ? ['user_id' => $userId] : []);
        $created = $this->call('POST', '/admin/users', $body, null, $adminToken);
        $this->assertSame(201, $created['status'], json_encode($created['body']));

        return $created['body']['user_id'];
    }

    private function createUserAndReturnEmail(string $adminToken, string $email): string
    {
        $this->createUser($adminToken, $email, ['medico']);

        return $email;
    }

    private function login(string $email, string $tenant): array
    {
        $login = $this->call('POST', '/auth/login', ['email' => $email, 'password' => self::PASSWORD], $tenant);
        $this->assertSame(200, $login['status'], json_encode($login['body']));

        return $login['body'];
    }

    private function totp(string $secret): string
    {
        return (new TotpVerifier())->codeAt($secret, intdiv(time(), 30));
    }

    /** @return array{status: int, body: array} */
    private function call(string $method, string $path, array $body = [], ?string $tenant = null, ?string $token = null): array
    {
        $headers = ['Content-Type' => 'application/json', 'X-Device-ID' => 'equipo-01', 'User-Agent' => 'PHPUnit']
            + ($tenant ? ['X-Tenant-ID' => $tenant] : [])
            + ($token ? ['Authorization' => "Bearer {$token}"] : []);
        // withParsedBody([]) reproduce ServerRequest::fromGlobals(), que entrega $_POST vacío en peticiones JSON.
        $request = (new ServerRequest($method, $path, $headers, $body === [] ? null : json_encode($body), '1.1', ['REMOTE_ADDR' => '10.0.0.1']))->withParsedBody([]);
        $response = $this->router->dispatch($request);

        return ['status' => $response->getStatusCode(), 'body' => json_decode((string) $response->getBody(), true) ?? []];
    }
}
