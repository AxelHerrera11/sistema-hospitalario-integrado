<?php
declare(strict_types=1);

namespace MicroHis\Pacientes\Domain;

final class DomainRuleViolation extends \DomainException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly ?string $field = null
    ) {
        parent::__construct($message);
    }
}