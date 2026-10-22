<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Application;

use Closure;
use DateTimeImmutable;
use MicroHis\TrasladosAltas\Domain\EstadoInternacion;
use MicroHis\TrasladosAltas\Domain\LiberacionDeCama;
use MicroHis\TrasladosAltas\Domain\Rechazo;

/**
 * Flujo común del traslado y del alta: autorizar, abrir la transacción, leer bloqueando, decidir, escribir y confirmar.
 * Cada operación aporta solo su decisión; así ambas cumplen la misma postcondición (LSP, RNF-TA-01).
 */
final class ProtocoloLiberacion
{
    public function __construct(private readonly RegistroInternacion $registro, private readonly Autorizador $autorizador)
    {
    }

    /**
     * @param Closure(EstadoInternacion, DateTimeImmutable): (Rechazo|Closure(): LiberacionDeCama) $decidir
     *        devuelve el rechazo o las escrituras que producen la liberación.
     */
    public function ejecutar(Operacion $operacion, string $actorRefId, string $estanciaEsperadaId, Closure $decidir): ResultadoOperacion
    {
        if (!$this->autorizador->permite($actorRefId, $operacion)) {
            return new RechazoConfirmado(Rechazo::NoAutorizado);
        }
        try {
            $this->registro->iniciar();
            $estado = $this->registro->leerPorEstancia($estanciaEsperadaId);
            $decision = $estado === null ? Rechazo::EstanciaInexistente : $decidir($estado, $this->registro->ahora());
            if ($decision instanceof Rechazo) {
                $this->registro->revertir();

                return new RechazoConfirmado($decision);
            }
            $liberacion = $decision();
        } catch (FalloPersistencia) {
            $this->registro->revertir();

            return new FalloConfirmado('HOSPITAL_NO_CONFIRMO');
        }
        try {
            $this->registro->confirmar();
        } catch (ConfirmacionIncierta) {
            // Sin revertir ni reintentar: el COMMIT pudo haberse aplicado (RNF-TA-06).
            return new ResultadoIncierto($estanciaEsperadaId);
        } catch (FalloPersistencia) {
            $this->registro->revertir();

            return new FalloConfirmado('HOSPITAL_NO_CONFIRMO');
        }

        return new ExitoConfirmado($liberacion);
    }
}
