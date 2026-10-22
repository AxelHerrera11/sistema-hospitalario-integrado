<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Domain;

/**
 * La cama destino tal como la ve la transacción: su beds.status y si alguna estancia de esa cama se solapa con
 * [T, ∞). La exclusión bed_stays_bed_active_period_exclusion de HOSPITAL respalda el solape en la base.
 */
final readonly class CamaDestino
{
    public function __construct(public string $camaId, public string $estado, public bool $libreDesdeT)
    {
    }
}
