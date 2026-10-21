<?php

declare(strict_types=1);

$root = __DIR__ . '/src';

foreach ([
    '/Domain/DomainRuleViolation.php',
    '/Domain/ResultStatus.php',
    '/Domain/PendingLabResult.php',
    '/Domain/ResultRuleValidator.php',
    '/Application/CaptureResultCommand.php',
    '/Application/CapturePendingResult.php',
    '/Application/CorrectionCommand.php',
    '/Application/CorrectCapturedResult.php',
    '/Application/PendingResultRepository.php',
    '/Application/AuditLogger.php',
    '/Application/PendingResultNotFound.php',
] as $file) {
    require_once $root . $file;
}

foreach ([
    '/Persistence/InMemoryPendingResultRepository.php',
    '/Persistence/PdoPendingResultRepository.php',
    '/Persistence/ArrayAuditLogger.php',
    '/Presentation/HttpResultController.php',
] as $file) {
    if (is_file($root . $file)) {
        require_once $root . $file;
    }
}
