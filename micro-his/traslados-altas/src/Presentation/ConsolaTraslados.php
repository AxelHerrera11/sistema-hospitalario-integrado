<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Presentation;

use Closure;
use MicroHis\TrasladosAltas\Application\AltaHospitalaria;
use MicroHis\TrasladosAltas\Application\Autorizador;
use MicroHis\TrasladosAltas\Application\RegistroInternacion;
use MicroHis\TrasladosAltas\Application\TrasladoInterno;
use MicroHis\TrasladosAltas\Domain\SolicitudAlta;
use MicroHis\TrasladosAltas\Domain\SolicitudInvalida;
use MicroHis\TrasladosAltas\Domain\SolicitudTraslado;

/**
 * Controller de consola: convierte argumentos en una solicitud, ejecuta la operación y delega la vista. Sin SQL ni reglas.
 * Las dependencias se piden solo después de validar la solicitud: una solicitud inválida nunca abre conexiones.
 */
final class ConsolaTraslados
{
    /** @param Closure(): array{0: RegistroInternacion, 1: Autorizador} $dependencias */
    public function __construct(private readonly Closure $dependencias, private readonly string $actorRefId)
    {
    }

    /**
     * @param list<string> $argumentos trasladar <estancia> <cama> <motivo> | alta <estancia>
     * @return array{codigo: int, cuerpo: array<string, mixed>}
     */
    public function atender(array $argumentos): array
    {
        try {
            $solicitud = match ($argumentos[0] ?? '') {
                'trasladar' => new SolicitudTraslado($argumentos[1] ?? '', $argumentos[2] ?? '', $argumentos[3] ?? ''),
                'alta' => new SolicitudAlta($argumentos[1] ?? ''),
                default => throw new SolicitudInvalida('operacion'),
            };
        } catch (SolicitudInvalida $invalida) {
            return VistaResultado::solicitudInvalida($invalida->campo);
        }
        [$registro, $autorizador] = ($this->dependencias)();
        $operacion = $solicitud instanceof SolicitudTraslado
            ? new TrasladoInterno($solicitud, $this->actorRefId, $registro, $autorizador)
            : new AltaHospitalaria($solicitud, $this->actorRefId, $registro, $autorizador);

        return VistaResultado::de($operacion->ejecutar());
    }
}
