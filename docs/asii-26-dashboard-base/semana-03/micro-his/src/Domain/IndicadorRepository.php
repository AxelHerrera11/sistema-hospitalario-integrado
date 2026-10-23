<?php

declare(strict_types=1);

namespace MicroHIS\Dashboard\Domain;

/**
 * Puerto de lectura de indicadores del dashboard. Definido en el Dominio.
 */
interface IndicadorRepository
{
    /** @return list<Indicador> */
    public function todos(): array;
}
