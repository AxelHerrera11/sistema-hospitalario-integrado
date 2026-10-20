<?php

declare(strict_types=1);

// Dirección de dependencias entre capas, comprobada sobre el código fuente.

echo "\n== Arquitectura ==\n";
$layer = static fn (string $name): string => implode("\n", array_map('file_get_contents', glob(__DIR__ . "/../src/{$name}/*.php")));
$domain = $layer('Domain');
$application = $layer('Application');
$presentation = $layer('Presentation');
$persistence = $layer('Persistence');
$uses = static fn (string $code, string $target): bool => str_contains($code, "MicroHis\\MedicationCatalog\\{$target}\\");

check('Domain no depende de ninguna otra capa ni de PDO', !$uses($domain, 'Application') && !$uses($domain, 'Presentation') && !$uses($domain, 'Persistence') && !str_contains($domain, 'PDO'));
check('Application depende solo de Domain (sin PDO, Presentation ni Persistence)', !$uses($application, 'Presentation') && !$uses($application, 'Persistence') && !str_contains($application, 'PDO'));
check('Presentation no conoce Persistence, PDO ni SQL', !$uses($presentation, 'Persistence') && !str_contains($presentation, 'PDO')
    && !preg_match('/\b(SELECT|INSERT|UPDATE|DELETE)\b/', $presentation));
check('Persistence usa prepare y nunca query/exec', str_contains($persistence, '->prepare(') && !preg_match('/->(query|exec)\(/', $persistence));
check('ninguna capa implementa borrado de medicamentos', !preg_match('/\bDELETE\b|function\s+delete/i', $domain . $application . $presentation . $persistence));
