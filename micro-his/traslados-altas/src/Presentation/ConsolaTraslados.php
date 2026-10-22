<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Presentation;

use MicroHis\TrasladosAltas\Application\AltaHospitalaria;
use MicroHis\TrasladosAltas\Application\Autorizador;
use MicroHis\TrasladosAltas\Application\RegistroInternacion;
use MicroHis\TrasladosAltas\Application\TrasladoInterno;
use MicroHis\TrasladosAltas\Domain\SolicitudAlta;
use MicroHis\TrasladosAltas\Domain\SolicitudInvalida;
use MicroHis\TrasladosAltas\Domain\SolicitudTraslado;

/** Controller de consola: convierte argumentos en una solicitud, ejecuta la operación y delega la vista. Sin SQL ni reglas. */
final class ConsolaTraslados
{
    public function __construct(private readonly RegistroInternacion $registro, private readonly Autorizador $autorizador,
        private readonly string $actorRefId)
    {
    }

    /**
     * @param list<string> $argumentos trasladar <estancia> <cama> <motivo> | alta <estancia>
     * @return array{codigo: int, cuerpo: array<string, mixed>}
     */
    public function atender(array $argumentos): array
    {
        try {
            $operacion = match ($argumentos[0] ?? '') {
                'trasladar' => new TrasladoInterno(new SolicitudTraslado($argumentos[1] ?? '', $argumentos[2] ?? '', $argumentos[3] ?? ''),
                    $this->actorRefId, $this->registro, $this->autorizador),
                'alta' => new AltaHospitalaria(new SolicitudAlta($argumentos[1] ?? ''), $this->actorRefId, $this->registro, $this->autorizador),
                default => throw new SolicitudInvalida('operacion'),
            };
        } catch (SolicitudInvalida $invalida) {
            return VistaResultado::solicitudInvalida($invalida->campo);
        }

        return VistaResultado::de($operacion->ejecutar());
    }
}
