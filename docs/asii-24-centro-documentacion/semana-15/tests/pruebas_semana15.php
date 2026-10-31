<?php

declare(strict_types=1);
/*
 * Semana 15: 3 pruebas unitarias, 2 de caja blanca y 4 de caja negra sobre el Micro-HIS (Semana 4)
 * y su API REST (Semana 5). Desde semana-15/: php -d zend.assertions=1 -d assert.exception=1 tests/pruebas_semana15.php
 */

$construir = require __DIR__ . '/../../semana-05/api/rutas.php';

use MicroHIS\Documentacion\Api\Router;
use MicroHIS\Documentacion\Application\ConsultarDocumentos;
use MicroHIS\Documentacion\Application\PublicarDocumento;
use MicroHIS\Documentacion\Domain\Documento;
use MicroHIS\Documentacion\Domain\DocumentoSinRolesException;
use MicroHIS\Documentacion\Infrastructure\DocumentoRepositoryInMemory;

$pruebas = [];
$pruebas['[UNIT-01] Documento::publicar normaliza roles (sin vacios ni duplicados)'] = static function (): void {
    $d = Documento::publicar('doc-1', 'Manual', ['enfermeria', '', 'medico', 'enfermeria']);
    assert($d->roles === ['enfermeria', 'medico']);
};
$pruebas['[UNIT-02] Documento::publicar sin roles validos lanza excepcion 422'] = static function (): void {
    try {
        Documento::publicar('doc-1', 'Manual', ['', '']);
        assert(false, 'debio lanzar excepcion');
    } catch (DocumentoSinRolesException $e) {
        assert($e->getMessage() === '422 documento_sin_roles');
    }
};
$pruebas['[UNIT-03] ConsultarDocumentos lista solo lo visible para el rol'] = static function (): void {
    $repo = new DocumentoRepositoryInMemory();
    (new PublicarDocumento($repo))->ejecutar('Manual de enfermeria', ['enfermeria']);
    (new PublicarDocumento($repo))->ejecutar('Guia comun', ['enfermeria', 'farmacia']);
    assert(count((new ConsultarDocumentos($repo))->listar('farmacia')) === 1);
};
$pruebas['[WB-01] Documento::publicar y visiblePara cubren ambas ramas'] = static function (): void {
    $lanzo = false;
    try {
        Documento::publicar('doc-1', 'Vacio', []);  // rama: roles === [] (verdadero)
    } catch (DocumentoSinRolesException) {
        $lanzo = true;
    }
    $d = Documento::publicar('doc-2', 'Guia', ['medico']);  // rama: roles === [] (falso)
    assert($lanzo && $d->visiblePara('medico') === true && $d->visiblePara('farmacia') === false);
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
$pruebas['[BB-01] particion valida: POST /documentos responde 201'] = static function () use ($construir): void {
    $r = $construir(new DocumentoRepositoryInMemory())->despachar('POST', '/documentos', ['titulo' => 'Manual', 'roles' => ['medico']]);
    assert($r['status'] === 201);
};
$pruebas['[BB-02] particion invalida: POST sin titulo responde 422 validation_failed'] = static function () use ($construir): void {
    $r = $construir(new DocumentoRepositoryInMemory())->despachar('POST', '/documentos', ['roles' => ['medico']]);
    assert($r['status'] === 422 && $r['body']['errors'] === ['titulo']);
};
$pruebas['[BB-03] particion valida sin datos: rol sin documentos recibe lista vacia'] = static function () use ($construir): void {
    $api = $construir(new DocumentoRepositoryInMemory());
    $api->despachar('POST', '/documentos', ['titulo' => 'Manual', 'roles' => ['medico']]);
    $r = $api->despachar('GET', '/documentos', ['rol' => 'farmacia']);
    assert($r['status'] === 200 && $r['body']['data']['documentos'] === []);
};
$pruebas['[BB-04] valor limite: el rol se compara exacto (mayusculas no dan acceso)'] = static function () use ($construir): void {
    $api = $construir(new DocumentoRepositoryInMemory());
    $api->despachar('POST', '/documentos', ['titulo' => 'Manual', 'roles' => ['medico']]);
    $r = $api->despachar('GET', '/documentos', ['rol' => 'Medico']);
    assert($r['status'] === 200 && $r['body']['data']['documentos'] === []);
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
