<?php

declare(strict_types=1);

namespace MicroHis\Alertas\Application;

/** La base falló; `duplicate` indica una violación de unicidad (evento repetido o acuse concurrente). */
final class PersistenceFailure extends \RuntimeException
{
    public function __construct(public readonly bool $duplicate, ?\Throwable $previous = null)
    {
        parent::__construct($duplicate ? 'registro duplicado' : 'falla de persistencia', 0, $previous);
    }
}
