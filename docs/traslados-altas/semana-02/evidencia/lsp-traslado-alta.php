<?php

declare(strict_types=1);

/*
 * ASII-08 · Semana 2 · Diseño después de ../solid-lsp.md, reducido a lo necesario para
 * demostrar LSP. Código didáctico en memoria; no es un servicio productivo.
 */

/** Error de construcción, no un resultado de ejecutar(); el mensaje es el código. */
final class SolicitudInvalida extends InvalidArgumentException
{
}

function exigir(bool $condicion, string $codigo): void
{
    if (!$condicion) {
        throw new SolicitudInvalida($codigo);
    }
}

final readonly class SolicitudTraslado
{
    public function __construct(public string $estanciaEsperadaId, public string $camaDestinoId, public string $reason)
    {
        exigir($estanciaEsperadaId !== '', 'ESTANCIA_ESPERADA_REQUERIDA');
        exigir($camaDestinoId !== '', 'DESTINO_REQUERIDO');
        exigir(trim($reason) !== '', 'REASON_VACIO');
    }
}

final readonly class SolicitudAlta
{
    public function __construct(public string $estanciaEsperadaId)
    {
        exigir($estanciaEsperadaId !== '', 'ESTANCIA_ESPERADA_REQUERIDA');
    }
}

/** Garantía común: la estancia esperada termina en T. No promete el estado del episodio ni disponibilidad. */
readonly class LiberacionDeCama
{
    public function __construct(public string $camaOrigenId, public string $estanciaCerradaId, public DateTimeImmutable $t) {}
}

/** Añade: episodio abierto y estancia destino [T, ∞). */
final readonly class ResultadoTraslado extends LiberacionDeCama
{
    public function __construct(LiberacionDeCama $comun, public string $estanciaDestinoId)
    {
        parent::__construct($comun->camaOrigenId, $comun->estanciaCerradaId, $comun->t);
    }
}

/** Añade: episodio closed en T y sin estancias abiertas. */
final readonly class ResultadoAlta extends LiberacionDeCama
{
}

interface ResultadoOperacion
{
}

final readonly class ExitoConfirmado implements ResultadoOperacion
{
    public function __construct(public LiberacionDeCama $liberacion) {}
}

final readonly class RechazoConfirmado implements ResultadoOperacion
{
    public function __construct(public string $codigo) {}
}

final readonly class FalloConfirmado implements ResultadoOperacion
{
    public function __construct(public string $codigo) {}
}

final readonly class ResultadoIncierto implements ResultadoOperacion
{
}

interface OperacionConLiberacionDeCama
{
    public function ejecutar(): ResultadoOperacion;
}

/**
 * Simplificación exclusiva de la demo en memoria: no modela ni demuestra bloqueos,
 * atomicidad o transacciones reales.
 */
interface PuertoHospital
{
    public function leer(): array;

    public function ahora(): DateTimeImmutable;

    /** Devuelve 'confirmado', 'fallo' (se sabe que no se confirmó) o 'incierto'. */
    public function confirmar(array $estadoNuevo): string;
}

/** Validación común. Devuelve [episodio, estancia actual] o un código; el episodio sale de la estancia esperada. */
function validarEstanciaActual(array $estado, string $estanciaEsperadaId, DateTimeImmutable $t): array|string
{
    $esperada = $estado['estancias'][$estanciaEsperadaId] ?? null;
    $episodio = $esperada === null ? null : ($estado['episodios'][$esperada['episodio']] ?? null);
    $actual = $episodio === null ? null : estanciaAbierta($estado, $episodio['id']);
    return match (true) {
        $episodio === null => 'ESTANCIA_DESACTUALIZADA',
        $episodio['tipo'] !== 'inpatient' => 'EPISODIO_NO_INPATIENT',
        $episodio['estado'] !== 'open' => 'EPISODIO_NO_ABIERTO',
        $actual === null => 'SIN_ESTANCIA_ABIERTA',
        $actual['id'] !== $estanciaEsperadaId => 'ESTANCIA_DESACTUALIZADA',
        !($t > $episodio['inicio'] && $t > $actual['inicio']) => 'T_INVALIDA',
        default => [$episodio, $actual],
    };
}

function estanciaAbierta(array $estado, string $episodioId): ?array
{
    foreach ($estado['estancias'] as $estancia) {
        if ($estancia['episodio'] === $episodioId && $estancia['fin'] === null) {
            return $estancia;
        }
    }
    return null;
}

/** Modela el solape por cama con rangos [inicio, fin): nadie ocupa la cama en [T, ∞). */
function camaLibreDesde(array $estado, string $camaId, DateTimeImmutable $t): bool
{
    foreach ($estado['estancias'] as $estancia) {
        if ($estancia['cama'] === $camaId && ($estancia['fin'] === null || $estancia['fin'] > $t)) {
            return false;
        }
    }
    return true;
}

/** Traslado calculado sobre una copia: [estado nuevo, ResultadoTraslado] o un código de rechazo. */
function calcularTraslado(array $estado, SolicitudTraslado $s, DateTimeImmutable $t): array|string
{
    $base = validarEstanciaActual($estado, $s->estanciaEsperadaId, $t);
    if (is_string($base)) {
        return $base;
    }
    [$episodio, $origen] = $base;
    $destino = $estado['camas'][$s->camaDestinoId] ?? null;
    $codigo = match (true) {
        $destino === null => 'DESTINO_INEXISTENTE',
        $destino['id'] === $origen['cama'] => 'DESTINO_IGUAL_ORIGEN',
        $destino['estado'] !== 'available' => 'DESTINO_NO_AVAILABLE',
        !camaLibreDesde($estado, $destino['id'], $t) => 'DESTINO_SOLAPADO',
        default => null,
    };
    if ($codigo !== null) {
        return $codigo;
    }
    $nueva = 'N' . (count($estado['estancias']) + 1);
    $estado['estancias'][$origen['id']]['fin'] = $t;
    $estado['estancias'][$nueva] = ['id' => $nueva, 'episodio' => $episodio['id'], 'cama' => $destino['id'], 'inicio' => $t, 'fin' => null];
    $comun = new LiberacionDeCama($origen['cama'], $origen['id'], $t);
    return [$estado, new ResultadoTraslado($comun, $nueva)];
}

/** Alta calculada sobre una copia: [estado nuevo, ResultadoAlta] o un código de rechazo. */
function calcularAlta(array $estado, SolicitudAlta $s, DateTimeImmutable $t): array|string
{
    $base = validarEstanciaActual($estado, $s->estanciaEsperadaId, $t);
    if (is_string($base)) {
        return $base;
    }
    [$episodio, $estancia] = $base;
    $estado['estancias'][$estancia['id']]['fin'] = $t;
    $estado['episodios'][$episodio['id']]['estado'] = 'closed';
    $estado['episodios'][$episodio['id']]['fin'] = $t;
    return [$estado, new ResultadoAlta($estancia['cama'], $estancia['id'], $t)];
}

/** Traduce el cálculo y el desenlace de confirmar() a uno de los cuatro resultados del contrato. */
function ejecutarCalculo(PuertoHospital $puerto, Closure $calcular): ResultadoOperacion
{
    $calculo = $calcular($puerto->leer(), $puerto->ahora());
    if (is_string($calculo)) {
        return new RechazoConfirmado($calculo);
    }
    [$estadoNuevo, $liberacion] = $calculo;
    return match ($puerto->confirmar($estadoNuevo)) {
        'confirmado' => new ExitoConfirmado($liberacion),
        'fallo' => new FalloConfirmado('COMMIT_RECHAZADO'),
        default => new ResultadoIncierto(),
    };
}

final class TrasladoInterno implements OperacionConLiberacionDeCama
{
    public function __construct(private SolicitudTraslado $solicitud, private PuertoHospital $puerto) {}

    public function ejecutar(): ResultadoOperacion
    {
        return ejecutarCalculo($this->puerto, fn (array $e, DateTimeImmutable $t) => calcularTraslado($e, $this->solicitud, $t));
    }
}

final class AltaHospitalaria implements OperacionConLiberacionDeCama
{
    public function __construct(private SolicitudAlta $solicitud, private PuertoHospital $puerto) {}

    public function ejecutar(): ResultadoOperacion
    {
        return ejecutarCalculo($this->puerto, fn (array $e, DateTimeImmutable $t) => calcularAlta($e, $this->solicitud, $t));
    }
}
