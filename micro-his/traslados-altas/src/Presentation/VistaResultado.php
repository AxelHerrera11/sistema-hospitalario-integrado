<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Presentation;

use MicroHis\TrasladosAltas\Application\ExitoConfirmado;
use MicroHis\TrasladosAltas\Application\FalloConfirmado;
use MicroHis\TrasladosAltas\Application\RechazoConfirmado;
use MicroHis\TrasladosAltas\Application\ResultadoIncierto;
use MicroHis\TrasladosAltas\Application\ResultadoOperacion;
use MicroHis\TrasladosAltas\Domain\ResultadoAlta;
use MicroHis\TrasladosAltas\Domain\ResultadoTraslado;

/**
 * Traduce cada resultado a un código de salida y a un cuerpo JSON. Solo expone identificadores (RNF-TA-07) y nunca
 * presenta como éxito un rechazo, un fallo o un resultado incierto.
 */
final class VistaResultado
{
    public const EXITO = 0;
    public const RECHAZO = 1;
    public const FALLO = 2;
    public const INCIERTO = 3;
    public const SOLICITUD_INVALIDA = 64;

    /** @return array{codigo: int, cuerpo: array<string, mixed>} */
    public static function de(ResultadoOperacion $resultado): array
    {
        return match (true) {
            $resultado instanceof ExitoConfirmado => ['codigo' => self::EXITO, 'cuerpo' => self::exito($resultado)],
            $resultado instanceof RechazoConfirmado => ['codigo' => self::RECHAZO, 'cuerpo' => ['resultado' => 'rechazo',
                'motivo' => $resultado->motivo->value, 'mensaje' => 'La operación fue rechazada; no se guardó ningún cambio.']],
            $resultado instanceof FalloConfirmado => ['codigo' => self::FALLO, 'cuerpo' => ['resultado' => 'fallo',
                'codigo' => $resultado->codigo, 'mensaje' => 'HOSPITAL no confirmó la operación; no se guardó ningún cambio.']],
            $resultado instanceof ResultadoIncierto => ['codigo' => self::INCIERTO, 'cuerpo' => ['resultado' => 'incierto',
                'estancia_esperada_id' => $resultado->estanciaEsperadaId,
                'mensaje' => 'No se sabe si HOSPITAL guardó la operación. Consulte el estado de la estancia antes de reintentar.']],
        };
    }

    /** @return array{codigo: int, cuerpo: array<string, mixed>} */
    public static function solicitudInvalida(string $campo): array
    {
        return ['codigo' => self::SOLICITUD_INVALIDA, 'cuerpo' => ['resultado' => 'solicitud_invalida', 'campo' => $campo,
            'uso' => 'trasladar <estancia_id> <cama_destino_id> <motivo> | alta <estancia_id>']];
    }

    private static function exito(ExitoConfirmado $exito): array
    {
        $l = $exito->liberacion;
        $comun = ['resultado' => 'exito', 'cama_liberada_id' => $l->camaOrigenId, 'estancia_cerrada_id' => $l->estanciaCerradaId,
            't' => $l->t->format(DATE_RFC3339_EXTENDED)];

        return $comun + match (true) {
            $l instanceof ResultadoTraslado => ['estancia_destino_id' => $l->estanciaDestinoId, 'cama_destino_id' => $l->camaDestinoId,
                'episodio_abierto_id' => $l->episodioAbiertoId],
            $l instanceof ResultadoAlta => ['episodio_cerrado_id' => $l->episodioCerradoId],
            default => [],
        };
    }
}
