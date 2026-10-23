<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Domain;

/** Dato de entrada que viola una regla del dominio QA (HTTP 422). */
final class InvalidQaInput extends \DomainException
{
    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }
}
