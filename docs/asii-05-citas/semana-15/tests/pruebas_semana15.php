<?php

declare(strict_types=1);
/*
 * Semana 15: 3 pruebas unitarias, 2 de caja blanca y 4 de caja negra sobre el Micro-HIS (Semana 4)
 * y su API REST (Semana 5). Desde semana-15/: php -d zend.assertions=1 -d assert.exception=1 tests/pruebas_semana15.php
 */

$construir = require __DIR__ . '/../../semana-05/api/rutas.php';

use MicroHIS\Citas\Api\Router;
use MicroHIS\Citas\Application\AgendarCita;
use MicroHIS\Citas\Application\ListarCitas;
use MicroHIS\Citas\Domain\Cita;
use MicroHIS\Citas\Domain\RangoFechaInvalidoException;
use MicroHIS\Citas\Infrastructure\CitaRepositoryInMemory;

$pruebas = [];
$base = ['paciente' => 'P-1', 'doctor' => 'D-1', 'desde' => '2026-11-02T09:00:00-06:00', 'hasta' => '2026-11-02T09:30:00-06:00'];
$pruebas['[UNIT-01] Cita::agendar crea la cita en estado scheduled'] = static function (): void {
    $cita = Cita::agendar('C-1', 'P-1', 'D-1', '2026-11-02T09:00:00-06:00', '2026-11-02T09:30:00-06:00');
    assert($cita->estado === Cita::SCHEDULED && $cita->paciente === 'P-1');
};
$pruebas['[UNIT-02] Cita::agendar rechaza rango invertido'] = static function (): void {
    try {
        Cita::agendar('C-1', 'P-1', 'D-1', '2026-11-02T10:00:00-06:00', '2026-11-02T09:00:00-06:00');
        assert(false, 'debio lanzar excepcion');
    } catch (RangoFechaInvalidoException $e) {
        assert($e->getMessage() === '409 cita_rango_invalido');
    }
};
$pruebas['[UNIT-03] AgendarCita persiste y ListarCitas filtra por paciente'] = static function (): void {
    $repo = new CitaRepositoryInMemory();
    (new AgendarCita($repo))->ejecutar('P-1', 'D-1', '2026-11-02T09:00:00-06:00', '2026-11-02T09:30:00-06:00');
    (new AgendarCita($repo))->ejecutar('P-2', 'D-2', '2026-11-02T09:00:00-06:00', '2026-11-02T09:30:00-06:00');
    assert(count($repo->todas()) === 2 && count((new ListarCitas($repo))->ejecutar('P-2')) === 1);
};
$pruebas['[WB-01] Cita::seSolapaCon cubre todas sus condiciones'] = static function (): void {
    $c = Cita::agendar('C-1', 'P-1', 'D-1', '2026-11-02T09:00:00-06:00', '2026-11-02T09:30:00-06:00');
    assert($c->seSolapaCon('P-1', 'D-9', Cita::enUtc('2026-11-02T09:15:00-06:00'), Cita::enUtc('2026-11-02T09:45:00-06:00')) === true);  // mismo paciente
    assert($c->seSolapaCon('P-9', 'D-1', Cita::enUtc('2026-11-02T08:45:00-06:00'), Cita::enUtc('2026-11-02T09:15:00-06:00')) === true);  // mismo doctor
    assert($c->seSolapaCon('P-9', 'D-9', Cita::enUtc('2026-11-02T09:00:00-06:00'), Cita::enUtc('2026-11-02T09:30:00-06:00')) === false); // actor distinto
    assert($c->seSolapaCon('P-1', 'D-1', Cita::enUtc('2026-11-02T09:30:00-06:00'), Cita::enUtc('2026-11-02T10:00:00-06:00')) === false); // inicia al terminar
    assert($c->seSolapaCon('P-1', 'D-1', Cita::enUtc('2026-11-02T08:30:00-06:00'), Cita::enUtc('2026-11-02T09:00:00-06:00')) === false); // termina al iniciar
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
$pruebas['[BB-01] particion valida: POST /citas responde 201'] = static function () use ($construir, $base): void {
    $r = $construir(new CitaRepositoryInMemory())->despachar('POST', '/citas', $base);
    assert($r['status'] === 201 && $r['body']['data']['estado'] === 'scheduled');
};
$pruebas['[BB-02] valor limite: cita contigua (inicia cuando termina otra) se acepta'] = static function () use ($construir, $base): void {
    $api = $construir(new CitaRepositoryInMemory());
    $api->despachar('POST', '/citas', $base);
    $r = $api->despachar('POST', '/citas', ['desde' => '2026-11-02T09:30:00-06:00', 'hasta' => '2026-11-02T10:00:00-06:00'] + $base);
    assert($r['status'] === 201);
};
$pruebas['[BB-03] particion invalida: solape de un minuto responde 409'] = static function () use ($construir, $base): void {
    $api = $construir(new CitaRepositoryInMemory());
    $api->despachar('POST', '/citas', $base);
    $r = $api->despachar('POST', '/citas', ['doctor' => 'D-2', 'desde' => '2026-11-02T09:29:00-06:00', 'hasta' => '2026-11-02T10:00:00-06:00'] + $base);
    assert($r['status'] === 409 && $r['body']['code'] === 'appointments_overlap');
};
$pruebas['[BB-04] particion invalida: rango invertido responde 409 cita_rango_invalido'] = static function () use ($construir, $base): void {
    $r = $construir(new CitaRepositoryInMemory())->despachar('POST', '/citas', ['desde' => '2026-11-02T10:00:00-06:00', 'hasta' => '2026-11-02T09:00:00-06:00'] + $base);
    assert($r['status'] === 409 && $r['body']['code'] === 'cita_rango_invalido');
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
