<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Application;

use DateTimeImmutable;
use MicroHis\TrasladosAltas\Domain\EstadoInternacion;
use MicroHis\TrasladosAltas\Domain\PlanTraslado;
use MicroHis\TrasladosAltas\Domain\ReglasLiberacion;
use MicroHis\TrasladosAltas\Domain\SolicitudTraslado;

/** UC-TA-01: cierra la estancia de origen y abre la de destino contigua; el episodio sigue abierto. */
final class TrasladoInterno implements OperacionConLiberacionDeCama
{
    public function __construct(private readonly SolicitudTraslado $solicitud, private readonly string $actorRefId,
        private readonly RegistroInternacion $registro, private readonly Autorizador $autorizador)
    {
    }

    public function ejecutar(): ResultadoOperacion
    {
        return (new ProtocoloLiberacion($this->registro, $this->autorizador))->ejecutar(Operacion::Traslado, $this->actorRefId,
            $this->solicitud->estanciaEsperadaId, function (EstadoInternacion $estado, DateTimeImmutable $t) {
                $rechazo = ReglasLiberacion::rechazoDeEstancia($estado, $this->solicitud->estanciaEsperadaId, $t)
                    ?? ReglasLiberacion::rechazoDeDestino($this->registro->leerCama($this->solicitud->camaDestinoId, $t), $estado);
                if ($rechazo !== null) {
                    return $rechazo;
                }
                $plan = PlanTraslado::desde($estado, $this->solicitud, $t);

                return function () use ($plan) {
                    $this->registro->cerrarEstancia($plan->estanciaOrigenId, $plan->t);

                    return $plan->resultado($this->registro->abrirEstancia($plan, $this->actorRefId));
                };
            });
    }
}
