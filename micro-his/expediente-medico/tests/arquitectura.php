<?php

declare(strict_types=1);

/**
 * Reglas de ARQUITECTURA verificadas sobre el código fuente (Semanas 2–4): dependencias hacia adentro (DIP),
 * controlador sin SQL ni reglas, PDO solo con sentencias preparadas e integración con otros módulos solo en Integration.
 */
echo PHP_EOL . '== Arquitectura (capas y dependencias)' . PHP_EOL;

/** @return array<string, string> ruta relativa => código SIN comentarios (los docblocks citan otros módulos a propósito) */
function layer(string $name): array
{
    $files = [];
    foreach (glob(__DIR__ . "/../src/$name/*.php") ?: [] as $file) {
        $tokens = token_get_all((string) file_get_contents($file));
        $code = array_map(static fn ($t): string => is_array($t) ? (in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true) ? '' : $t[1]) : $t, $tokens);
        $files["$name/" . basename($file)] = implode('', $code);
    }

    return $files;
}

$offending = static fn (array $files, string $pattern): array => array_keys(array_filter($files, static fn (string $code): bool => preg_match($pattern, $code) === 1));

$domain = layer('Domain');
$application = layer('Application');
$presentation = layer('Presentation');
$persistence = layer('Persistence');

check('R-01 Domain no depende de Application, Persistence, Presentation, Integration ni PDO',
    $offending($domain, '/ExpedienteMedico\\\\(Application|Persistence|Presentation|Integration)|\bPDO\b/') === []);
check('R-02 Application no conoce PDO, SQL, adaptadores ni otros módulos (solo puertos propios)',
    $offending($application, '/\bPDO\b|\bSELECT\b|\bINSERT\b|ExpedienteMedico\\\\(Persistence|Presentation|Integration)|MicroHis\\\\(Rbac|Gobernanza|Pacientes)/') === []);
check('R-03 Presentation (controlador, router, vista) no tiene SQL ni usa PDO ni adaptadores',
    $offending($presentation, '/\bPDO\b|\bSELECT\b|\bINSERT\b|ExpedienteMedico\\\\(Persistence|Integration)/') === []);
check('R-04 el controlador no aplica reglas de dominio (no construye MedicalRecord ni valida estados)',
    preg_match('/new MedicalRecord|status ===|isActive\(/', $presentation['Presentation/MedicalRecordController.php'] ?? '') === 0);
check('R-05 Persistence solo usa sentencias preparadas (sin query/exec con variables)',
    $offending($persistence, '/->(query|exec)\(/') === []);
check('R-06 los contratos de RBAC, Gobernanza y Pacientes solo se usan en Integration',
    $offending($domain + $application + $presentation + $persistence, '/MicroHis\\\\(Rbac|Gobernanza|Pacientes)\\\\/') === []);

$lines = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__), FilesystemIterator::SKIP_DOTS)) as $file) {
    if (count(file($file->getPathname())) > 400) {
        $lines[] = $file->getFilename();
    }
}
check('R-07 ningún archivo del módulo supera 400 líneas', $lines === []);
