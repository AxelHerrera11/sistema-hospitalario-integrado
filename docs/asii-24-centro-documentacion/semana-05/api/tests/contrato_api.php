<?php

declare(strict_types=1);
/* Pruebas de contrato (Semana 5). Ejecutar: php -d zend.assertions=1 -d assert.exception=1 api/tests/contrato_api.php */

$construir = require __DIR__ . '/../rutas.php';

use MicroHIS\Documentacion\Infrastructure\DocumentoRepositoryInMemory;

$contrato = (string) file_get_contents(__DIR__ . '/../../contrato-api.yaml');
$pruebas = [];
$pruebas['ruta desconocida responde 404 con el sobre comun'] = static function () use ($construir): void {
    $r = $construir(new DocumentoRepositoryInMemory())->despachar('GET', '/no-existe', []);
    assert($r['status'] === 404 && $r['body']['code'] === 'route_not_found' && isset($r['body']['message']));
};
$pruebas['POST /documentos responde 201 con data.id'] = static function () use ($construir): void {
    $r = $construir(new DocumentoRepositoryInMemory())->despachar('POST', '/documentos', ['titulo' => 'Manual de enfermeria', 'roles' => ['enfermeria']]);
    assert($r['status'] === 201 && str_starts_with($r['body']['data']['id'], 'doc-'));
};
$pruebas['POST /documentos sin roles responde 422 documento_sin_roles'] = static function () use ($construir): void {
    $r = $construir(new DocumentoRepositoryInMemory())->despachar('POST', '/documentos', ['titulo' => 'Sin roles', 'roles' => []]);
    assert($r['status'] === 422 && $r['body']['code'] === 'documento_sin_roles');
};
$pruebas['GET /documentos devuelve solo lo visible para el rol'] = static function () use ($construir): void {
    $api = $construir(new DocumentoRepositoryInMemory());
    $api->despachar('POST', '/documentos', ['titulo' => 'Manual de enfermeria', 'roles' => ['enfermeria']]);
    $api->despachar('POST', '/documentos', ['titulo' => 'Manual de farmacia', 'roles' => ['farmacia']]);
    $r = $api->despachar('GET', '/documentos', ['rol' => 'farmacia']);
    assert($r['status'] === 200 && count($r['body']['data']['documentos']) === 1);
};
$pruebas['GET /documentos sin rol responde 422 validation_failed'] = static function () use ($construir): void {
    $r = $construir(new DocumentoRepositoryInMemory())->despachar('GET', '/documentos', []);
    assert($r['status'] === 422 && $r['body']['errors'] === ['rol']);
};
$pruebas['el contrato declara las rutas y status probados'] = static function () use ($contrato): void {
    // Estructura: la ruta debe estar bajo paths y el esquema bajo components.schemas (no en la raiz).
    assert(preg_match('/^paths:\R  \/documentos:/m', $contrato) === 1, 'ruta fuera de paths');
    assert(preg_match('/^  schemas:\R(?:    .*\R)*    Documento:/m', $contrato) === 1, 'esquema fuera de components.schemas');
    foreach (['/documentos:', 'post:', 'get:', "'201'", "'200'", "'422'", "'404'"] as $fragmento) {
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
