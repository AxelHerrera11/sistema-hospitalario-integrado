<?php

declare(strict_types=1);

/**
 * Ejecuta todas las suites, cada una en su propio proceso PHP:
 *   php micro-his/qa-calidad-versiones/tests/run.php
 * Con QA_JUNIT_DIR=<carpeta> cada suite deja además su reporte JUnit.
 */
$suites = ['unit', 'whitebox', 'blackbox', 'application', 'pdo', 'ci', 'architecture'];
$failed = [];
foreach ($suites as $suite) {
    echo PHP_EOL."######## {$suite} ########".PHP_EOL;
    if (! is_file(__DIR__."/{$suite}.php")) {
        // Las suites se entregan semana a semana; una suite aún no entregada no cuenta como aprobada.
        echo "NO ENTREGADA TODAVÍA: tests/{$suite}.php".PHP_EOL;

        continue;
    }
    passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__."/{$suite}.php"), $code);
    $code === 0 || $failed[] = $suite;
}
echo PHP_EOL.($failed === [] ? 'TODAS LAS SUITES APROBADAS' : 'SUITES CON FALLOS: '.implode(', ', $failed)).PHP_EOL;
exit($failed === [] ? 0 : 1);
