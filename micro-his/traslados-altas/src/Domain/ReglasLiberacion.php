<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Domain;

use DateTimeImmutable;

/** Reglas de dominio comunes al traslado y al alta; funciones puras, sin acceso a datos. */
final class ReglasLiberacion
{
    /** RF-TA-04: la estancia esperada debe ser la abierta de un episodio hospitalario abierto, y T posterior a ambos inicios. */
    public static function rechazoDeEstancia(EstadoInternacion $estado, string $estanciaEsperadaId, DateTimeImmutable $t): ?Rechazo
    {
        return match (true) {
            $estado->tipoEpisodio !== 'inpatient' => Rechazo::EpisodioNoHospitalario,
            $estado->estadoEpisodio !== 'open' => Rechazo::EpisodioNoAbierto,
            $estado->estanciaAbiertaId === null => Rechazo::SinEstanciaAbierta,
            $estado->estanciaAbiertaId !== $estanciaEsperadaId => Rechazo::EstanciaNoVigente,
            $t <= $estado->inicioEpisodio || $t <= $estado->inicioEstancia => Rechazo::MomentoNoPosterior,
            default => null,
        };
    }
}
