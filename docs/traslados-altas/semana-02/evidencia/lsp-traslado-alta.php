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
