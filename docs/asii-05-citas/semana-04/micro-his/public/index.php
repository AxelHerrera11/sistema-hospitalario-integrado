<?php

declare(strict_types=1);
/* Entrada MVC del Micro-HIS Citas (ASII-05, Semana 4). Uso: php -S localhost:8080 -t public */

require __DIR__ . '/../autoload.php';

use MicroHIS\Citas\Application\AgendarCita;
use MicroHIS\Citas\Infrastructure\CitaRepositoryInMemory;
use MicroHIS\Citas\Infrastructure\CitaRepositoryPDO;
use MicroHIS\Citas\Presentation\CitaController;

$dsn = getenv('MICRO_HIS_DSN');
$repo = ($dsn === false || $dsn === '') ? new CitaRepositoryInMemory() : new CitaRepositoryPDO(new \PDO($dsn));
$respuesta = (new CitaController(new AgendarCita($repo)))->agendar($_GET);

http_response_code($respuesta['status']);
header('Content-Type: application/json');
echo json_encode($respuesta['body'], JSON_UNESCAPED_UNICODE), "\n";
