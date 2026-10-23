<?php

declare(strict_types=1);
/*
 * Runner minimo de pruebas del Micro-HIS Dashboard base (sin PHPUnit).
 * Ejecutar: php -d zend.assertions=1 -d assert.exception=1 tests/ejecutar_pruebas.php
 */

require __DIR__ . '/../bootstrap.php';

use MicroHIS\Dashboard\Application\ResumenDashboard;
use MicroHIS\Dashboard\Domain\ErrorPersistenciaException;
use MicroHIS\Dashboard\Domain\Indicador;
use MicroHIS\Dashboard\Domain\IndicadorInvalidoException;
use MicroHIS\Dashboard\Persistence\IndicadorRepositoryEnMemoria;
use MicroHIS\Dashboard\Persistence\IndicadorRepositoryQueFalla;

$pruebas = [];
$pruebas['dominio: rechaza valor negativo'] = static function (): void {
    try {
        Indicador::crear('citas_hoy', 'Citas de hoy', -1);
        throw new \RuntimeException('no lanzo la excepcion esperada');
    } catch (IndicadorInvalidoException $e) {
        assert($e->getMessage() === '422 indicador_valor_invalido');
    }
};
$pruebas['dominio: acepta valor cero'] = static function (): void {
    assert(Indicador::crear('docs_pendientes', 'Documentos pendientes', 0)->valor === 0);
};
$pruebas['aplicacion: camino feliz arma el resumen'] = static function (): void {
    $repositorio = new IndicadorRepositoryEnMemoria();
    $repositorio->agregar(Indicador::crear('citas_hoy', 'Citas de hoy', 12));
    $repositorio->agregar(Indicador::crear('pacientes_activos', 'Pacientes activos', 42));
    $resumen = (new ResumenDashboard($repositorio))->ejecutar();
    assert(count($resumen) === 2);
    assert($resumen[0]['clave'] === 'citas_hoy');
    assert($resumen[1]['valor'] === 42);
};
$pruebas['aplicacion: resumen vacio sin indicadores'] = static function (): void {
    assert((new ResumenDashboard(new IndicadorRepositoryEnMemoria()))->ejecutar() === []);
};
$pruebas['aplicacion: error de persistencia se propaga'] = static function (): void {
    try {
        (new ResumenDashboard(new IndicadorRepositoryQueFalla()))->ejecutar();
        throw new \RuntimeException('no lanzo la excepcion esperada');
    } catch (ErrorPersistenciaException $e) {
        assert($e->getMessage() === 'fallo simulado de persistencia');
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
