<?php

declare(strict_types=1);
/* Pruebas de contrato (Semana 5). Ejecutar: php -d zend.assertions=1 -d assert.exception=1 api/tests/contrato_api.php */

$construir = require __DIR__ . '/../rutas.php';

use MicroHIS\Reportes\Infrastructure\ReporteRepositoryInMemory;

$contrato = (string) file_get_contents(__DIR__ . '/../../contrato-api.yaml');
$pruebas = [];
$pruebas['ruta desconocida responde 404 con el sobre comun'] = static function () use ($construir): void {
    $r = $construir(new ReporteRepositoryInMemory())->despachar('GET', '/no-existe', []);
    assert($r['status'] === 404 && $r['body']['code'] === 'route_not_found' && isset($r['body']['message']));
};
$pruebas['POST /reportes responde 201 con data.id'] = static function () use ($construir): void {
    $r = $construir(new ReporteRepositoryInMemory())->despachar('POST', '/reportes', ['periodo' => '2026-11', 'datos' => ['atendidos' => 12]]);
    assert($r['status'] === 201 && str_starts_with($r['body']['data']['id'], 'rep-'));
};
$pruebas['POST /reportes sin periodo responde 422 reporte_invalido'] = static function () use ($construir): void {
    $r = $construir(new ReporteRepositoryInMemory())->despachar('POST', '/reportes', ['datos' => ['atendidos' => 1]]);
    assert($r['status'] === 422 && $r['body']['code'] === 'reporte_invalido');
};
$pruebas['GET /reportes lista solo el periodo pedido'] = static function () use ($construir): void {
    $api = $construir(new ReporteRepositoryInMemory());
    $api->despachar('POST', '/reportes', ['periodo' => '2026-10', 'datos' => []]);
    $api->despachar('POST', '/reportes', ['periodo' => '2026-11', 'datos' => []]);
    $r = $api->despachar('GET', '/reportes', ['periodo' => '2026-11']);
    assert($r['status'] === 200 && count($r['body']['data']['reportes']) === 1);
};
$pruebas['GET /reportes sin periodo responde 422 periodo_requerido'] = static function () use ($construir): void {
    $r = $construir(new ReporteRepositoryInMemory())->despachar('GET', '/reportes', []);
    assert($r['status'] === 422 && $r['body']['code'] === 'periodo_requerido');
};
$pruebas['el contrato declara las rutas y status probados'] = static function () use ($contrato): void {
    foreach (['/reportes:', 'post:', 'get:', "'201'", "'200'", "'422'", "'404'"] as $fragmento) {
        assert(str_contains($contrato, $fragmento), $fragmento);
    }
};
$total = 0;
$fallidas = 0;
foreach ($pruebas as $nombre => $prueba) {
    $total++;
    try {
        $prueba();
        echo "  [OK] {$nombre}\n";
    } catch (\Throwable $e) {
        $fallidas++;
        echo "  [FALLO] {$nombre}: {$e->getMessage()}\n";
    }
}
echo "\nResumen: {$total} pruebas, {$fallidas} fallidas.\n";
exit($fallidas === 0 ? 0 : 1);
