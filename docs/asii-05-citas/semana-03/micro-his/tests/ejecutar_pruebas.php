<?php

declare(strict_types=1);

/*
 * Runner minimo de pruebas del Micro-HIS Citas (sin PHPUnit).
 * Ejecutar: php tests/ejecutar_pruebas.php
 */

require __DIR__ . '/../bootstrap.php';

use MicroHIS\Citas\Application\AgendarCita;
use MicroHIS\Citas\Application\EntradaCita;
use MicroHIS\Citas\Domain\Cita;
use MicroHIS\Citas\Domain\ErrorPersistenciaException;
use MicroHIS\Citas\Domain\RangoFechaInvalidoException;
use MicroHIS\Citas\Domain\SolapeCitaException;
use MicroHIS\Citas\Persistence\CitaRepositoryEnMemoria;
use MicroHIS\Citas\Persistence\CitaRepositoryQueFalla;

$pruebas = [];
$pruebas['dominio: rechaza rango de fecha invertido'] = static function (): void {
    try {
        Cita::crear('C-1', 'P-1', 'D-1', '2026-10-21T10:00', '2026-10-21T09:00');
        throw new \RuntimeException('no lanzo la excepcion esperada');
    } catch (RangoFechaInvalidoException $e) {
        assert($e->getMessage() === '409 cita_rango_invalido');
    }
};
$pruebas['dominio: confirma, cancela y detecta solape'] = static function (): void {
    $cita = Cita::crear('C-2', 'P-2', 'D-2', '2026-10-21T11:00', '2026-10-21T11:30');
    assert($cita->seSolapaCon('P-9', 'D-2', '2026-10-21T11:15', '2026-10-21T11:45') === true);
    $cita->confirmar();
    assert($cita->estado() === Cita::CONFIRMED);
    $cita->cancelar();
    assert($cita->estado() === Cita::CANCELLED);
};
$pruebas['aplicacion: camino feliz agenda'] = static function (): void {
    $r = (new AgendarCita(new CitaRepositoryEnMemoria()))
        ->ejecutar(new EntradaCita('P-1', 'D-1', '2026-10-21T09:00', '2026-10-21T09:30'));
    assert($r->estado === 'scheduled');
};
$pruebas['aplicacion: rechaza solape sin persistir'] = static function (): void {
    $repo = new CitaRepositoryEnMemoria();
    $caso = new AgendarCita($repo);
    $caso->ejecutar(new EntradaCita('P-1', 'D-1', '2026-10-21T09:00', '2026-10-21T09:30'));
    try {
        $caso->ejecutar(new EntradaCita('P-1', 'D-9', '2026-10-21T09:15', '2026-10-21T09:45'));
        throw new \RuntimeException('no lanzo la excepcion esperada');
    } catch (SolapeCitaException $e) {
        assert($e->getMessage() === '409 appointments_overlap');
    }
    assert(count($repo->todas()) === 1);
};
$pruebas['aplicacion: error de persistencia se propaga'] = static function (): void {
    try {
        (new AgendarCita(new CitaRepositoryQueFalla()))
            ->ejecutar(new EntradaCita('P-1', 'D-1', '2026-10-21T09:00', '2026-10-21T09:30'));
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
