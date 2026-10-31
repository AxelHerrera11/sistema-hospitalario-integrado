<?php

declare(strict_types=1);
/* Pruebas de contrato (Semana 5). Ejecutar: php -d zend.assertions=1 -d assert.exception=1 api/tests/contrato_api.php */

$construir = require __DIR__ . '/../rutas.php';

use MicroHIS\Citas\Infrastructure\CitaRepositoryInMemory;

$contrato = (string) file_get_contents(__DIR__ . '/../../contrato-api.yaml');
$pruebas = [];
$pruebas['ruta desconocida responde 404 con el sobre comun'] = static function () use ($construir): void {
    $r = $construir(new CitaRepositoryInMemory())->despachar('GET', '/no-existe', []);
    assert($r['status'] === 404 && $r['body']['code'] === 'route_not_found' && isset($r['body']['message']));
};
$cita = ['paciente' => 'P-1', 'doctor' => 'D-1', 'desde' => '2026-11-02T09:00:00-06:00', 'hasta' => '2026-11-02T09:30:00-06:00'];
$pruebas['POST /citas camino feliz responde 201 con data'] = static function () use ($construir, $cita): void {
    $r = $construir(new CitaRepositoryInMemory())->despachar('POST', '/citas', $cita);
    assert($r['status'] === 201 && $r['body']['data']['estado'] === 'scheduled' && $r['body']['data']['id'] !== '');
};
$pruebas['POST /citas solapada responde 409 appointments_overlap'] = static function () use ($construir, $cita): void {
    $api = $construir(new CitaRepositoryInMemory());
    $api->despachar('POST', '/citas', $cita);
    $r = $api->despachar('POST', '/citas', ['doctor' => 'D-2', 'desde' => '2026-11-02T09:15:00-06:00', 'hasta' => '2026-11-02T09:45:00-06:00'] + $cita);
    assert($r['status'] === 409 && $r['body']['code'] === 'appointments_overlap');
};
$pruebas['POST /citas sin campos responde 422 validation_failed'] = static function () use ($construir): void {
    $r = $construir(new CitaRepositoryInMemory())->despachar('POST', '/citas', ['paciente' => 'P-1']);
    assert($r['status'] === 422 && $r['body']['errors'] === ['doctor', 'desde', 'hasta']);
};
$pruebas['GET /citas filtra por paciente y responde 200'] = static function () use ($construir, $cita): void {
    $api = $construir(new CitaRepositoryInMemory());
    $api->despachar('POST', '/citas', $cita);
    $api->despachar('POST', '/citas', ['paciente' => 'P-2', 'doctor' => 'D-2'] + $cita);
    $r = $api->despachar('GET', '/citas', ['paciente' => 'P-2']);
    assert($r['status'] === 200 && count($r['body']['data']) === 1 && $r['body']['data'][0]['paciente'] === 'P-2');
};
$pruebas['el contrato declara las rutas y status probados'] = static function () use ($contrato): void {
    foreach (['/citas:', 'post:', 'get:', "'201'", "'409'", "'422'", "'404'"] as $fragmento) {
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
