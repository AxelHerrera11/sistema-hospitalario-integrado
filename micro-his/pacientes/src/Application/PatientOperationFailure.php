<?php
declare(strict_types=1);

namespace MicroHis\Pacientes\Application;

final class PatientOperationFailure extends \RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly ?string $field = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
