<?php
declare(strict_types=1);
namespace App\Modules\Tenants;
use App\Modules\Tenants\Application\Commands\RegisterTenantCommand;
use App\Modules\Tenants\Application\Commands\RegisterUserCommand;
use App\Modules\Tenants\Application\Results\RegisterUserResult;
use App\Modules\Tenants\Application\UseCases\AuthenticateUserUseCase;
use App\Modules\Tenants\Application\UseCases\RefreshTokenUseCase;
use App\Modules\Tenants\Application\UseCases\RegisterTenantUseCase;
use App\Modules\Tenants\Application\UseCases\RegisterUserUseCase;
use App\Modules\Tenants\Application\UseCases\RevokeSessionUseCase;
use App\Modules\Tenants\Domain\Services\Argon2idHasher;
use App\Modules\Tenants\Domain\Services\MfaPolicy;
use App\Modules\Tenants\Domain\Services\PasswordPolicy;
use App\Modules\Tenants\Domain\Services\RefreshTokenRotator;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use App\Modules\Tenants\Infrastructure\External\HaveIBeenPwnedClient;
use App\Modules\Tenants\Infrastructure\Persistence\InMemory\InMemoryAuditLogRepository;
use App\Modules\Tenants\Infrastructure\Persistence\InMemory\InMemoryMfaChallengeRepository;
use App\Modules\Tenants\Infrastructure\Persistence\InMemory\InMemoryRefreshTokenRepository;
use App\Modules\Tenants\Infrastructure\Persistence\InMemory\InMemorySessionRepository;
use App\Modules\Tenants\Infrastructure\Persistence\InMemory\InMemoryTenantRepository;
use App\Modules\Tenants\Infrastructure\Persistence\InMemory\InMemoryUserRepository;
use App\Modules\Tenants\Infrastructure\Security\JwtTokenService;
use App\Modules\Tenants\Infrastructure\Security\TotpVerifier;
use App\Modules\Tenants\Presentation\Controllers\AdminTenantController;
use App\Modules\Tenants\Presentation\Controllers\AdminUserController;
use App\Modules\Tenants\Presentation\Controllers\AuthController;
use App\Modules\Tenants\Presentation\Middleware\JwtAuthMiddleware;
use App\Modules\Tenants\Presentation\Middleware\RateLimitMiddleware;
use App\Modules\Tenants\Presentation\Middleware\TenantMiddleware;
use App\Modules\Tenants\Presentation\Router;
use App\Modules\Tenants\Presentation\Transformers\AuthResponseTransformer;
use ArrayObject;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
/**
 * Arranque del micro-HIS de Tenants en PHP vanilla (Semana 3). Cablea las dependencias a mano,
 * sin contenedor DI, y no toca el arranque de Laravel (public/index.php sigue intacto).
 * El estado vive en repositorios en memoria que pueden guardarse en un archivo entre peticiones;
 * no escribe en las tablas central_users/hospitals de Laravel.
 *
 * Dependencias usadas (declaradas en composer.json): lcobucci/jwt ^4.0, guzzlehttp/guzzle ^7, ramsey/uuid ^4.
 */
final class ModuleBootstrap
{
    public readonly InMemoryUserRepository $users;
    public readonly InMemoryTenantRepository $tenants;
    public readonly InMemorySessionRepository $sessions;
    public readonly InMemoryRefreshTokenRepository $refreshTokens;
    public readonly InMemoryMfaChallengeRepository $challenges;
    public readonly InMemoryAuditLogRepository $auditLog;
    public readonly ArrayObject $rateLimitHits;
    private function __construct()
    {
        $this->users = new InMemoryUserRepository();
        $this->tenants = new InMemoryTenantRepository();
        $this->sessions = new InMemorySessionRepository();
        $this->refreshTokens = new InMemoryRefreshTokenRepository();
        $this->challenges = new InMemoryMfaChallengeRepository();
        $this->auditLog = new InMemoryAuditLogRepository();
        $this->rateLimitHits = new ArrayObject();}
    public static function create(): self
    {
        return new self();}
    public static function fromFile(string $path): self
    {
        if (!is_file($path)) {
            return new self();}
        $state = unserialize((string) file_get_contents($path), ['allowed_classes' => true]);
        return $state instanceof self ? $state : new self();}
    public function saveTo(string $path): void
    {
        file_put_contents($path, serialize($this), LOCK_EX);}
    /**
     * @param array{jwt_secret: string, jwt_issuer?: string, jwt_audience?: string, access_ttl?: int,
     *              refresh_ttl?: int, check_hibp?: bool, argon2?: array{memory: int, time: int, threads: int}} $config
     */
    public function router(array $config): Router
    {
        $responses = new AuthResponseTransformer(new HttpFactory());
        $argon = $config['argon2'] ?? ['memory' => 65536, 'time' => 4, 'threads' => 1];
        $hasher = new Argon2idHasher($argon['memory'], $argon['time'], $argon['threads']);
        $tokens = new JwtTokenService($config['jwt_secret'], $config['jwt_issuer'] ?? 'his-tenants-auth', $config['jwt_audience'] ?? 'his-api', $config['access_ttl'] ?? 900);
        $rotator = new RefreshTokenRotator($this->refreshTokens, $config['refresh_ttl'] ?? 604800);
        $totp = new TotpVerifier();
        $revoke = new RevokeSessionUseCase($this->users, $this->sessions, $this->refreshTokens, $this->auditLog);
        $auth = new AuthController(
            new AuthenticateUserUseCase($this->users, $this->tenants, $this->sessions, $this->refreshTokens, $this->challenges, $this->auditLog, $hasher, new MfaPolicy(), $totp, $tokens, $rotator),
            new RefreshTokenUseCase($this->users, $this->tenants, $this->sessions, $this->refreshTokens, $this->auditLog, $rotator, $tokens),
            $revoke,
            $responses);
        $admin = new AdminUserController($this->registerUserUseCase($hasher, $config), $revoke, $this->users, $responses);
        $tenantAdmin = new AdminTenantController(new RegisterTenantUseCase($this->tenants, $this->auditLog), $this->tenants, $responses);
        $router = new Router($responses);
        $router->pipe(new RateLimitMiddleware($this->rateLimitHits, $responses));
        $router->pipe(new TenantMiddleware($responses));
        $router->pipe(new JwtAuthMiddleware($tokens, $this->sessions, $this->auditLog, $responses));
        $router->add('GET', '/health', fn() => $responses->json(['status' => 'ok', 'service' => 'tenants-auth']));
        $router->add('POST', '/auth/login', [$auth, 'login']);
        $router->add('POST', '/auth/mfa', [$auth, 'verifyMfa']);
        $router->add('POST', '/auth/refresh', [$auth, 'refresh']);
        $router->add('POST', '/auth/logout', [$auth, 'logout']);
        $router->add('GET', '/auth/me', [$auth, 'me']);
        $router->add('GET', '/admin/users', [$admin, 'list']);
        $router->add('POST', '/admin/users', [$admin, 'create']);
        $router->add('DELETE', '/admin/users/{userId}/sessions', [$admin, 'revokeSessions']);
        $router->add('GET', '/admin/tenants', [$tenantAdmin, 'list']);
        $router->add('POST', '/admin/tenants', [$tenantAdmin, 'create']);
        $router->add('GET', '/admin/tenants/{id}', [$tenantAdmin, 'get']);
        return $router;}
    /** Siembra un hospital con su primer administrador (CLI y pruebas). El alta normal pasa por /admin/users. */
    public function seedHospital(array $config, string $hospitalName, string $email, string $password, array $roles = ['admin_hospital']): RegisterUserResult
    {
        $argon = $config['argon2'] ?? ['memory' => 65536, 'time' => 4, 'threads' => 1];
        $tenant = (new RegisterTenantUseCase($this->tenants, $this->auditLog))->execute(new RegisterTenantCommand($hospitalName));
        return $this->registerUserUseCase(new Argon2idHasher($argon['memory'], $argon['time'], $argon['threads']), $config)->execute(
            new RegisterUserCommand(new Email($email), 'Administrador ' . $hospitalName, $password, $roles, $tenant->tenantId(), UserId::generate()));}
    private function registerUserUseCase(Argon2idHasher $hasher, array $config): RegisterUserUseCase
    {
        $checker = ($config['check_hibp'] ?? false) ? new HaveIBeenPwnedClient(new Client()) : null;
        return new RegisterUserUseCase($this->users, $this->tenants, new PasswordPolicy($hasher, $checker), new MfaPolicy(), new TotpVerifier(), $this->auditLog);}}
