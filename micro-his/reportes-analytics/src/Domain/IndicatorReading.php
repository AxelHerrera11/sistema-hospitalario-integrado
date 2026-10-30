<?php

declare(strict_types=1);

namespace MicroHis\Reportes\Domain;

/** Un punto del indicador con trazabilidad minima. Por construccion, un
 * objeto es o un resultado calculado (porcentaje no nulo) o un estado
 * publicado sin valor; nunca un calculo parcial. */
final class IndicatorReading
{
    /**
     * @param string $indicator Identificador del indicador (IND-RA-01).
     * @param string $formulaVersion Version de formula (TCI-1.0).
     * @param float|null $percentage Porcentaje con dos decimales; null sin calculo.
     * @param string|null $state Estado publicado cuando no hay calculo.
     * @param string|null $computedAt Instante del calculo completado (ISO-8601 con zona).
     */
    public function __construct(
        public readonly string $indicator,
        public readonly string $formulaVersion,
        public readonly ?float $percentage,
        public readonly ?string $state,
        public readonly ?string $computedAt,
    ) {
    }

    public static function completed(string $indicator, string $version, float $percentage, string $computedAt): self
    {
        return new self($indicator, $version, $percentage, null, $computedAt);
    }

    public static function withoutValue(string $indicator, string $version, string $state): self
    {
        return new self($indicator, $version, null, $state, null);
    }
}
