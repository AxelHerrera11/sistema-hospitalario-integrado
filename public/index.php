<?php

declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
use App\Modules\Tenants\ModuleBootstrap;
use App\Modules\Tenants\Presentation\Router;
use App\Modules\Tenants\Presentation\Middleware\TenantMiddleware;
use App\Modules\Tenants\Presentation\Middleware\JwtAuthMiddleware;
use App\Modules\Tenants\Presentation\Middleware\RateLimitMiddleware;
use App\Modules\Tenants\Domain\Services\JwtTokenService;
use App\Modules\Tenants\Domain\Services\BlocklistService;
use App\Modules\Tenants\Infrastructure\Persistence\Redis\RedisRateLimitRepository;
use App\Modules\Tenants\Infrastructure\Persistence\Redis\RedisBlocklistRepository;
use DI\ContainerBuilder;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Redis;

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    'config' => [
        'jwt' => [
            'private_key' => $_ENV['JWT_PRIVATE_KEY'] ?? '',
            'public_key' => $_ENV['JWT_PUBLIC_KEY'] ?? '',
            'issuer' => $_ENV['JWT_ISSUER'] ?? 'his-tenants-auth',
            'audience' => $_ENV['JWT_AUDIENCE'] ?? 'his-api',
            'access_ttl' => (int)($_ENV['JWT_ACCESS_TTL'] ?? 900),
            'refresh_ttl' => (int)($_ENV['JWT_REFRESH_TTL'] ?? 604800),
            'key_id' => $_ENV['JWT_KEY_ID'] ?? 'key-1',
        ],
        'redis' => [
            'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
            'password' => $_ENV['REDIS_PASSWORD'] ?? '',
        ],
        'database' => [
            'central' => [
                'host' => $_ENV['CENTRAL_DB_HOST'] ?? '127.0.0.1',
                'port' => (int)($_ENV['CENTRAL_DB_PORT'] ?? 5432),
                'dbname' => $_ENV['CENTRAL_DB_DATABASE'] ?? 'his_central',
                'user' => $_ENV['CENTRAL_DB_USERNAME'] ?? 'postgres',
                'password' => $_ENV['CENTRAL_DB_PASSWORD'] ?? '',
            ],
            'hospital' => [
                'host' => $_ENV['HOSPITAL_DB_HOST'] ?? '127.0.0.1',
                'port' => (int)($_ENV['HOSPITAL_DB_PORT'] ?? 5432),
                'dbname' => $_ENV['HOSPITAL_DB_DATABASE'] ?? 'his_hospital',
                'user' => $_ENV['HOSPITAL_DB_USERNAME'] ?? 'postgres',
                'password' => $_ENV['HOSPITAL_DB_PASSWORD'] ?? '',
            ],
        ],
        'password' => [
            'min_length' => (int)($_ENV['PASSWORD_MIN_LENGTH'] ?? 12),
            'require_complexity' => (bool)($_ENV['PASSWORD_REQUIRE_COMPLEXITY'] ?? true),
            'check_hibp' => (bool)($_ENV['PASSWORD_CHECK_HIBP'] ?? true),
            'history_size' => (int)($_ENV['PASSWORD_HISTORY_SIZE'] ?? 5),
        ],
    ],
    Redis::class => function () {
        $redis = new Redis();
        $redis->connect(
            $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            (int)($_ENV['REDIS_PORT'] ?? 6379)
        );
        if (!empty($_ENV['REDIS_PASSWORD'])) {
            $redis->auth($_ENV['REDIS_PASSWORD']);
        }
        return $redis;
    },
    'pdo.central' => function () {
        $params = [
            'driver' => 'pdo_pgsql',
            'host' => $_ENV['CENTRAL_DB_HOST'] ?? '127.0.0.1',
            'port' => (int)($_ENV['CENTRAL_DB_PORT'] ?? 5432),
            'dbname' => $_ENV['CENTRAL_DB_DATABASE'] ?? 'his_central',
            'user' => $_ENV['CENTRAL_DB_USERNAME'] ?? 'postgres',
            'password' => $_ENV['CENTRAL_DB_PASSWORD'] ?? '',
            'charset' => 'utf8',
        ];
        return \Doctrine\DBAL\DriverManager::getConnection($params)->getWrappedConnection();
    },
    'pdo.hospital' => function () {
        $params = [
            'driver' => 'pdo_pgsql',
            'host' => $_ENV['HOSPITAL_DB_HOST'] ?? '127.0.0.1',
            'port' => (int)($_ENV['HOSPITAL_DB_PORT'] ?? 5432),
            'dbname' => $_ENV['HOSPITAL_DB_DATABASE'] ?? 'his_hospital',
            'user' => $_ENV['HOSPITAL_DB_USERNAME'] ?? 'postgres',
            'password' => $_ENV['HOSPITAL_DB_PASSWORD'] ?? '',
            'charset' => 'utf8',
        ];
        return \Doctrine\DBAL\DriverManager::getConnection($params)->getWrappedConnection();
    },
]);
$container = $containerBuilder->build();
ModuleBootstrap::register($container);
$container->set(RedisRateLimitRepository::class, function () use ($container) {
    return new RedisRateLimitRepository($container->get(Redis::class));
});
$container->set(RedisBlocklistRepository::class, function () use ($container) {
    return new RedisBlocklistRepository($container->get(Redis::class));
});
$container->set(BlocklistService::class, function () use ($container) {
    return $container->get(RedisBlocklistRepository::class);
});
$router = new \App\Modules\Tenants\Presentation\Router();
$router->addMiddleware(new TenantMiddleware());
$router->addMiddleware(new RateLimitMiddleware($container->get(RedisRateLimitRepository::class)));
$router->addMiddleware(new JwtAuthMiddleware(
    $container->get(JwtTokenService::class),
    $container->get(BlocklistService::class)
));
$requestFactory = new Psr17Factory();
$request = $requestFactory->createServerRequestFromGlobals();
try {
    $response = $router->dispatch($request);
} catch (\Throwable $e) {
    $responseFactory = new Psr17Factory();
    $response = $responseFactory->createResponse(500);
    $response->getBody()->write(json_encode([
        'success' => false,
        'error' => [
            'message' => 'Error interno del servidor',
            'code' => 500,
            'debug' => $_ENV['APP_DEBUG'] ?? false ? $e->getMessage() : null
        ]
    ]));
    $response = $response->withHeader('Content-Type', 'application/json');
}
$emitter = new \Http\Client\Common\Plugin\Emitter\SapiEmitter();
$emitter->emit($response);
