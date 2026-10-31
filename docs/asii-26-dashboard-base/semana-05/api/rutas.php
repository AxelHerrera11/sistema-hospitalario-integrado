<?php

declare(strict_types=1);
/* Composicion de la API REST (Semana 5): reutiliza sin cambios el Micro-HIS MVC + Repository de la Semana 4. */

require_once __DIR__ . '/../../semana-04/micro-his/autoload.php';
require_once __DIR__ . '/Router.php';
use MicroHIS\Dashboard\Api\Router;
use MicroHIS\Dashboard\Application\ObtenerTablero;
use MicroHIS\Dashboard\Domain\IndicadorRepository;
use MicroHIS\Dashboard\Presentation\TableroController;

return static function (IndicadorRepository $repositorio): Router {
    $controlador = new TableroController(new ObtenerTablero($repositorio));
    $router = new Router();
    $router->agregar('GET', '/tablero', [$controlador, 'mostrar']);
    return $router;
};
