<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Application;

/**
 * RNF-TA-06: el COMMIT se envió y no hubo respuesta fiable (p. ej. se cortó la conexión). Puede haberse confirmado
 * o no: no es éxito ni rollback garantizado. El cliente debe consultar el estado antes de reintentar.
 */
final readonly class ResultadoIncierto implements ResultadoOperacion
{
    public function __construct(public string $estanciaEsperadaId)
    {
    }

    public function confirmoCambios(): bool
    {
        return false;
    }
}
