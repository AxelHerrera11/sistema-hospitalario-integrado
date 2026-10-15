<?php

declare(strict_types=1);

$root = __DIR__ . '/src';

foreach ([
    '/Domain/DomainRuleViolation.php',
    '/Domain/ReviewStatus.php',
    '/Domain/ReviewableLabResult.php',
    '/Domain/BiochemistReviewPolicy.php',
    '/Application/ReviewResultRepository.php',
    '/Application/ReviewAuditLogger.php',
    '/Application/ValidateResultCommand.php',
    '/Application/RejectResultCommand.php',
    '/Application/ValidateReviewedResult.php',
    '/Application/RejectReviewedResult.php',
    '/Persistence/InMemoryReviewResultRepository.php',
    '/Persistence/PdoReviewResultRepository.php',
    '/Persistence/ArrayReviewAuditLogger.php',
    '/Presentation/HttpReviewController.php',
] as $file) {
    require_once $root . $file;
}
