<?php
declare(strict_types=1);

namespace MicroHis\SalasCamas\Domain;

final class DomainRuleViolation extends \DomainException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
