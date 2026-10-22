<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Domain;

/** Los identificadores HOSPITAL son uuid (bed_stays, beds y care_episodes); se normalizan a minúsculas. */
final class Identificador
{
    private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/D';

    public static function exigir(string $valor, string $campo): string
    {
        $valor = strtolower(trim($valor));
        if (preg_match(self::UUID, $valor) !== 1) {
            throw new SolicitudInvalida($campo);
        }

        return $valor;
    }
}
