<?php

namespace Tests\Feature\Tenants\Concerns;

use App\Modules\Tenants\Infrastructure\Security\TotpVerifier;
use App\Modules\Tenants\ModuleBootstrap;
use App\Modules\Tenants\Presentation\Router;
use GuzzleHttp\Psr7\ServerRequest;

/**
 * Cliente HTTP en proceso para las pruebas de caja negra del micro-HIS (Semana 3):
 * cada llamada es una petición PSR-7 real que recorre middleware, router y controladores.
 */
trait MicroHisHttp
{
    protected const PASSWORD = 'Clave-Segura-2026!';

    protected ModuleBootstrap $module;

    protected Router $router;

    protected array $config = ['jwt_secret' => 'secreto-de-pruebas-semana-15-32-bytes!', 'argon2' => ['memory' => 1024, 'time' => 1, 'threads' => 1]];

    protected function bootMicroHis(): void
    {
        $this->module = ModuleBootstrap::create();
        $this->router = $this->module->router($this->config);
    }

    /** @return array{tenant: string, token: string, user: string} hospital nuevo con su admin_hospital ya autenticado */
    protected function hospitalWithAdmin(string $hospital, string $email): array
    {
        $admin = $this->module->seedHospital($this->config, $hospital, $email, self::PASSWORD);
        $tenant = $admin->tenantId()->value();
        $challenge = $this->call('POST', '/auth/login', ['email' => $email, 'password' => self::PASSWORD], $tenant)['body'];
        $tokens = $this->call('POST', '/auth/mfa', ['challenge_id' => $challenge['challenge_id'], 'code' => $this->totp($admin->totpSecret())])['body'];

        return ['tenant' => $tenant, 'token' => $tokens['access_token'], 'user' => $admin->userId()->value()];
    }

    protected function createUser(string $adminToken, string $email, array $roles, ?string $userId = null): array
    {
        $body = ['email' => $email, 'name' => 'Usuario ' . $email, 'password' => self::PASSWORD, 'roles' => $roles] + ($userId ? ['user_id' => $userId] : []);

        return $this->call('POST', '/admin/users', $body, null, $adminToken);
    }

    protected function login(string $email, string $tenant, string $password = self::PASSWORD, string $device = 'equipo-01'): array
    {
        return $this->call('POST', '/auth/login', ['email' => $email, 'password' => $password], $tenant, null, $device);
    }

    protected function totp(string $secret): string
    {
        return (new TotpVerifier())->codeAt($secret, intdiv(time(), 30));
    }

    /** @return array{status: int, body: array} */
    protected function call(string $method, string $path, array $body = [], ?string $tenant = null, ?string $token = null, string $device = 'equipo-01'): array
    {
        $headers = ['Content-Type' => 'application/json', 'X-Device-ID' => $device, 'User-Agent' => 'PHPUnit']
            + ($tenant ? ['X-Tenant-ID' => $tenant] : [])
            + ($token ? ['Authorization' => "Bearer {$token}"] : []);
        $request = (new ServerRequest($method, $path, $headers, $body === [] ? null : json_encode($body), '1.1', ['REMOTE_ADDR' => '10.0.0.1']))->withParsedBody([]);
        $response = $this->router->dispatch($request);

        return ['status' => $response->getStatusCode(), 'body' => json_decode((string) $response->getBody(), true) ?? []];
    }
}
