<?php

declare(strict_types=1);
/*
 * Runner minimo de pruebas del Micro-HIS Reportes (Semana 4).
 * Ejecutar: php -d zend.assertions=1 -d assert.exception=1 tests/ejecutar_pruebas.php
 */

require __DIR__ . '/../autoload.php';

use MicroHIS\Reportes\Application\GenerarReporte;
use MicroHIS\Reportes\Infrastructure\ReporteRepositoryInMemory;
use MicroHIS\Reportes\Infrastructure\ReporteRepositoryPDO;
use MicroHIS\Reportes\Presentation\ReporteController;

$pruebas = [];
$pruebas['repository pdo e in memory cumplen el mismo contrato'] = static function (): void {
    foreach ([new ReporteRepositoryInMemory(), new ReporteRepositoryPDO(new \PDO('sqlite::memory:'))] as $repo) {
        (new GenerarReporte($repo))->ejecutar('2026-10', ['atendidos' => 10]);
        assert(count($repo->porPeriodo('2026-10')) === 1);
    }
};
$pruebas['controlador: genera y lista por periodo'] = static function (): void {
    $repo = new ReporteRepositoryInMemory();
    $c = new ReporteController(new GenerarReporte($repo));
    assert($c->generar(['periodo' => '2026-10', 'datos' => ['atendidos' => 5]])['status'] === 201);
    assert($c->listar(['periodo' => '2026-10'])['status'] === 200);
};
$pruebas['controlador: periodo requerido al listar'] = static function (): void {
    $repo = new ReporteRepositoryInMemory();
    $c = new ReporteController(new GenerarReporte($repo));
    assert($c->listar([])['status'] === 422);
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
