<?php

declare(strict_types=1);

// Ejecuta todas las suites sin base de datos: php micro-his/traslados-altas/tests/run.php

require __DIR__ . '/harness.php';

require __DIR__ . '/solicitudes.php';
require __DIR__ . '/reglas.php';
require __DIR__ . '/destino.php';
require __DIR__ . '/planes.php';
require __DIR__ . '/resultados.php';

finish();
