<?php
/**
 * Front controller del micro-HIS de Tenants (PHP vanilla, Semana 3). No sustituye a public/index.php.
 *
 *   export MICRO_HIS_JWT_SECRET="$(php -r 'echo bin2hex(random_bytes(32));')"
 *   php app/Modules/Tenants/micro-his.php seed "Hospital Demo" admin@demo.test 'Clave-Segura-2026!'
 *   php -S 127.0.0.1:8081 app/Modules/Tenants/micro-his.php
 *
 * El estado (hospitales, membresías, sesiones, bitácora) se guarda en MICRO_HIS_STATE
 * o, por defecto, en el directorio temporal del sistema.
 */
declare(strict_types=1);
require __DIR__ . '/../../../vendor/autoload.php';
use App\Modules\Tenants\ModuleBootstrap;
use GuzzleHttp\Psr7\ServerRequest;
$statePath = getenv('MICRO_HIS_STATE') ?: sys_get_temp_dir() . '/micro-his-tenants.state';
$config = ['jwt_secret' => (string) getenv('MICRO_HIS_JWT_SECRET'), 'check_hibp' => getenv('MICRO_HIS_CHECK_HIBP') === '1'];
if (strlen($config['jwt_secret']) < 32) {
    fwrite(STDERR, "Defina MICRO_HIS_JWT_SECRET con al menos 32 bytes.\n");
    exit(1);}
$module = ModuleBootstrap::fromFile($statePath);
if (PHP_SAPI === 'cli') {
    [, $command, $hospital, $email, $password] = $argv + [null, null, null, null, null];
    if ($command !== 'seed' || $password === null) {
        fwrite(STDERR, "Uso: php micro-his.php seed <hospital> <email> <contraseña>\n");
        exit(1);}
    $result = $module->seedHospital($config, $hospital, $email, $password);
    $module->saveTo($statePath);
    echo json_encode(['tenant_id' => $result->tenantId()->value(), 'user_id' => $result->userId()->value(), 'totp_secret' => $result->totpSecret()], JSON_PRETTY_PRINT), "\n";
    exit(0);}
$response = $module->router($config)->dispatch(ServerRequest::fromGlobals());
$module->saveTo($statePath);
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    header($name . ': ' . implode(', ', $values));}
echo $response->getBody();
