<?php

declare(strict_types=1);

// Ejecuta todas las suites sin base de datos: php micro-his/traslados-altas/tests/run.php

require __DIR__ . '/harness.php';

require __DIR__ . '/solicitudes.php';
require __DIR__ . '/reglas.php';
require __DIR__ . '/destino.php';
require __DIR__ . '/planes.php';
require __DIR__ . '/resultados.php';
require __DIR__ . '/arquitectura.php';
require __DIR__ . '/protocolo.php';
require __DIR__ . '/traslado.php';
require __DIR__ . '/alta.php';
require __DIR__ . '/presentacion.php';
require __DIR__ . '/contrato-inmemory.php';
require __DIR__ . '/consulta.php';
require __DIR__ . '/http.php';
require __DIR__ . '/ejecutor.php';
require __DIR__ . '/limites.php';

finish();
