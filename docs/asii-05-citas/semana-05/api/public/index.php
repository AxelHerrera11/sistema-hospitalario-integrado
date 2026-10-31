<?php

declare(strict_types=1);
/* Entrada REST del Micro-HIS Citas (Semana 5). Uso: php -S localhost:8081 -t api/public */

$construir = require __DIR__ . '/../rutas.php';

use MicroHIS\Citas\Infrastructure\CitaRepositoryInMemory;
use MicroHIS\Citas\Infrastructure\CitaRepositoryPDO;

$dsn = getenv('MICRO_HIS_DSN');
$repositorio = ($dsn === false || $dsn === '') ? new CitaRepositoryInMemory() : new CitaRepositoryPDO(new \PDO($dsn));
$ruta = (string) preg_replace('#^/api/v1#', '', (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$cuerpo = json_decode((string) file_get_contents('php://input'), true);
$entrada = array_merge($_GET, is_array($cuerpo) ? $cuerpo : []);
$respuesta = $construir($repositorio)->despachar($_SERVER['REQUEST_METHOD'] ?? 'GET', $ruta, $entrada);

http_response_code($respuesta['status']);
header('Content-Type: application/json');
echo json_encode($respuesta['body'], JSON_UNESCAPED_UNICODE), "\n";
