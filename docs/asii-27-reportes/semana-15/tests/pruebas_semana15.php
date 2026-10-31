<?php

declare(strict_types=1);
/*
 * Semana 15: 3 pruebas unitarias, 2 de caja blanca y 4 de caja negra sobre el Micro-HIS (Semana 4)
 * y su API REST (Semana 5). Desde semana-15/: php -d zend.assertions=1 -d assert.exception=1 tests/pruebas_semana15.php
 */

$construir = require __DIR__ . '/../../semana-05/api/rutas.php';

use MicroHIS\Reportes\Api\Router;
use MicroHIS\Reportes\Application\GenerarReporte;
use MicroHIS\Reportes\Domain\Reporte;
use MicroHIS\Reportes\Domain\ReporteInvalidoException;
use MicroHIS\Reportes\Infrastructure\ReporteRepositoryInMemory;
use MicroHIS\Reportes\Presentation\ReporteController;

$pruebas = [];
$pruebas['[UNIT-01] Reporte::generar conserva periodo y contenido'] = static function (): void {
    $r = Reporte::generar('rep-1', '2026-11', ['atendidos' => 12]);
    assert($r->periodo === '2026-11' && $r->contenido === ['atendidos' => 12]);
};
$pruebas['[UNIT-02] Reporte::generar sin periodo lanza excepcion 422'] = static function (): void {
    try {
        Reporte::generar('rep-1', '', []);
        assert(false, 'debio lanzar excepcion');
    } catch (ReporteInvalidoException $e) {
        assert($e->getMessage() === '422 reporte_invalido');
    }
};
$pruebas['[UNIT-03] GenerarReporte::listar filtra por periodo'] = static function (): void {
    $uc = new GenerarReporte(new ReporteRepositoryInMemory());
    $uc->ejecutar('2026-10', []);
    $uc->ejecutar('2026-11', ['atendidos' => 3]);
    assert(count($uc->listar('2026-11')) === 1 && $uc->listar('2026-11')[0]['contenido'] === ['atendidos' => 3]);
};
$pruebas['[WB-01] ReporteController recorre sus 4 caminos'] = static function (): void {
    $c = new ReporteController(new GenerarReporte(new ReporteRepositoryInMemory()));
    assert($c->generar(['periodo' => ''])['status'] === 422);        // catch ReporteInvalidoException
    assert($c->generar(['periodo' => '2026-11'])['status'] === 201); // generar exitoso
    assert($c->listar([])['status'] === 422);                        // periodo vacio
    assert($c->listar(['periodo' => '2026-11'])['status'] === 200);  // listar exitoso
};
$pruebas['[WB-02] Router::despachar recorre sus 5 caminos (404, 405, 422, error, exito)'] = static function (): void {
    $router = new Router();
    $router->agregar('POST', '/x', static fn (array $e): array => ($e['falla'] ?? false)
        ? ['status' => 409, 'body' => ['error' => 'regla']]
        : ['status' => 201, 'body' => ['ok' => true]], ['campo']);
    $caminos = [
        [['GET', '/nada', []], 404, 'route_not_found'],
        [['GET', '/x', []], 405, 'method_not_allowed'],
        [['POST', '/x', []], 422, 'validation_failed'],
        [['POST', '/x', ['campo' => 'v', 'falla' => true]], 409, 'regla'],
        [['POST', '/x', ['campo' => 'v']], 201, null],
    ];
    foreach ($caminos as [[$metodo, $ruta, $entrada], $status, $codigo]) {
        $r = $router->despachar($metodo, $ruta, $entrada);
        assert($r['status'] === $status && ($r['body']['code'] ?? null) === $codigo, "{$metodo} {$ruta}");
    }
};
$pruebas['[BB-01] particion valida: POST /reportes responde 201'] = static function () use ($construir): void {
    $r = $construir(new ReporteRepositoryInMemory())->despachar('POST', '/reportes', ['periodo' => '2026-11', 'datos' => ['atendidos' => 1]]);
    assert($r['status'] === 201);
};
$pruebas['[BB-02] particion invalida: POST sin periodo responde 422 reporte_invalido'] = static function () use ($construir): void {
    $r = $construir(new ReporteRepositoryInMemory())->despachar('POST', '/reportes', ['datos' => []]);
    assert($r['status'] === 422 && $r['body']['code'] === 'reporte_invalido');
};
$pruebas['[BB-03] particion valida sin datos: periodo sin reportes devuelve lista vacia'] = static function () use ($construir): void {
    $r = $construir(new ReporteRepositoryInMemory())->despachar('GET', '/reportes', ['periodo' => '2025-01']);
    assert($r['status'] === 200 && $r['body']['data']['reportes'] === []);
};
$pruebas['[BB-04] particion invalida: GET sin periodo responde 422 periodo_requerido'] = static function () use ($construir): void {
    $r = $construir(new ReporteRepositoryInMemory())->despachar('GET', '/reportes', []);
    assert($r['status'] === 422 && $r['body']['code'] === 'periodo_requerido');
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
