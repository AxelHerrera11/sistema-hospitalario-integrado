<?php

declare(strict_types=1);
/* Composicion de la API REST (Semana 5): reutiliza sin cambios el Micro-HIS MVC + Repository de la Semana 4. */

require_once __DIR__ . '/../../semana-04/micro-his/autoload.php';
require_once __DIR__ . '/Router.php';
use MicroHIS\Documentacion\Api\Router;
use MicroHIS\Documentacion\Application\ConsultarDocumentos;
use MicroHIS\Documentacion\Application\PublicarDocumento;
use MicroHIS\Documentacion\Domain\DocumentoRepository;
use MicroHIS\Documentacion\Presentation\DocumentoController;

return static function (DocumentoRepository $repositorio): Router {
    $controlador = new DocumentoController(new PublicarDocumento($repositorio), new ConsultarDocumentos($repositorio));
    $router = new Router();
    $router->agregar('POST', '/documentos', [$controlador, 'publicar'], ['titulo']);
    $router->agregar('GET', '/documentos', [$controlador, 'listar'], ['rol']);
    return $router;
};
