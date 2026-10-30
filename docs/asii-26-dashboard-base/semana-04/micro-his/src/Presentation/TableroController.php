<?php

declare(strict_types=1);

namespace MicroHIS\Dashboard\Presentation;

use MicroHIS\Dashboard\Application\ObtenerTablero;

// Controlador MVC. No contiene SQL ni reglas: delega en el caso de uso y arma la respuesta HTTP.
final class TableroController
{
    public function __construct(private readonly ObtenerTablero $obtenerTablero)
    {
    }

    public function mostrar(array $entrada): array
    {
        $periodo = (string) ($entrada['periodo'] ?? '');
        if ($periodo === '') {
            return ['status' => 422, 'body' => ['error' => 'periodo_requerido']];
        }
        return ['status' => 200, 'body' => $this->obtenerTablero->ejecutar($periodo)];
    }
}
