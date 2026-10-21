<?php

declare(strict_types=1);

// Dependencias entre capas: Domain no conoce otras capas ni PDO; Application no usa PDO ni conoce Persistence o Presentation.
$reglas = [
    'Domain' => '/MicroHis.TrasladosAltas.(Application|Persistence|Presentation)|\bPDO\b/',
    'Application' => '/MicroHis.TrasladosAltas.(Persistence|Presentation)|\bPDO\b/',
];
foreach ($reglas as $capa => $prohibido) {
    $violaciones = [];
    foreach (glob(__DIR__ . "/../src/{$capa}/*.php") as $archivo) {
        if (preg_match($prohibido, (string) file_get_contents($archivo)) === 1) {
            $violaciones[] = basename($archivo);
        }
    }
    check("{$capa} respeta sus dependencias" . ($violaciones ? ': ' . implode(', ', $violaciones) : ''), $violaciones === []);
}
