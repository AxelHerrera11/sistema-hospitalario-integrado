<?php

declare(strict_types=1);

// Suite completa del micro-HIS Expediente médico: php micro-his/expediente-medico/tests/run.php
// La descubre también el CI (micro-his/qa-calidad-versiones/bin/micro-his-suites.php). Sale con 1 si algo falla.
require __DIR__ . '/harness.php';

// Las suites se incorporan por entregas semanales: se ejecutan las que existen y se informa cuáles faltan.
foreach (['unitarias', 'caja-blanca', 'caja-negra', 'integracion-pdo', 'arquitectura'] as $suite) {
    is_file(__DIR__ . "/$suite.php") ? require __DIR__ . "/$suite.php" : print("(suite $suite aún no incluida)" . PHP_EOL);
}

$failed = $GLOBALS['checks']['fallos'];
echo PHP_EOL . sprintf('Resumen: %d OK, %d FALLO', $GLOBALS['checks']['ok'], count($failed)) . PHP_EOL;
foreach ($failed as $name) {
    echo "  - $name" . PHP_EOL;
}
exit($failed === [] ? 0 : 1);
