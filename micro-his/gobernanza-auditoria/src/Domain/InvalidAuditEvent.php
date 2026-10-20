<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Domain;

/** El evento no cumple el contrato (dato faltante o con formato inválido). */
final class InvalidAuditEvent extends \InvalidArgumentException
{
}
