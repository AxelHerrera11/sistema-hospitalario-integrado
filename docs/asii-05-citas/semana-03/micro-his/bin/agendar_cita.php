<?php

declare(strict_types=1);

/*
 * Front controller CLI del Micro-HIS Citas.
 * Uso: php bin/agendar_cita.php --paciente=P-1 --doctor=D-1 --desde=... --hasta=...
 */

require __DIR__ . '/../bootstrap.php';

use MicroHIS\Citas\Application\AgendarCita;
use MicroHIS\Citas\Application\EntradaCita;
use MicroHIS\Citas\Persistence\CitaRepositoryPDO;

$almacen = __DIR__ . '/../storage';
if (!is_dir($almacen)) {
    mkdir($almacen, 0777, true);
}
$dsn = getenv('CITAS_DSN') ?: 'sqlite:' . $almacen . '/citas.sqlite';

$opciones = [];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([a-z_]+)=(.*)$/', $arg, $m) === 1) {
        $opciones[$m[1]] = $m[2];
    }
}
$entrada = new EntradaCita(
    (string) ($opciones['paciente'] ?? ''),
    (string) ($opciones['doctor'] ?? ''),
    (string) ($opciones['desde'] ?? ''),
    (string) ($opciones['hasta'] ?? '')
);

try {
    $r = (new AgendarCita(new CitaRepositoryPDO(new \PDO($dsn))))->ejecutar($entrada);
    echo "OK cita {$r->id} estado={$r->estado}\n";
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, "Error: {$e->getMessage()}\n");
    exit(2);
}
