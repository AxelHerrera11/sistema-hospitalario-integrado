<?php

declare(strict_types=1);
/*
 * Runner minimo de pruebas del Micro-HIS Reportes operativos (sin PHPUnit).
 * Ejecutar: php -d zend.assertions=1 -d assert.exception=1 tests/ejecutar_pruebas.php
 */

require __DIR__ . '/../bootstrap.php';

use MicroHIS\Reportes\Application\GenerarReporte;
use MicroHIS\Reportes\Domain\Exportador;
use MicroHIS\Reportes\Domain\FiltroInvalidoException;
use MicroHIS\Reportes\Domain\FormatoNoSoportadoException;
use MicroHIS\Reportes\Domain\Reporte;
use MicroHIS\Reportes\Persistence\ExportadorCsv;
use MicroHIS\Reportes\Persistence\ExportadorJson;
use MicroHIS\Reportes\Persistence\ReporteRepositoryEnMemoria;

$pruebas = [];
$pruebas['dominio: rechaza rango de fechas invertido'] = static function (): void {
    try {
        Reporte::construir('2026-10-31', '2026-10-01', []);
        throw new \RuntimeException('no lanzo la excepcion esperada');
    } catch (FiltroInvalidoException $e) {
        assert($e->getMessage() === '422 rango_fechas_invalido');
    }
};
$pruebas['aplicacion: camino feliz filtra y exporta csv'] = static function (): void {
    $repositorio = new ReporteRepositoryEnMemoria();
    $repositorio->agregar(['fecha' => '2026-10-05', 'area' => 'consultas', 'total' => 12]);
    $repositorio->agregar(['fecha' => '2026-11-02', 'area' => 'consultas', 'total' => 9]);
    $caso = new GenerarReporte($repositorio, [new ExportadorCsv()]);
    $csv = $caso->ejecutar('2026-10-01', '2026-10-31', 'csv');
    assert(str_contains($csv, '2026-10-05'));
    assert(!str_contains($csv, '2026-11-02'));
};
$pruebas['aplicacion: formato no soportado'] = static function (): void {
    $caso = new GenerarReporte(new ReporteRepositoryEnMemoria(), [new ExportadorCsv()]);
    try {
        $caso->ejecutar('2026-10-01', '2026-10-31', 'pdf');
        throw new \RuntimeException('no lanzo la excepcion esperada');
    } catch (FormatoNoSoportadoException $e) {
        assert(str_contains($e->getMessage(), '406 report_format_unsupported'));
    }
};
$pruebas['ocp: un exportador nuevo funciona sin tocar el caso de uso'] = static function (): void {
    $txt = new class implements Exportador {
        public function formato(): string
        {
            return 'txt';
        }

        public function exportar(array $filas): string
        {
            return 'total=' . count($filas) . "\n";
        }
    };
    $repositorio = new ReporteRepositoryEnMemoria();
    $repositorio->agregar(['fecha' => '2026-10-05', 'area' => 'consultas', 'total' => 12]);
    $caso = new GenerarReporte($repositorio, [new ExportadorCsv(), $txt]);
    assert($caso->ejecutar('2026-10-01', '2026-10-31', 'txt') === "total=1\n");
};
$pruebas['aplicacion: json agrega el total'] = static function (): void {
    $repositorio = new ReporteRepositoryEnMemoria();
    $repositorio->agregar(['fecha' => '2026-10-05', 'area' => 'urgencias', 'total' => 7]);
    $json = (new GenerarReporte($repositorio, [new ExportadorJson()]))->ejecutar('2026-10-01', '2026-10-31', 'json');
    assert(json_decode($json, true)['total'] === 1);
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
