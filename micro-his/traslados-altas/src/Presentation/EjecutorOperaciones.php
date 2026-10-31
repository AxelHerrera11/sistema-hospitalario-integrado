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
 * Componente común de los controllers (consola y HTTP).
 * Entrada: tipo de operación, campos con nombre y actor. Salida: código y cuerpo de VistaResultado.
 * No conoce argv ni HTTP; pide las dependencias solo cuando la solicitud es válida.
 */
final class EjecutorOperaciones
{
    public const TRASLADO = 'traslado';
    public const ALTA = 'alta';

    /** @param Closure(): array{0: RegistroInternacion, 1: Autorizador} $dependencias */
    public function __construct(private readonly Closure $dependencias)
    {
    }

    /**
     * @param array<string, mixed> $campos estancia_esperada_id, cama_destino_id y motivo (los dos últimos solo en el traslado)
     * @return array{codigo: int, cuerpo: array<string, mixed>}
     */
    public function ejecutar(string $tipo, array $campos, string $actorRefId): array
    {
        try {
            $solicitud = self::solicitud($tipo, $campos);
        } catch (SolicitudInvalida $invalida) {
            return VistaResultado::solicitudInvalida($invalida->campo);
        }
        [$registro, $autorizador] = ($this->dependencias)();
        $operacion = $solicitud instanceof SolicitudTraslado
            ? new TrasladoInterno($solicitud, $actorRefId, $registro, $autorizador)
            : new AltaHospitalaria($solicitud, $actorRefId, $registro, $autorizador);

        return VistaResultado::de($operacion->ejecutar());
    }

    /**
     * Un valor que no es escalar (p. ej. un arreglo en el JSON) cuenta como ausente: nunca se convierte en "Array".
     *
     * @param array<string, mixed> $campos
     * @throws SolicitudInvalida
     */
    public static function solicitud(string $tipo, array $campos): SolicitudTraslado|SolicitudAlta
    {
        $texto = static fn (string $campo): string => is_scalar($campos[$campo] ?? null) ? (string) $campos[$campo] : '';

        return match ($tipo) {
            self::TRASLADO => new SolicitudTraslado($texto('estancia_esperada_id'), $texto('cama_destino_id'), $texto('motivo')),
            self::ALTA => new SolicitudAlta($texto('estancia_esperada_id')),
            default => throw new SolicitudInvalida('operacion'),
        };
    }
}
