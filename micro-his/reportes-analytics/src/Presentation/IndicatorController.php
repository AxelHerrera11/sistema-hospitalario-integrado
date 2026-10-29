<?php

declare(strict_types=1);

namespace MicroHis\Reportes\Presentation;

use MicroHis\Reportes\Application\CalculateCancellationRate;
use MicroHis\Reportes\Domain\IndicatorViolation;

/** Controlador de presentacion: recibe la entrada, ejecuta el caso de uso y
 * elige la vista. Sin SQL, sin reglas de negocio ni conteos manual. */
final class IndicatorController
{
    public function __construct(private readonly CalculateCancellationRate $useCase, private readonly JsonView $view)
    {
    }

    /** @param array<string, mixed> $body */
    public function calculate(string $indicator, array $body): array
    {
        try {
            $reading = $this->useCase->execute($body);
        } catch (IndicatorViolation $e) {
            return $this->view->render(422, ['code' => $e->getMessage(), 'indicator' => $indicator]);
        } catch (\RuntimeException) {
            return $this->view->render(503, ['code' => 'LECTURA_NO_DISPONIBLE', 'indicator' => $indicator]);
        }

        return $reading->percentage === null
            ? $this->view->render(200, [
                'indicator' => $reading->indicator,
                'formula_version' => $reading->formulaVersion,
                'state' => $reading->state,
                'period' => $body['from'].' .. < '.$body['until'],
                'filters' => array_intersect_key($body, ['service_code' => 1]),
                'source' => 'HOSPITAL.care_episodes',
            ])
            : $this->view->render(200, [
                'indicator' => $reading->indicator,
                'formula_version' => $reading->formulaVersion,
                'percentage' => $reading->percentage,
                'state' => 'COMPLETADO',
                'period' => $body['from'].' .. < '.$body['until'],
                'filters' => array_intersect_key($body, ['service_code' => 1]),
                'source' => 'HOSPITAL.care_episodes',
                'computed_at' => $reading->computedAt,
            ]);
    }
}
