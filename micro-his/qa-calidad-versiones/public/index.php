<?php

declare(strict_types=1);

/**
 * SERVIDOR DE DEMOSTRACIÓN Y DE PRUEBAS E2E. No es la integración real con Laravel.
 *
 *   php -S 127.0.0.1:8093 micro-his/qa-calidad-versiones/public/index.php
 *   -> http://127.0.0.1:8093/prototipo/
 *
 * Diferencias con producción, todas deliberadas y visibles:
 * - La identidad sale de la cabecera X-Demo-Session (lider | observador | lider_h2)
 *   en lugar de JWT + TenantMiddleware.
 * - Los permisos qa.* (PROPUESTOS, no están en config/rbac.php) los decide StaticAccessGate.
 * - Los datos son ficticios y viven en un SQLite temporal (QA_DEMO_DB) que
 *   POST /demo/reset borra. Nunca apunta a una base del HIS.
 * - ?demo_falla=almacen|rbac simula la caída del almacén o del RBAC.
 */
use MicroHis\QaCalidad\Application\QaPermissions;
use MicroHis\QaCalidad\Ci\ModuleFactory;

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (! str_starts_with($path, '/api/') && ! str_starts_with($path, '/demo/')) {
    if ($path === '/' || $path === '/index.php') {
        header('Location: /prototipo/');

        return true;
    }
    $file = realpath(__DIR__.$path.(str_ends_with($path, '/') ? 'index.html' : ''));
    if ($file !== false && is_file($file) && str_starts_with(str_replace('\\', '/', $file), str_replace('\\', '/', __DIR__).'/prototipo/')) {
        return false; // archivo estático del prototipo (el servidor embebido resuelve index.html)
    }
    http_response_code(404);

    return true;
}

require __DIR__.'/../bootstrap.php';
require __DIR__.'/../tests/doubles.php';

$db = getenv('QA_DEMO_DB') ?: sys_get_temp_dir().'/qa-calidad-demo.sqlite';
header('Content-Type: application/json; charset=utf-8');

if ($path === '/demo/reset' && $method === 'POST') {
    is_file($db) && unlink($db);
    echo json_encode(['reset' => true]);

    return true;
}

$failure = is_string($_GET['demo_falla'] ?? null) ? $_GET['demo_falla'] : '';
$all = [QaPermissions::VIEW, QaPermissions::REGISTER, QaPermissions::EVALUATE];
$grants = [LIDER.'@'.H1 => $all, OBS.'@'.H1 => [QaPermissions::VIEW], LIDER.'@'.H2 => $all];
$sessions = ['lider' => [LIDER, H1], 'observador' => [OBS, H1], 'lider_h2' => [LIDER, H2]];
[$user, $tenant] = $sessions[$_SERVER['HTTP_X_DEMO_SESSION'] ?? ''] ?? [null, null];

try {
    $repo = $failure === 'almacen' ? new BrokenRepository : ModuleFactory::repository($db);
    $module = ModuleFactory::build($repo, $grants, ModuleFactory::policy(__DIR__.'/../config/quality-gate.json'), $failure === 'rbac');
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['code' => 'QA_STORAGE_UNAVAILABLE', 'trace_id' => 'demo-boot']);

    return true;
}

$query = $_GET;
unset($query['demo_falla']);
$result = $module->http->handle(
    $method,
    $path,
    ['user_id' => $user, 'tenant_id' => $tenant],
    ['X-Tenant-ID' => $_SERVER['HTTP_X_TENANT_ID'] ?? $tenant, 'X-Request-ID' => $_SERVER['HTTP_X_REQUEST_ID'] ?? null],
    $query,
    (string) file_get_contents('php://input'),
);
http_response_code($result['status']);
foreach ($result['headers'] as $name => $value) {
    header("{$name}: {$value}");
}
echo json_encode($result['body'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

return true;
