<?php

declare(strict_types=1);

namespace MicroHis\Reportes\Presentation;

/** Enrutador minimo del Micro-HIS de reportes: expone la consulta del
 * indicador y respond 404 para lo demás. */
final class Router
{
    public function __construct(private readonly IndicatorController $indicators, private readonly JsonView $view)
    {
    }

    public function dispatch(string $method, string $path, array $body): array
    {
        if ($method === 'POST' && preg_match('#^/indicators/IND-RA-01/calculate$#', $path) === 1) {
            return $this->indicators->calculate('IND-RA-01', $body);
        }

        return $this->view->render(404, ['code' => 'ROUTE_NOT_FOUND']);
    }
}
