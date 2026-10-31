<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Application;

/** Falla de infraestructura. `duplicate` permite distinguir una colisión de idempotencia de una caída real. */
final class PersistenceFailure extends \RuntimeException
{
    public function __construct(
        public readonly bool $duplicate = false,
        ?\Throwable $previous = null,
    ) {
        parent::__construct('PERSISTENCE_FAILURE', 0, $previous);
    }
}
