<?php

declare(strict_types=1);
/* Composicion de la API REST (Semana 5): reutiliza sin cambios el Micro-HIS MVC + Repository de la Semana 4. */

require_once __DIR__ . '/../../semana-04/micro-his/autoload.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/ListarCitas.php';

use MicroHIS\Citas\Api\Router;
use MicroHIS\Citas\Application\AgendarCita;
use MicroHIS\Citas\Application\ListarCitas;
use MicroHIS\Citas\Domain\CitaRepository;
use MicroHIS\Citas\Presentation\CitaController;

return static function (CitaRepository $repositorio): Router {
    $controlador = new CitaController(new AgendarCita($repositorio));
    $listar = new ListarCitas($repositorio);
    $router = new Router();
    $router->agregar('POST', '/citas', [$controlador, 'agendar'], ['paciente', 'doctor', 'desde', 'hasta']);
    $router->agregar('GET', '/citas', static fn (array $e): array => [
        'status' => 200,
        'body' => $listar->ejecutar((string) ($e['paciente'] ?? '')),
    ]);
    return $router;
};
