<?php

declare(strict_types=1);
/* Entrada MVC del Micro-HIS Centro de Documentacion (ASII-24, Semana 4). Uso: php -S localhost:8080 -t public */

require __DIR__ . '/../autoload.php';

use MicroHIS\Documentacion\Application\ConsultarDocumentos;
use MicroHIS\Documentacion\Application\PublicarDocumento;
use MicroHIS\Documentacion\Infrastructure\DocumentoRepositoryInMemory;
use MicroHIS\Documentacion\Infrastructure\DocumentoRepositoryPDO;
use MicroHIS\Documentacion\Presentation\DocumentoController;

$dsn = getenv('MICRO_HIS_DSN');
$repo = ($dsn === false || $dsn === '') ? new DocumentoRepositoryInMemory() : new DocumentoRepositoryPDO(new \PDO($dsn));
$controlador = new DocumentoController(new PublicarDocumento($repo), new ConsultarDocumentos($repo));
$accion = (string) ($_GET['accion'] ?? 'listar');
$respuesta = $accion === 'publicar' ? $controlador->publicar($_GET) : $controlador->listar($_GET);

http_response_code($respuesta['status']);
header('Content-Type: application/json');
echo json_encode($respuesta['body'], JSON_UNESCAPED_UNICODE), "\n";
