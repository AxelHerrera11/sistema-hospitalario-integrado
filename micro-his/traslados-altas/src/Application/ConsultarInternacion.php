<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Application;

use MicroHis\TrasladosAltas\Domain\EstadoInternacion;

/**
 * Consulta de solo lectura del episodio al que pertenece una estancia. Es la vía para resolver un ResultadoIncierto:
 * en lugar de reintentar a ciegas, el cliente consulta si la estancia esperada sigue abierta (RNF-TA-06).
 * La transacción se revierte siempre: nunca escribe.
 */
final class ConsultarInternacion
{
    public function __construct(private readonly RegistroInternacion $registro, private readonly Autorizador $autorizador)
    {
    }

    /**
     * @return EstadoInternacion|null null si la estancia no existe
     * @throws NoAutorizado si el actor no puede consultar
     * @throws FalloPersistencia si HOSPITAL no responde
     */
    public function porEstancia(string $actorRefId, string $estanciaId): ?EstadoInternacion
    {
        if (!$this->autorizador->permite($actorRefId, Operacion::Consulta)) {
            throw new NoAutorizado();
        }
        $this->registro->iniciar();
        try {
            return $this->registro->leerPorEstancia($estanciaId);
        } finally {
            $this->registro->revertir();
        }
    }
}
