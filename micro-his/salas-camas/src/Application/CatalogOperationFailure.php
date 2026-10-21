<?php
declare(strict_types=1);

namespace MicroHis\SalasCamas\Application;

final class CatalogOperationFailure extends \RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
