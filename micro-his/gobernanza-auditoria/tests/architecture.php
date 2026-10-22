<?php

declare(strict_types=1);

require __DIR__ . '/harness.php';

/** Reglas de dependencias entre capas, verificadas sobre el código fuente. */
$root = __DIR__ . '/../src';
$read = static fn (string $layer): string => implode("\n", array_map('file_get_contents', glob("{$root}/{$layer}/*.php")));
$domain = $read('Domain');
$application = $read('Application');
$presentation = $read('Presentation');

section('Dependencias entre capas');
check('TC-A-01', 'Domain no depende de Application, Persistence, Presentation, Integration ni PDO', !preg_match('/Gobernanza\\\\(Application|Persistence|Presentation|Integration)|\bPDO\b/', $domain));
check('TC-A-02', 'Application no depende de Persistence, Presentation, Integration ni PDO', !preg_match('/Gobernanza\\\\(Persistence|Presentation|Integration)|\bPDO\b/', $application));
check('TC-A-03', 'Presentation no contiene SQL ni usa PDO ni adaptadores concretos', !preg_match('/\b(SELECT|INSERT|UPDATE|DELETE)\b|\bPDO\b|Gobernanza\\\\Persistence/', $presentation));
check('TC-A-04', 'ningún archivo del módulo supera 400 líneas', array_filter(
    array_merge(glob("{$root}/*.php"), glob("{$root}/*/*.php"), glob(__DIR__ . '/*.php'), glob(__DIR__ . '/../../../app/GobernanzaAuditoria/*.php') ?: []),
    static fn (string $f): bool => count(file($f)) > 400,
) === []);

summary('architecture');
