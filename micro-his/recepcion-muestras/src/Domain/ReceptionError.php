<?php

declare(strict_types=1);

namespace Reception\Domain;

final class ReceptionError extends \DomainException
{
    public function __construct(public readonly string $error, string $message, int $status)
    {
        parent::__construct($message, $status);
    }
}
