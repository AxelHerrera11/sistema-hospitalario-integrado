<?php

declare(strict_types=1);

namespace MicroHis\CatalogoLab\Application;

/** La base falló; `duplicate` indica una violación de unicidad (alta concurrente). */
final class PersistenceFailure extends \RuntimeException
{
    public function __construct(public readonly bool $duplicate, ?\Throwable $previous = null)
    {
        parent::__construct($duplicate ? 'registro duplicado' : 'falla de persistencia', 0, $previous);
    }
}
