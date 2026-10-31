<?php
/**
 * Micro-benchmark del micro-HIS de Tenants (Semana 15). Mide latencia EN PROCESO: peticiones
 * PSR-7 al router, sin red, sin servidor web y sin base de datos. No es una prueba de carga
 * ni sustituye a k6; sirve para comparar el costo relativo de cada operación.
 *
 *   php docs/tenants/semana-15/bench/micro-his-bench.php [iteraciones=200]
 *
 * Argon2id usa los parámetros reales del módulo (64 MiB, 4 pasadas, 1 hilo), así que el login
 * refleja el costo de verificar la contraseña.
 */
declare(strict_types=1);
require __DIR__ . '/../../../../vendor/autoload.php';

use App\Modules\Tenants\ModuleBootstrap;
use GuzzleHttp\Psr7\ServerRequest;

$iterations = max(10, (int) ($argv[1] ?? 200));
$config = ['jwt_secret' => bin2hex(random_bytes(32))];
$module = ModuleBootstrap::create();
$router = $module->router($config);
$admin = $module->seedHospital($config, 'Hospital Bench', 'admin@bench.test', 'Clave-Segura-2026!');
$tenant = $admin->tenantId()->value();

$call = function (string $method, string $path, array $body = [], ?string $token = null) use ($router, $tenant): array {
    $headers = ['Content-Type' => 'application/json', 'X-Tenant-ID' => $tenant, 'X-Device-ID' => 'bench']
        + ($token ? ['Authorization' => "Bearer {$token}"] : []);
    $request = (new ServerRequest($method, $path, $headers, $body ? json_encode($body) : null, '1.1', ['REMOTE_ADDR' => '127.0.0.' . random_int(1, 250)]))->withParsedBody([]);
    $response = $router->dispatch($request);

    return [$response->getStatusCode(), json_decode((string) $response->getBody(), true)];
};

// Médico sin MFA para medir login → me → refresh → logout.
$challenge = $call('POST', '/auth/login', ['email' => 'admin@bench.test', 'password' => 'Clave-Segura-2026!'])[1];
$code = (new App\Modules\Tenants\Infrastructure\Security\TotpVerifier())->codeAt($admin->totpSecret(), intdiv(time(), 30));
$adminToken = $call('POST', '/auth/mfa', ['challenge_id' => $challenge['challenge_id'], 'code' => $code])[1]['access_token'];
$call('POST', '/admin/users', ['email' => 'medico@bench.test', 'name' => 'Medico', 'password' => 'Clave-Segura-2026!', 'roles' => ['medico']], $adminToken);

$samples = ['login' => [], 'me' => [], 'refresh' => [], 'logout' => []];
for ($i = 0; $i < $iterations; $i++) {
    $t = hrtime(true);
    [$status, $login] = $call('POST', '/auth/login', ['email' => 'medico@bench.test', 'password' => 'Clave-Segura-2026!']);
    $samples['login'][] = (hrtime(true) - $t) / 1e6;
    if ($status !== 200) {
        fwrite(STDERR, "login respondió {$status}\n");
        exit(1);
    }
    foreach ([['me', 'GET', '/auth/me', [], $login['access_token']], ['refresh', 'POST', '/auth/refresh', ['refresh_token' => $login['refresh_token']], null], ['logout', 'POST', '/auth/logout', [], $login['access_token']]] as [$name, $method, $path, $body, $token]) {
        $t = hrtime(true);
        [$status] = $call($method, $path, $body, $token);
        $samples[$name][] = (hrtime(true) - $t) / 1e6;
        if ($status !== 200) {
            fwrite(STDERR, "{$name} respondió {$status}\n");
            exit(1);
        }
    }
}

$percentile = function (array $values, float $p): float {
    sort($values);

    return $values[(int) ceil($p * count($values)) - 1];
};
printf("PHP %s · %d iteraciones · %s\n", PHP_VERSION, $iterations, php_uname('s') . ' ' . php_uname('m'));
printf("%-8s %9s %9s %9s %9s\n", 'op', 'p50 ms', 'p95 ms', 'p99 ms', 'máx ms');
foreach ($samples as $name => $values) {
    printf("%-8s %9.2f %9.2f %9.2f %9.2f\n", $name, $percentile($values, .50), $percentile($values, .95), $percentile($values, .99), max($values));
}
