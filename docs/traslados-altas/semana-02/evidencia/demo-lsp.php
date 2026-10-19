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

/** La MISMA batería para cualquier OperacionConLiberacionDeCama. */
function bateriaDeContrato(string $nombre, Closure $crear): void
{
    echo PHP_EOL . "== Contrato común: $nombre ==" . PHP_EOL;
    $h = escenario();
    $r = $crear($h, 'S1')->ejecutar();
    $lib = $r instanceof ExitoConfirmado ? $r->liberacion : null;
    comprobar('ExitoConfirmado contiene una LiberacionDeCama', $lib instanceof LiberacionDeCama);
    comprobar('S1 termina en T; la liberación informa cama A, estancia S1 y T', $h->estado['estancias']['S1']['fin'] == hora('12:00')
        && $lib?->camaOrigenId === 'A' && $lib->estanciaCerradaId === 'S1' && $lib->t == hora('12:00'));
    comprobar('como máximo una estancia abierta en E1', abiertas($h, 'E1') <= 1);
    comprobar('beds.status intacto: A sigue occupied (liberar ≠ available)', $h->estado['camas'] === escenario()->estado['camas']);
    foreach (['S3' => 'EPISODIO_NO_INPATIENT', 'S4' => 'EPISODIO_NO_ABIERTO', 'S5' => 'SIN_ESTANCIA_ABIERTA', 'S0' => 'ESTANCIA_DESACTUALIZADA'] as $estancia => $codigo) {
        $h = escenario();
        comprobarRechazo($codigo, $h, $crear($h, $estancia));
    }
    $h = escenario('09:00');
    comprobarRechazo('T_INVALIDA', $h, $crear($h, 'S1'));
}

bateriaDeContrato('TrasladoInterno', fn (AdaptadorHospitalEnMemoria $h, string $estancia) => traslado($h, $estancia));
bateriaDeContrato('AltaHospitalaria', fn (AdaptadorHospitalEnMemoria $h, string $estancia) => alta($h, $estancia));

echo PHP_EOL . '== Resultados específicos ==' . PHP_EOL;
$h = escenario();
$r = traslado($h, 'S1')->ejecutar();
$lib = $r instanceof ExitoConfirmado ? $r->liberacion : null;
$destino = $lib instanceof ResultadoTraslado ? $h->estado['estancias'][$lib->estanciaDestinoId] : null;
comprobar('traslado: ResultadoTraslado y E1 sigue open', $destino !== null && $h->estado['episodios']['E1']['estado'] === 'open');
comprobar('traslado: destino B en [T, ∞), mismo episodio y contiguo a S1', $destino !== null && $destino['cama'] === 'B'
    && $destino['episodio'] === 'E1' && $destino['inicio'] == $h->estado['estancias']['S1']['fin'] && $destino['fin'] === null);
foreach (['Z' => 'DESTINO_INEXISTENTE', 'A' => 'DESTINO_IGUAL_ORIGEN', 'C' => 'DESTINO_NO_AVAILABLE', 'D' => 'DESTINO_SOLAPADO'] as $cama => $codigo) {
    $h = escenario();
    comprobarRechazo($codigo, $h, traslado($h, 'S1', $cama), "traslado a $cama: ");
}
$h = escenario();
$r = alta($h, 'S1')->ejecutar();
comprobar('alta: ResultadoAlta y E1 closed en T', $r instanceof ExitoConfirmado && $r->liberacion instanceof ResultadoAlta
    && $h->estado['episodios']['E1']['estado'] === 'closed' && $h->estado['episodios']['E1']['fin'] == hora('12:00'));
comprobar('alta: ninguna estancia abierta y ninguna creada', abiertas($h, 'E1') === 0 && count($h->estado['estancias']) === 6);
$invalidas = [
    'ESTANCIA_ESPERADA_REQUERIDA' => fn () => new SolicitudAlta(''),
    'DESTINO_REQUERIDO' => fn () => new SolicitudTraslado('S1', '', 'Cambio de unidad'),
    'REASON_VACIO' => fn () => new SolicitudTraslado('S1', 'B', '   '),
];
foreach ($invalidas as $codigo => $construir) {
    try {
        $construir();
        comprobar("solicitud inválida: $codigo", false);
    } catch (SolicitudInvalida $e) {
        comprobar("solicitud inválida: $codigo, sin operación", $e->getMessage() === $codigo);
    }
}

echo PHP_EOL . ($fallos === 0 ? 'Todas las comprobaciones se cumplieron.' : "$fallos comprobación(es) fallaron.") . PHP_EOL;
exit($fallos === 0 ? 0 : 1);
