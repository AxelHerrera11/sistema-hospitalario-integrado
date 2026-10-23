<?php

declare(strict_types=1);

/** Integración con CI: JUnit, evidencia, guardia de rutas y CLI del gate (RF-04, RF-05, RNF-05). */
require __DIR__.'/harness.php';

use MicroHis\QaCalidad\Ci\EvidenceCollector;
use MicroHis\QaCalidad\Ci\JUnitReportReader;
use MicroHis\QaCalidad\Ci\JUnitWriter;
use MicroHis\QaCalidad\Ci\RouteGuard;

$fx = __DIR__.'/fixtures';
$reader = new JUnitReportReader;

section('Lectura de JUnit');
check('CI01', 'PHPUnit real (--log-junit): 5 casos, 0 fallos', $reader->read("{$fx}/phpunit-unit.xml")['total'] === 5 && $reader->read("{$fx}/phpunit-unit.xml")['failed'] === 0);
$node = $reader->read("{$fx}/node-mixed.xml");
check('CI02', 'node --test real: cuenta casos anidados, 1 fallo y 1 omitida', $node['total'] === 4 && $node['failed'] === 1 && $node['skipped'] === 1);
check('CI03', 'Archivo inexistente → RuntimeException', thrown(fn () => $reader->read("{$fx}/no-existe.xml")) instanceof RuntimeException);
$tmp = sys_get_temp_dir().'/qa-ci-'.bin2hex(random_bytes(4));
mkdir("{$tmp}/junit", 0775, true);
register_shutdown_function(static function () use ($tmp): void {
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($tmp);
});
file_put_contents("{$tmp}/junit/html.xml", '<html><body>502 Bad Gateway</body></html>');
check('CI04', 'XML que no es JUnit (p. ej. página de error) → RuntimeException', thrown(fn () => $reader->read("{$tmp}/junit/html.xml")) instanceof RuntimeException);
$xxe = thrown(fn () => $reader->read("{$fx}/xxe.xml"));
check('CI05', 'Reporte con entidad externa (XXE) → rechazado sin leer el archivo referido', $xxe instanceof RuntimeException);

section('Evidencia');
$writer = new JUnitWriter('demo');
$writer->add('caso <ok> & "comillas"', 'passed', 0.5);
$writer->add('caso roto', 'failed', 0.1, 'exit 1');
$writer->write("{$tmp}/junit/escrito.xml");
$back = $reader->read("{$tmp}/junit/escrito.xml");
check('CI06', 'JUnitWriter produce XML que el lector reconoce (y escapa caracteres)', $back['total'] === 2 && $back['failed'] === 1 && $back['duration_ms'] === 600);
$collector = new EvidenceCollector($tmp);
$ref = $collector->collect('junit', 'junit/escrito.xml');
check('CI07', 'collect() calcula SHA-256 y tamaño reales', $ref->sha256 === hash_file('sha256', "{$tmp}/junit/escrito.xml") && $ref->bytes === filesize("{$tmp}/junit/escrito.xml"));
file_put_contents("{$tmp}/fuera.txt", 'fuera del paquete');
$inner = new EvidenceCollector("{$tmp}/junit");
check('CI08', 'Archivo existente pero fuera de la raíz (../fuera.txt) → null', $inner->resolve('../fuera.txt') === null && $inner->resolve('escrito.xml') !== null);
check('CI09', 'collect() fuera de la raíz lanza en vez de registrar', thrown(fn () => $inner->collect('log', '../fuera.txt')) instanceof RuntimeException);

section('Guardia de rutas (contrato RBAC)');
$guard = new RouteGuard(['usuarios.ver'], ['public' => ['POST api/v1/auth/login'], 'authenticated_only' => ['GET api/v1/auth/me']]);
$route = static fn (string $m, string $u, array $mw): array => ['method' => $m, 'uri' => $u, 'middleware' => $mw];
$rows = $guard->check([
    $route('POST', 'api/v1/auth/login', ['api', 'tenant']),
    $route('GET|HEAD', 'api/v1/auth/me', ['api', 'tenant', 'App\\Http\\Middleware\\JwtAuth']),
    $route('GET|HEAD', 'api/v1/admin/users', ['api', 'tenant', 'App\\Http\\Middleware\\JwtAuth', 'App\\Rbac\\RequirePermission:usuarios.ver']),
    $route('GET|HEAD', 'api/v1/abierta', ['api']),
    $route('GET|HEAD', 'api/v1/sin-jwt', ['api', 'tenant']),
    $route('GET|HEAD', 'api/v1/sin-rbac', ['api', 'tenant', 'App\\Http\\Middleware\\JwtAuth']),
    $route('POST', 'api/v1/inventado', ['api', 'tenant', 'App\\Http\\Middleware\\JwtAuth', 'App\\Rbac\\RequirePermission:usuarios.crear']),
]);
$ok = array_column($rows, 'ok');
check('CI10', 'Pública y solo-autenticada por lista explícita → OK', $ok[0] && $ok[1]);
check('CI11', 'tenant + JWT + permiso del catálogo → OK (GET|HEAD normalizado)', $ok[2] && $rows[2]['route'] === 'GET api/v1/admin/users');
check('CI12', 'Sin tenant / sin JWT / sin RBAC → FALLO cada una', ! $ok[3] && ! $ok[4] && ! $ok[5]);
check('CI13', 'Permiso que no está en el catálogo (usuarios.crear) → FALLO', ! $ok[6] && str_contains($rows[6]['message'], 'usuarios.crear'));

section('CLI del quality gate (códigos de salida)');
$run = static function (array $args): array {
    $cmd = array_merge([PHP_BINARY, __DIR__.'/../bin/quality-gate.php'], $args);
    $p = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, ['GITHUB_STEP_SUMMARY' => ''] + getenv());
    $out = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);

    return [proc_close($p), $out];
};
$manifest = static function (string $dir, array $suites): string {
    file_put_contents("{$dir}/manifest.json", json_encode(['tenant_id' => H1, 'ci_actor' => 'ci-bot', 'suites' => $suites]));

    return "{$dir}/manifest.json";
};
copy("{$fx}/phpunit-unit.xml", "{$tmp}/junit/unit.xml");
copy("{$fx}/node-mixed.xml", "{$tmp}/junit/e2e-rojo.xml");
$base = ['--evidence-root='.$tmp, '--out-dir='.$tmp.'/gate', '--sha='.SHA, '--release=1.4.0-rc.1'];
$green = $manifest($tmp, [['name' => 'unit', 'kind' => 'unit', 'junit' => 'junit/unit.xml'], ['name' => 'e2e', 'kind' => 'e2e', 'junit' => 'junit/escrito.xml', 'required' => false], ['name' => 'e2e-b', 'kind' => 'e2e', 'junit' => 'junit/unit.xml']]);
[$code, $out] = $run([...$base, "--manifest={$green}", '--db='.$tmp.'/gate/qa-runs.sqlite']);
check('CI14', 'Obligatorias verdes → exit 0 y resumen APROBADO', $code === 0 && str_contains($out, 'APROBADO'));
$saved = json_decode((string) file_get_contents("{$tmp}/gate/gate-decision.json"), true);
check('CI15', 'gate-decision.json guarda veredicto y huellas de evidencia', ($saved['run']['gate']['verdict'] ?? '') === 'approved' && strlen($saved['run']['suites'][0]['result']['evidence'][0]['sha256'] ?? '') === 64);
check('CI16', 'Con --db el registro queda en SQLite (adaptador PDO real)', (int) (new PDO('sqlite:'.$tmp.'/gate/qa-runs.sqlite'))->query('SELECT COUNT(*) FROM qa_gate_decisions')->fetchColumn() === 1);
$red = $manifest($tmp, [['name' => 'unit', 'kind' => 'unit', 'junit' => 'junit/unit.xml'], ['name' => 'e2e', 'kind' => 'e2e', 'junit' => 'junit/e2e-rojo.xml']]);
[$code, $out] = $run([...$base, "--manifest={$red}"]);
check('CI17', 'E2E obligatoria con un fallo y una omitida → exit 1 RECHAZADO', $code === 1 && str_contains($out, 'RECHAZADO') && str_contains($out, 'SKIPPED_IN_REQUIRED'));
$missing = $manifest($tmp, [['name' => 'unit', 'kind' => 'unit', 'junit' => 'junit/unit.xml'], ['name' => 'e2e', 'kind' => 'e2e', 'junit' => 'junit/no-corrio.xml']]);
[$code, $out] = $run([...$base, "--manifest={$missing}"]);
check('CI18', 'Etapa E2E que no publicó reporte → exit 2 BLOQUEADO con nota', $code === 2 && str_contains($out, 'BLOQUEADO') && str_contains($out, 'no se encontró junit/no-corrio.xml'));
[$code, $out] = $run(['--evidence-root='.$tmp, '--out-dir='.$tmp.'/gate', "--manifest={$green}"]);
check('CI19', 'Sin --sha → exit 3 (error de configuración, nunca aprobado)', $code === 3 && str_contains($out, '--sha'));
[$code] = $run([...$base, "--manifest={$green}", '--environment=production']);
check('CI20', 'Ambiente production → exit 3', $code === 3);
// Regresión del cambio de la Semana 6: un log adicional vacío abortaba todo el gate con exit 3.
touch("{$tmp}/junit/vacio.log");
$withEmptyLog = $manifest($tmp, [
    ['name' => 'unit', 'kind' => 'unit', 'junit' => 'junit/unit.xml', 'evidence' => [['kind' => 'log', 'path' => 'junit/vacio.log']]],
    ['name' => 'e2e', 'kind' => 'e2e', 'junit' => 'junit/unit.xml'],
]);
[$code, $out] = $run([...$base, "--manifest={$withEmptyLog}"]);
check('CI21', 'Log adicional vacío → se descarta con nota y el gate decide (exit 0), no aborta', $code === 0 && str_contains($out, 'evidencia adicional descartada junit/vacio.log'));

summary('ci');
