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

$implementsRepository = static fn (string $class): bool => in_array(\MicroHis\MedicationCatalog\Application\MedicationRepository::class, class_implements($class), true);
check('ambos adaptadores de Persistence cumplen el contrato MedicationRepository', $implementsRepository(\MicroHis\MedicationCatalog\Persistence\PdoMedicationCatalog::class)
    && $implementsRepository(\MicroHis\MedicationCatalog\Persistence\InMemoryMedicationRepository::class));
$clients = array_merge(array_map(static fn (string $useCase): string => __DIR__ . "/../src/Application/{$useCase}.php",
    ['RegisterMedication', 'ConsultMedications', 'ChangeMedicationValidity']), glob(__DIR__ . '/../src/Presentation/*Controller.php'));
check('ningún caso de uso ni controlador depende del Repository completo (se conserva el ISP)', count(array_filter($clients, 'is_file')) === 6
    && !preg_match('/\bMedicationRepository\b/', implode("\n", array_map('file_get_contents', $clients))));
$router = (string) file_get_contents(__DIR__ . '/../src/Presentation/Router.php');
check('el Router (MVC) no tiene SQL ni reglas de negocio: no usa Domain ni Persistence', !$uses($router, 'Domain') && !$uses($router, 'Persistence')
    && !preg_match('/\b(SELECT|INSERT|UPDATE|DELETE)\b|PDO/', $router));
