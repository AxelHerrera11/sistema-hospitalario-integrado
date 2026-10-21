<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Domain;

/** Severidad de un hallazgo; el orden numérico permite tomar el máximo. */
enum Severity: int
{
    case None = 0;
    case Warning = 1;
    case Critical = 2;

    public function max(self $other): self
    {
        return $other->value > $this->value ? $other : $this;
    }
}
