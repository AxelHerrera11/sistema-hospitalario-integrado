<?php

declare(strict_types=1);
/* Composicion de la API REST (Semana 5): reutiliza sin cambios el Micro-HIS MVC + Repository de la Semana 4. */

require_once __DIR__ . '/../../semana-04/micro-his/autoload.php';
require_once __DIR__ . '/Router.php';
use MicroHIS\Reportes\Api\Router;
use MicroHIS\Reportes\Application\GenerarReporte;
use MicroHIS\Reportes\Domain\ReporteRepository;
use MicroHIS\Reportes\Presentation\ReporteController;

return static function (ReporteRepository $repositorio): Router {
    $controlador = new ReporteController(new GenerarReporte($repositorio));
    $router = new Router();
    $router->agregar('POST', '/reportes', [$controlador, 'generar']);
    $router->agregar('GET', '/reportes', [$controlador, 'listar']);
    return $router;
};
