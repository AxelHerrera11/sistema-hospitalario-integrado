<?php

declare(strict_types=1);
/*
 * Runner minimo de pruebas del Micro-HIS Dashboard (Semana 4).
 * Ejecutar: php -d zend.assertions=1 -d assert.exception=1 tests/ejecutar_pruebas.php
 */

require __DIR__ . '/../autoload.php';

use MicroHIS\Dashboard\Application\ObtenerTablero;
use MicroHIS\Dashboard\Domain\Indicador;
use MicroHIS\Dashboard\Infrastructure\IndicadorRepositoryInMemory;
use MicroHIS\Dashboard\Infrastructure\IndicadorRepositoryPDO;
use MicroHIS\Dashboard\Presentation\TableroController;

$pruebas = [];
$pruebas['repository pdo e in memory cumplen el mismo contrato'] = static function (): void {
    foreach ([new IndicadorRepositoryInMemory(), new IndicadorRepositoryPDO(new \PDO('sqlite::memory:'))] as $repo) {
        $repo->guardar(Indicador::registrar('ocupacion', '2026-10', 82.0, 80.0));
        assert(count($repo->porPeriodo('2026-10')) === 1);
    }
};
$pruebas['caso de uso agrega cumplimiento de meta'] = static function (): void {
    $repo = new IndicadorRepositoryInMemory();
    $repo->guardar(Indicador::registrar('ocupacion', '2026-10', 82.0, 80.0));
    $repo->guardar(Indicador::registrar('espera', '2026-10', 15.0, 20.0));
    $tablero = (new ObtenerTablero($repo))->ejecutar('2026-10');
    assert($tablero['total'] === 2 && $tablero['cumplen_meta'] === 1);
};
$pruebas['controlador: periodo requerido y tablero 200'] = static function (): void {
    $repo = new IndicadorRepositoryInMemory();
    $c = new TableroController(new ObtenerTablero($repo));
    assert($c->mostrar([])['status'] === 422);
    assert($c->mostrar(['periodo' => '2026-10'])['status'] === 200);
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
