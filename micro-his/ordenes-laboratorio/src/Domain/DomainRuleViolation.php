<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Domain;

/** Violación de una regla del dominio; el código es estable y no expone detalles de infraestructura. */
final class DomainRuleViolation extends \RuntimeException
{
    public function __construct(public readonly string $error, public readonly string $field = '')
    {
        parent::__construct($error);
    }
}
