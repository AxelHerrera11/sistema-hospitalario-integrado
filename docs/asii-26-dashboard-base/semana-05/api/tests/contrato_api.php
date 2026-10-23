<?php

declare(strict_types=1);
/* Pruebas de contrato (Semana 5). Ejecutar: php -d zend.assertions=1 -d assert.exception=1 api/tests/contrato_api.php */

$construir = require __DIR__ . '/../rutas.php';

use MicroHIS\Dashboard\Infrastructure\IndicadorRepositoryInMemory;

$contrato = (string) file_get_contents(__DIR__ . '/../../contrato-api.yaml');
$pruebas = [];
$pruebas['ruta desconocida responde 404 con el sobre comun'] = static function () use ($construir): void {
    $r = $construir(new IndicadorRepositoryInMemory())->despachar('GET', '/no-existe', []);
    assert($r['status'] === 404 && $r['body']['code'] === 'route_not_found' && isset($r['body']['message']));
};
use MicroHIS\Dashboard\Domain\Indicador;

$pruebas['GET /tablero responde 200 con el read model del periodo'] = static function () use ($construir): void {
    $repo = new IndicadorRepositoryInMemory();
    $repo->guardar(Indicador::registrar('ocupacion', '2026-11', 82.0, 80.0));
    $repo->guardar(Indicador::registrar('citas_atendidas', '2026-11', 70.0, 90.0));
    $r = $construir($repo)->despachar('GET', '/tablero', ['periodo' => '2026-11']);
    assert($r['status'] === 200 && $r['body']['data']['total'] === 2 && $r['body']['data']['cumplen_meta'] === 1);
};
$pruebas['GET /tablero sin periodo responde 422 periodo_requerido'] = static function () use ($construir): void {
    $r = $construir(new IndicadorRepositoryInMemory())->despachar('GET', '/tablero', []);
    assert($r['status'] === 422 && $r['body']['code'] === 'periodo_requerido');
};
$pruebas['POST /tablero responde 405 (recurso de solo lectura)'] = static function () use ($construir): void {
    $r = $construir(new IndicadorRepositoryInMemory())->despachar('POST', '/tablero', ['periodo' => '2026-11']);
    assert($r['status'] === 405 && $r['body']['code'] === 'method_not_allowed');
};
$pruebas['el contrato declara las rutas y status probados'] = static function () use ($contrato): void {
    foreach (['/tablero:', 'get:', "'200'", "'422'", "'405'", "'404'"] as $fragmento) {
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
