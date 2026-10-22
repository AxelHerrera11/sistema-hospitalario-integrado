<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Application;

/** Filtros de consulta inválidos; $errors usa los nombres públicos de parámetro. */
final class InvalidAuditFilter extends \DomainException
{
    /** @param array<string, string> $errors parámetro => código de error */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('invalid_audit_filter');
    }
}
