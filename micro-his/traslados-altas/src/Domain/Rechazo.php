<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Domain;

/** Motivos de rechazo confirmados: la operación no escribe nada (RNF-TA-02). */
enum Rechazo: string
{
    case NoAutorizado = 'NO_AUTORIZADO';
    case EstanciaInexistente = 'ESTANCIA_INEXISTENTE';
    case EpisodioNoHospitalario = 'EPISODIO_NO_HOSPITALARIO';
    case EpisodioNoAbierto = 'EPISODIO_NO_ABIERTO';
    case SinEstanciaAbierta = 'SIN_ESTANCIA_ABIERTA';
    case EstanciaNoVigente = 'ESTANCIA_NO_VIGENTE';
    case MomentoNoPosterior = 'MOMENTO_NO_POSTERIOR';
    case DestinoInexistente = 'DESTINO_INEXISTENTE';
    case DestinoIgualOrigen = 'DESTINO_IGUAL_ORIGEN';
    case DestinoOcupado = 'DESTINO_OCUPADO';
    case DestinoNoDisponible = 'DESTINO_NO_DISPONIBLE';
}
