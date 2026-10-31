<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Application;

interface Clock
{
    /** Instante actual en ISO-8601 UTC. */
    public function now(): string;
}
