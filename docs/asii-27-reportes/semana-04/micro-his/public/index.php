<?php

declare(strict_types=1);
/* Entrada MVC del Micro-HIS Reportes (ASII-27, Semana 4). Uso: php -S localhost:8080 -t public */

require __DIR__ . '/../autoload.php';

use MicroHIS\Reportes\Application\GenerarReporte;
use MicroHIS\Reportes\Infrastructure\ReporteRepositoryInMemory;
use MicroHIS\Reportes\Infrastructure\ReporteRepositoryPDO;
use MicroHIS\Reportes\Presentation\ReporteController;

$dsn = getenv('MICRO_HIS_DSN');
$repo = ($dsn === false || $dsn === '') ? new ReporteRepositoryInMemory() : new ReporteRepositoryPDO(new \PDO($dsn));
$controlador = new ReporteController(new GenerarReporte($repo));
$accion = (string) ($_GET['accion'] ?? 'listar');
$respuesta = $accion === 'generar' ? $controlador->generar($_GET) : $controlador->listar($_GET);

http_response_code($respuesta['status']);
header('Content-Type: application/json');
echo json_encode($respuesta['body'], JSON_UNESCAPED_UNICODE), "\n";
