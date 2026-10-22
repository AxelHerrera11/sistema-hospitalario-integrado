<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Application;

use DateTimeImmutable;
use MicroHis\TrasladosAltas\Domain\EstadoInternacion;
use MicroHis\TrasladosAltas\Domain\PlanAlta;
use MicroHis\TrasladosAltas\Domain\ReglasLiberacion;
use MicroHis\TrasladosAltas\Domain\SolicitudAlta;

/** UC-TA-02: cierra la estancia y el episodio con la misma marca temporal; no toca beds.status (RF-TA-10). */
final class AltaHospitalaria implements OperacionConLiberacionDeCama
{
    public function __construct(private readonly SolicitudAlta $solicitud, private readonly string $actorRefId,
        private readonly RegistroInternacion $registro, private readonly Autorizador $autorizador)
    {
    }

    public function ejecutar(): ResultadoOperacion
    {
        return (new ProtocoloLiberacion($this->registro, $this->autorizador))->ejecutar(Operacion::Alta, $this->actorRefId,
            $this->solicitud->estanciaEsperadaId, function (EstadoInternacion $estado, DateTimeImmutable $t) {
                $rechazo = ReglasLiberacion::rechazoDeEstancia($estado, $this->solicitud->estanciaEsperadaId, $t);
                if ($rechazo !== null) {
                    return $rechazo;
                }
                $plan = PlanAlta::desde($estado, $t);

                return function () use ($plan) {
                    $this->registro->cerrarEstancia($plan->estanciaId, $plan->t);
                    $this->registro->cerrarEpisodio($plan->episodioId, $plan->t);

                    return $plan->resultado();
                };
            });
    }
}
