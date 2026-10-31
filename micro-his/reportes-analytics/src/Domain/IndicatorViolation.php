<?php

declare(strict_types=1);

namespace MicroHis\Reportes\Domain;

/** Violacion de regla de entrada o de dominio del indicador.
 * El codigo de contrato viaja en el mensaje (coincidente con el JSON de 422). */
final class IndicatorViolation extends \RuntimeException
{
}
