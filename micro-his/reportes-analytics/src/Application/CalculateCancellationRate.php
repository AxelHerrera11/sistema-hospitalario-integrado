<?php

declare(strict_types=1);

namespace MicroHis\Reportes\Application;

use MicroHis\Reportes\Domain\IndicatorReading;
use MicroHis\Reportes\Domain\IndicatorViolation;
use MicroHis\Reportes\Domain\Period;

/** Caso de uso: calcular y justificar lectura y calculo IND-RA-01/TCI-1.0,
 * con validez semantica de la Lectura, sin mezclar estados y con una lectura
 * agregada por periodo. Deteccion de denominador 0 y lectura incoherente. */
final class CalculateCancellationRate
{
    public const INDICATOR = 'IND-RA-01';
    public const FORMULA_VERSION = 'TCI-1.0';

    public function __construct(private readonly EpisodeSummaryReader $reader)
    {
    }

    public function execute(array $input): IndicatorReading
    {
        is_string($input['from'] ?? null) && is_string($input['until'] ?? null)
            || throw new IndicatorViolation('entrada_invalida');
        $service = $input['service_code'] ?? '';
        is_string($service) || throw new IndicatorViolation('entrada_invalida');

        $period = Period::create($input['from'], $input['until']);
        $counts = $this->reader->read($period->lower(), $period->upper(), $service);
        $completedAt = (new \DateTimeImmutable('now'))->format('Y-m-d\TH:i:sP');
        if ($counts === null) {
            return IndicatorReading::withoutValue(self::INDICATOR, self::FORMULA_VERSION, 'SIN_DATOS_EN_PERIODO');
        }

        is_int($counts['finalized'] ?? null) && is_int($counts['cancelled'] ?? null)
            && $counts['finalized'] >= 0 && $counts['cancelled'] >= 0
            && $counts['cancelled'] <= $counts['finalized']
            || throw new \RuntimeException('lectura_incoherente_del_periodo');

        if ($counts['finalized'] === 0) {
            return IndicatorReading::withoutValue(self::INDICATOR, self::FORMULA_VERSION, 'SIN_DATOS_EN_PERIODO');
        }

        return IndicatorReading::completed(self::INDICATOR, self::FORMULA_VERSION, round(100 * $counts['cancelled'] / $counts['finalized'], 2, PHP_ROUND_HALF_UP), $completedAt);
    }
}

