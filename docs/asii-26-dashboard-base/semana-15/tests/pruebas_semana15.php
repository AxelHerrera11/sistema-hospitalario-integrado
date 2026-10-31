<?php

declare(strict_types=1);
/*
 * Semana 15: 3 pruebas unitarias, 2 de caja blanca y 4 de caja negra sobre el Micro-HIS (Semana 4)
 * y su API REST (Semana 5). Desde semana-15/: php -d zend.assertions=1 -d assert.exception=1 tests/pruebas_semana15.php
 */

$construir = require __DIR__ . '/../../semana-05/api/rutas.php';

use MicroHIS\Dashboard\Api\Router;
use MicroHIS\Dashboard\Application\ObtenerTablero;
use MicroHIS\Dashboard\Domain\Indicador;
use MicroHIS\Dashboard\Domain\IndicadorInvalidoException;
use MicroHIS\Dashboard\Infrastructure\IndicadorRepositoryInMemory;

$pruebas = [];
$sembrado = static function (): IndicadorRepositoryInMemory {
    $repo = new IndicadorRepositoryInMemory();
    $repo->guardar(Indicador::registrar('ocupacion', '2026-11', 82.0, 80.0));
    $repo->guardar(Indicador::registrar('citas_atendidas', '2026-11', 70.0, 90.0));
    return $repo;
};
$pruebas['[UNIT-01] Indicador cumple la meta en el valor limite (valor = meta)'] = static function (): void {
    assert(Indicador::registrar('ocupacion', '2026-11', 80.0, 80.0)->cumpleMeta() === true);
    assert(Indicador::registrar('ocupacion', '2026-11', 79.9, 80.0)->cumpleMeta() === false);
};
$pruebas['[UNIT-02] Indicador::registrar sin clave lanza excepcion 422'] = static function (): void {
    try {
        Indicador::registrar('', '2026-11', 1.0, 1.0);
        assert(false, 'debio lanzar excepcion');
    } catch (IndicadorInvalidoException $e) {
        assert($e->getMessage() === '422 indicador_invalido');
    }
};
$pruebas['[UNIT-03] ObtenerTablero agrega total y cumplen_meta'] = static function () use ($sembrado): void {
    $tablero = (new ObtenerTablero($sembrado()))->ejecutar('2026-11');
    assert($tablero['total'] === 2 && $tablero['cumplen_meta'] === 1);
};
$pruebas['[WB-01] Indicador::registrar: MC/DC de (clave vacia || periodo vacio)'] = static function (): void {
    $casos = [['', '2026-11', true], ['ocupacion', '', true], ['ocupacion', '2026-11', false]];
    foreach ($casos as [$clave, $periodo, $debeFallar]) {
        try {
            Indicador::registrar($clave, $periodo, 1.0, 1.0);
            $fallo = false;
        } catch (IndicadorInvalidoException) {
            $fallo = true;
        }
        assert($fallo === $debeFallar, "{$clave}/{$periodo}");
    }
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
$pruebas['[BB-01] particion valida: GET /tablero con datos responde 200'] = static function () use ($construir, $sembrado): void {
    $r = $construir($sembrado())->despachar('GET', '/tablero', ['periodo' => '2026-11']);
    assert($r['status'] === 200 && count($r['body']['data']['indicadores']) === 2);
};
$pruebas['[BB-02] particion valida sin datos: periodo vacio de datos devuelve total 0'] = static function () use ($construir, $sembrado): void {
    $r = $construir($sembrado())->despachar('GET', '/tablero', ['periodo' => '2025-01']);
    assert($r['status'] === 200 && $r['body']['data']['total'] === 0);
};
$pruebas['[BB-03] particion invalida: sin periodo responde 422'] = static function () use ($construir, $sembrado): void {
    $r = $construir($sembrado())->despachar('GET', '/tablero', []);
    assert($r['status'] === 422 && $r['body']['code'] === 'periodo_requerido');
};
$pruebas['[BB-04] particion invalida: DELETE /tablero responde 405'] = static function () use ($construir, $sembrado): void {
    $r = $construir($sembrado())->despachar('DELETE', '/tablero', ['periodo' => '2026-11']);
    assert($r['status'] === 405);
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
