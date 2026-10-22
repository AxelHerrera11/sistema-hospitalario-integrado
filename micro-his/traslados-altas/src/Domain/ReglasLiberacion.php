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

    /**
     * RF-TA-05: el destino existe, no es la cama actual y está libre desde T. La exigencia de beds.status = available
     * es la decisión PROPUESTA R-TA-01 (Semana 2), pendiente de acordar con salas y camas (ASII-06).
     */
    public static function rechazoDeDestino(?CamaDestino $destino, EstadoInternacion $estado): ?Rechazo
    {
        return match (true) {
            $destino === null => Rechazo::DestinoInexistente,
            $destino->camaId === $estado->camaActualId => Rechazo::DestinoIgualOrigen,
            !$destino->libreDesdeT => Rechazo::DestinoOcupado,
            $destino->estado !== 'available' => Rechazo::DestinoNoDisponible,
            default => null,
        };
    }
}
