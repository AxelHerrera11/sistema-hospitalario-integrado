<?php

declare(strict_types=1);

/*
 * ASII-08 · Semana 2 · Evidencia ejecutable de LSP. PHP 8.2+, sin framework ni base de datos.
 * Ejecutar desde la raíz: php docs/traslados-altas/semana-02/evidencia/demo-lsp.php
 * Modelo en memoria: no valida PostgreSQL, bloqueos, atomicidad, transacciones reales, GiST,
 * triggers, concurrencia ni COMMIT. Los desenlaces de COMMIT son SIMULADOS.
 * Sale con código 1 solo si falla una comprobación.
 */

require_once __DIR__ . '/lsp-traslado-alta.php';

/** Adaptador en memoria del puerto: confirmar() reemplaza el estado completo o lo descarta. */
final class AdaptadorHospitalEnMemoria implements PuertoHospital
{
    public function __construct(public array $estado, private DateTimeImmutable $t) {}

    public function leer(): array
    {
        return $this->estado;
    }

    public function ahora(): DateTimeImmutable
    {
        return $this->t;
    }

    public function confirmar(array $estadoNuevo): string
    {
        $this->estado = $estadoNuevo;
        return 'confirmado';
    }
}

function hora(string $hhmm): DateTimeImmutable
{
    return new DateTimeImmutable("2026-08-11 $hhmm+00");
}

/** Datos ficticios. A está 'occupied' a propósito: liberar la ocupación no debe volverla available. */
function escenario(string $t = '12:00'): AdaptadorHospitalEnMemoria
{
    $estado = ['episodios' => [], 'camas' => [], 'estancias' => []];
    $episodios = [
        'E1' => ['inpatient', 'open', '08:00'],
        'E2' => ['inpatient', 'open', '07:00'],
        'E3' => ['outpatient', 'open', '10:00'],
        'E4' => ['inpatient', 'closed', '06:00'],
        'E5' => ['inpatient', 'open', '06:00'],
    ];
    foreach ($episodios as $id => [$tipo, $situacion, $inicio]) {
        $estado['episodios'][$id] = ['id' => $id, 'tipo' => $tipo, 'estado' => $situacion, 'inicio' => hora($inicio)];
    }
    foreach (['A' => 'occupied', 'B' => 'available', 'C' => 'maintenance', 'D' => 'available'] as $id => $situacion) {
        $estado['camas'][$id] = ['id' => $id, 'estado' => $situacion];
    }
    $estancias = [
        'S0' => ['E1', 'C', '08:00', '09:00'],
        'S1' => ['E1', 'A', '09:00', null],
        'S2' => ['E2', 'D', '08:00', null],
        'S3' => ['E3', 'C', '10:00', null],
        'S4' => ['E4', 'B', '06:30', '07:30'],
        'S5' => ['E5', 'D', '06:00', '07:00'],
    ];
    foreach ($estancias as $id => [$episodio, $cama, $inicio, $fin]) {
        $estado['estancias'][$id] = ['id' => $id, 'episodio' => $episodio, 'cama' => $cama, 'inicio' => hora($inicio), 'fin' => $fin === null ? null : hora($fin)];
    }
    return new AdaptadorHospitalEnMemoria($estado, hora($t));
}

$fallos = 0;

function comprobar(string $nombre, bool $cumple): void
{
    global $fallos;
    echo ($cumple ? 'PASS  ' : 'FAIL  ') . $nombre . PHP_EOL;
    $fallos += $cumple ? 0 : 1;
}

/** Ejecuta la operación y exige RechazoConfirmado con el código indicado y el estado intacto. */
function comprobarRechazo(string $codigo, AdaptadorHospitalEnMemoria $h, OperacionConLiberacionDeCama $operacion, string $caso = ''): void
{
    $antes = $h->estado;
    $r = $operacion->ejecutar();
    comprobar("{$caso}{$codigo}, sin cambios", $r instanceof RechazoConfirmado && $r->codigo === $codigo && $h->estado === $antes);
}

function abiertas(AdaptadorHospitalEnMemoria $h, string $episodioId): int
{
    return count(array_filter($h->estado['estancias'], fn ($e) => $e['episodio'] === $episodioId && $e['fin'] === null));
}

function traslado(AdaptadorHospitalEnMemoria $h, string $estancia, string $destino = 'B'): TrasladoInterno
{
    return new TrasladoInterno(new SolicitudTraslado($estancia, $destino, 'Cambio de unidad'), $h);
}

function alta(AdaptadorHospitalEnMemoria $h, string $estancia): AltaHospitalaria
{
    return new AltaHospitalaria(new SolicitudAlta($estancia), $h);
}

echo PHP_EOL . ($fallos === 0 ? 'Todas las comprobaciones se cumplieron.' : "$fallos comprobación(es) fallaron.") . PHP_EOL;
exit($fallos === 0 ? 0 : 1);
