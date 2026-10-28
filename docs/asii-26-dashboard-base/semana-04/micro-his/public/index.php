<?php

declare(strict_types=1);
/* Entrada MVC del Micro-HIS Dashboard (ASII-26, Semana 4). Uso: php -S localhost:8080 -t public */

require __DIR__ . '/../autoload.php';

use MicroHIS\Dashboard\Application\ObtenerTablero;
use MicroHIS\Dashboard\Infrastructure\IndicadorRepositoryInMemory;
use MicroHIS\Dashboard\Infrastructure\IndicadorRepositoryPDO;
use MicroHIS\Dashboard\Presentation\TableroController;

$dsn = getenv('MICRO_HIS_DSN');
$repo = ($dsn === false || $dsn === '') ? new IndicadorRepositoryInMemory() : new IndicadorRepositoryPDO(new \PDO($dsn));
$controlador = new TableroController(new ObtenerTablero($repo));
$respuesta = $controlador->mostrar($_GET);

http_response_code($respuesta['status']);
header('Content-Type: application/json');
echo json_encode($respuesta['body'], JSON_UNESCAPED_UNICODE), "\n";
