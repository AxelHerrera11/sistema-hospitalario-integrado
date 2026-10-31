<?php

declare(strict_types=1);

/**
 * Valida el contrato de seguridad de las rutas API de Laravel sin modificarlas.
 *
 *   php artisan route:list --json --path=api > qa-evidence/contract/routes.json
 *   php micro-his/qa-calidad-versiones/bin/route-guard.php qa-evidence/contract/routes.json \
 *       config/rbac.php qa-evidence/contract/route-guard.xml
 */
use MicroHis\QaCalidad\Ci\JUnitWriter;
use MicroHis\QaCalidad\Ci\RouteGuard;

require __DIR__.'/../bootstrap.php';

[$script, $routesFile, $rbacFile, $junit] = $argv + [null, '', '', ''];
$routes = json_decode((string) @file_get_contents($routesFile), true);
// config/rbac.php es un arreglo PHP plano (sin helpers de Laravel), se puede incluir directo.
$rbac = is_file($rbacFile) ? require $rbacFile : null;
$allow = json_decode((string) file_get_contents(dirname(__DIR__).'/config/route-guard.json'), true);
if (! is_array($routes) || ! is_array($rbac['catalog'] ?? null) || ! is_array($allow) || $junit === '') {
    fwrite(STDERR, "Uso: route-guard.php <routes.json> <config/rbac.php> <salida.xml>\n");
    exit(3);
}

$writer = new JUnitWriter('route-guard');
foreach ((new RouteGuard($rbac['catalog'], $allow))->check($routes) as $row) {
    $writer->add($row['route'], $row['ok'] ? 'passed' : 'failed', 0.0, $row['message']);
    echo ($row['ok'] ? 'OK    ' : 'FALLO ').str_pad($row['route'], 60).' '.$row['message'].PHP_EOL;
}
$writer->write($junit);
exit($writer->failures() === 0 ? 0 : 1);
