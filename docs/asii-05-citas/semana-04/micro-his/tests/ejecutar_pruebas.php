<?php

declare(strict_types=1);
/*
 * Runner minimo de pruebas del Micro-HIS Citas (Semana 4).
 * Ejecutar: php -d zend.assertions=1 -d assert.exception=1 tests/ejecutar_pruebas.php
 */

require __DIR__ . '/../autoload.php';

use MicroHIS\Citas\Application\AgendarCita;
use MicroHIS\Citas\Infrastructure\CitaRepositoryInMemory;
use MicroHIS\Citas\Infrastructure\CitaRepositoryPDO;
use MicroHIS\Citas\Presentation\CitaController;

$pruebas = [];
$pruebas['repository pdo e in memory cumplen el mismo contrato'] = static function (): void {
    $pdo = new CitaRepositoryPDO(new \PDO('sqlite::memory:'));
    $mem = new CitaRepositoryInMemory();
    foreach ([$pdo, $mem] as $repo) {
        (new AgendarCita($repo))->ejecutar('P-1', 'D-1', '2026-10-21T09:00:00-06:00', '2026-10-21T09:30:00-06:00');
        assert(count($repo->todas()) === 1);
    }
};
$pruebas['controlador: camino feliz responde 201'] = static function (): void {
    $controlador = new CitaController(new AgendarCita(new CitaRepositoryInMemory()));
    $r = $controlador->agendar(['paciente' => 'P-1', 'doctor' => 'D-1', 'desde' => '2026-10-21T09:00:00-06:00', 'hasta' => '2026-10-21T09:30:00-06:00']);
    assert($r['status'] === 201 && $r['body']['estado'] === 'scheduled');
};
$pruebas['controlador: errores de dominio responden 409'] = static function (): void {
    $controlador = new CitaController(new AgendarCita(new CitaRepositoryInMemory()));
    $controlador->agendar(['paciente' => 'P-1', 'doctor' => 'D-1', 'desde' => '2026-10-21T09:00:00-06:00', 'hasta' => '2026-10-21T09:30:00-06:00']);
    $solape = $controlador->agendar(['paciente' => 'P-1', 'doctor' => 'D-9', 'desde' => '2026-10-21T09:15:00-06:00', 'hasta' => '2026-10-21T09:45:00-06:00']);
    $rango = $controlador->agendar(['paciente' => 'P-2', 'doctor' => 'D-2', 'desde' => '2026-10-21T10:00:00-06:00', 'hasta' => '2026-10-21T09:00:00-06:00']);
    assert($solape['status'] === 409 && $solape['body']['error'] === 'appointments_overlap');
    assert($rango['status'] === 409 && $rango['body']['error'] === 'cita_rango_invalido');
};
$pruebas['revision #1182: zona horaria, datos vacios y solape entre zonas'] = static function (): void {
    $c = new CitaController(new AgendarCita(new CitaRepositoryInMemory()));
    $zona = $c->agendar(['paciente' => 'P-1', 'doctor' => 'D-1', 'desde' => '2026-10-27T10:00:00-06:00', 'hasta' => '2026-10-27T15:00:00Z']);
    $vacios = $c->agendar(['paciente' => '', 'doctor' => '', 'desde' => 'a', 'hasta' => 'b']);
    $c->agendar(['paciente' => 'P-2', 'doctor' => 'D-2', 'desde' => '2026-10-27T09:00:00-06:00', 'hasta' => '2026-10-27T10:00:00-06:00']);
    $solape = $c->agendar(['paciente' => 'P-3', 'doctor' => 'D-2', 'desde' => '2026-10-27T15:30:00Z', 'hasta' => '2026-10-27T16:30:00Z']);
    $fecha = $c->agendar(['paciente' => 'P-4', 'doctor' => 'D-4', 'desde' => '2026-10-27T09:00', 'hasta' => '2026-10-27T10:00:00Z']);
    assert($zona['body']['error'] === 'cita_rango_invalido' && $vacios['status'] === 422 && $vacios['body']['error'] === 'cita_datos_invalidos');
    assert($solape['body']['error'] === 'appointments_overlap' && $fecha['status'] === 422 && $fecha['body']['error'] === 'cita_fecha_invalida');
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
