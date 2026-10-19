<?php
declare(strict_types=1);
namespace App\Modules\Tenants;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\SessionRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\TenantRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Tenants\Domain\Services\Argon2idHasher;
use App\Modules\Tenants\Domain\Services\BlocklistService;
use App\Modules\Tenants\Domain\Services\DeviceTrustService;
use App\Modules\Tenants\Domain\Services\MfaPolicy;
use App\Modules\Tenants\Domain\Services\PasswordPolicy;
use App\Modules\Tenants\Domain\Services\RefreshTokenRotator;
use App\Modules\Tenants\Domain\Services\TokenFactory;
use App\Modules\Tenants\Domain\Services\TotpVerifier;
use App\Modules\Tenants\Domain\Services\WebAuthnVerifier;
use App\Modules\Tenants\Infrastructure\Audit\EventPublisher;
use App\Modules\Tenants\Infrastructure\Audit\PdoAuditLogRepository;
use App\Modules\Tenants\Infrastructure\External\EmailNotificationService;
use App\Modules\Tenants\Infrastructure\External\HaveIBeenPwnedClient;
use App\Modules\Tenants\Infrastructure\External\SiemForwarder;
use App\Modules\Tenants\Infrastructure\Persistence\PDO\PDOUserRepository;
use App\Modules\Tenants\Infrastructure\Persistence\PDO\PDOTenantRepository;
use App\Modules\Tenants\Infrastructure\Persistence\PDO\PDOSessionRepository;
use App\Modules\Tenants\Infrastructure\Persistence\Redis\RedisBlocklistRepository;
use App\Modules\Tenants\Infrastructure\Persistence\Redis\RedisLockoutRepository;
use App\Modules\Tenants\Infrastructure\Persistence\Redis\RedisRateLimitRepository;
use App\Modules\Tenants\Infrastructure\Persistence\Redis\RedisRefreshTokenRepository;
use App\Modules\Tenants\Infrastructure\Persistence\Redis\RedisSessionRepository;
use App\Modules\Tenants\Infrastructure\Security\JwtTokenService;
use App\Modules\Tenants\Infrastructure\Security\TotpVerifier as InfrastructureTotpVerifier;
use App\Modules\Tenants\Infrastructure\Security\WebAuthnVerifier as InfrastructureWebAuthnVerifier;
use App\Modules\Tenants\Presentation\Controllers\AuthController;
use App\Modules\Tenants\Presentation\Transformers\AuthResponseTransformer;
use Doctrine\DBAL\DriverManager;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriber;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProducer;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Redis;
use Symfony\Component\Mailer\MailerInterface;
final class ModuleBootstrap
{
    public static function register(ContainerInterface $container): void
    {
        $container->set(PDO::class . '.central', function () {
            $params = [
                'driver' => 'pdo_pgsql',
                'host' => $_ENV['CENTRAL_DB_HOST'] ?? '127.0.0.1',
                'port' => $_ENV['CENTRAL_DB_PORT'] ?? '5432',
                'dbname' => $_ENV['CENTRAL_DB_DATABASE'] ?? 'his_central',
                'user' => $_ENV['CENTRAL_DB_USERNAME'] ?? 'postgres',
                'password' => $_ENV['CENTRAL_DB_PASSWORD'] ?? '',
                'charset' => 'utf8',
            ];
            return DriverManager::getConnection($params)->getWrappedConnection();
        });
        $container->set(PDO::class . '.hospital', function () {
            $params = [
                'driver' => 'pdo_pgsql',
                'host' => $_ENV['HOSPITAL_DB_HOST'] ?? '127.0.0.1',
                'port' => $_ENV['HOSPITAL_DB_PORT'] ?? '5432',
                'dbname' => $_ENV['HOSPITAL_DB_DATABASE'] ?? 'his_hospital',
                'user' => $_ENV['HOSPITAL_DB_USERNAME'] ?? 'postgres',
                'password' => $_ENV['HOSPITAL_DB_PASSWORD'] ?? '',
                'charset' => 'utf8',
            ];
            return DriverManager::getConnection($params)->getWrappedConnection();
        });
        $container->set(Redis::class, function () {
            $redis = new Redis();
            $redis->connect(
                $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                (int)($_ENV['REDIS_PORT'] ?? 6379)
            );
            if (!empty($_ENV['REDIS_PASSWORD'])) {
                $redis->auth($_ENV['REDIS_PASSWORD']);
            }
            return $redis;
        });
        $container->set(Argon2idHasher::class, function () {
            return new Argon2idHasher();
        });
        $container->set(PasswordPolicy::class, function (ContainerInterface $c) {
            return new PasswordPolicy(
                $c->get(Argon2idHasher::class),
                $c->get(HaveIBeenPwnedClient::class),
                (int)($_ENV['PASSWORD_MIN_LENGTH'] ?? 12),
                (bool)($_ENV['PASSWORD_REQUIRE_COMPLEXITY'] ?? true),
                (bool)($_ENV['PASSWORD_CHECK_HIBP'] ?? true),
                (int)($_ENV['PASSWORD_HISTORY_SIZE'] ?? 5)
            );
        });
        $container->set(MfaPolicy::class, function () {
            return new MfaPolicy();
        });
        $container->set(TokenFactory::class, function (ContainerInterface $c) {
            return new JwtTokenService(
                $_ENV['JWT_PRIVATE_KEY'] ?? '',
                $_ENV['JWT_PUBLIC_KEY'] ?? '',
                $_ENV['JWT_ISSUER'] ?? 'his-tenants-auth',
                $_ENV['JWT_AUDIENCE'] ?? 'his-api',
                (int)($_ENV['JWT_ACCESS_TTL'] ?? 900),
                $_ENV['JWT_KEY_ID'] ?? 'key-1'
            );
        });
        $container->set(RefreshTokenRotator::class, function (ContainerInterface $c) {
            return new RefreshTokenRotator(
                $c->get(RefreshTokenRepositoryInterface::class),
                $c->get(Argon2idHasher::class),
                $c->get(TokenFactory::class),
                (int)($_ENV['JWT_REFRESH_TTL'] ?? 604800)
            );
        });
        $container->set(DeviceTrustService::class, function (ContainerInterface $c) {
            return new DeviceTrustService($c->get(SessionRepositoryInterface::class));
        });
        $container->set(BlocklistService::class, function (ContainerInterface $c) {
            return new RedisBlocklistRepository($c->get(Redis::class));
        });
        $container->set(TotpVerifier::class, function () {
            return new InfrastructureTotpVerifier();
        });
        $container->set(WebAuthnVerifier::class, function (ContainerInterface $c) {
            return new InfrastructureWebAuthnVerifier(
                null,
                null
            );
        });
        $container->set(UserRepositoryInterface::class, function (ContainerInterface $c) {
            return new PDOUserRepository($c->get(PDO::class . '.central'));
        });
        $container->set(TenantRepositoryInterface::class, function (ContainerInterface $c) {
            return new PDOTenantRepository($c->get(PDO::class . '.central'));
        });
        $container->set(SessionRepositoryInterface::class, function (ContainerInterface $c) {
            return new RedisSessionRepository($c->get(Redis::class));
        });
        $container->set(RefreshTokenRepositoryInterface::class, function (ContainerInterface $c) {
            return new RedisRefreshTokenRepository($c->get(Redis::class));
        });
        $container->set(AuditLogRepositoryInterface::class, function (ContainerInterface $c) {
            return new PdoAuditLogRepository($c->get(PDO::class . '.central'));
        });
        $container->set(HaveIBeenPwnedClient::class, function (ContainerInterface $c) {
            $client = new \GuzzleHttp\Client();
            return new HaveIBeenPwnedClient($client, $_ENV['HIBP_API_KEY'] ?? '');
        });
        $container->set(EmailNotificationService::class, function (ContainerInterface $c) {
            return new EmailNotificationService(
                $c->get(MailerInterface::class),
                $_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@hospital.com',
                $_ENV['MAIL_FROM_NAME'] ?? 'Sistema Hospitalario'
            );
        });
        $container->set(SiemForwarder::class, function (ContainerInterface $c) {
            return new SiemForwarder(
                $c->get(LoggerInterface::class),
                $_ENV['SIEM_ENDPOINT'] ?? null,
                $_ENV['SIEM_ENDPOINT'] ? new \GuzzleHttp\Client() : null
            );
        });
        $container->set(EventPublisher::class, function (ContainerInterface $c) {
            return new EventPublisher(
                $c->get(ProducerInterface::class),
                $c->get(PsrContext::class),
                $c->get(LoggerInterface::class)
            );
        });
        $container->set(\App\Modules\Tenants\Application\UseCases\RegisterUserUseCase::class, function (ContainerInterface $c) {
            return new \App\Modules\Tenants\Application\UseCases\RegisterUserUseCase(
                $c->get(UserRepositoryInterface::class),
                $c->get(TenantRepositoryInterface::class),
                $c->get(PasswordPolicy::class),
                $c->get(MfaPolicy::class),
                $c->get(AuditLogRepositoryInterface::class),
                $c->get(EventPublisher::class),
                $c->get(EmailNotificationService::class)
            );
        });
        $container->set(\App\Modules\Tenants\Application\UseCases\AuthenticateUserUseCase::class, function (ContainerInterface $c) {
            return new \App\Modules\Tenants\Application\UseCases\AuthenticateUserUseCase(
                $c->get(UserRepositoryInterface::class),
                $c->get(TenantRepositoryInterface::class),
                $c->get(RefreshTokenRepositoryInterface::class),
                $c->get(SessionRepositoryInterface::class),
                $c->get(AuditLogRepositoryInterface::class),
                $c->get(EventPublisher::class),
                $c->get(Argon2idHasher::class),
                $c->get(MfaPolicy::class),
                $c->get(TokenFactory::class),
                $c->get(RefreshTokenRotator::class),
                $c->get(DeviceTrustService::class),
                $c->get(TotpVerifier::class),
                $c->get(WebAuthnVerifier::class),
                $c->get(BlocklistService::class)
            );
        });
        $container->set(\App\Modules\Tenants\Application\UseCases\RefreshTokenUseCase::class, function (ContainerInterface $c) {
            return new \App\Modules\Tenants\Application\UseCases\RefreshTokenUseCase(
                $c->get(UserRepositoryInterface::class),
                $c->get(RefreshTokenRepositoryInterface::class),
                $c->get(SessionRepositoryInterface::class),
                $c->get(AuditLogRepositoryInterface::class),
                $c->get(EventPublisher::class),
                $c->get(RefreshTokenRotator::class),
                $c->get(TokenFactory::class)
            );
        });
        $container->set(\App\Modules\Tenants\Application\UseCases\RevokeSessionUseCase::class, function (ContainerInterface $c) {
            return new \App\Modules\Tenants\Application\UseCases\RevokeSessionUseCase(
                $c->get(UserRepositoryInterface::class),
                $c->get(SessionRepositoryInterface::class),
                $c->get(RefreshTokenRepositoryInterface::class),
                $c->get(BlocklistService::class),
                $c->get(AuditLogRepositoryInterface::class),
                $c->get(EventPublisher::class)
            );
        });
        $container->set(AuthResponseTransformer::class, function (ContainerInterface $c) {
            return new AuthResponseTransformer($c->get(ResponseFactoryInterface::class));
        });
        $container->set(AuthController::class, function (ContainerInterface $c) {
            return new AuthController(
                $c->get(\App\Modules\Tenants\Application\UseCases\RegisterUserUseCase::class),
                $c->get(\App\Modules\Tenants\Application\UseCases\AuthenticateUserUseCase::class),
                $c->get(\App\Modules\Tenants\Application\UseCases\RefreshTokenUseCase::class),
                $c->get(\App\Modules\Tenants\Application\UseCases\RevokeSessionUseCase::class),
                $c->get(AuthResponseTransformer::class)
            );
        });
    }
}
