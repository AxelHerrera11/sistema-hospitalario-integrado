<?php

declare(strict_types=1);

/* Ejemplo ejecutable S2 (LSP): el MISMO contrato de IND-RA-01/TCI-1.0 se
 * satisface por dos lectores distintos y por dos calculadores distintos.
 * Antes: la jerarquia viola LSP (precondicion mas fuerte + unidad distinta).
 * Despues: sustitucion segura respeta precondiciones, postcondiciones y unidad.
 *
 * Ejecutar: php micro-his/reportes-analytics/tests/example-lsp.php
 * Sale 0 cuando todas las comprobaciones pasan; 1 si alguna falla. */

final class EpisodeSummary
{
    public function __construct(public readonly int $finalized, public readonly int $cancelled)
    {
    }

    public function isCoherent(): bool
    {
        return $this->finalized >= 0 && $this->cancelled >= 0 && $this->cancelled <= $this->finalized;
    }
}

final class IndicatorReading
{
    private function __construct(
        public readonly ?float $percentage,
        public readonly bool $completed,
        public readonly ?string $state,
    ) {
    }

    public static function completed(float $percentage): self
    {
        return new self($percentage, true, null);
    }

    public static function withoutValue(string $state): self
    {
        return new self(null, false, $state);
    }
}

/* ---- ANTES: jerarquia rigida (LSP violado) ---- */
abstract class RigidCancellationCalculator
{
    public function __construct(public readonly EpisodeSummary $summary)
    {
    }

    abstract public function calculate(): string;
}

final class InpatientCancellationRate extends RigidCancellationCalculator
{
    public function calculate(): string
    {
        if ($this->summary->finalized === 0) {
            throw new RuntimeException('no hay datos'); // precondicion extra
        }

        return number_format(100 * $this->summary->cancelled / $this->summary->finalized, 2) . '%'; // unidad decente
    }
}

final class CancelledRatioOfInpatient extends RigidCancellationCalculator
{
    public function calculate(): string
    {
        if ($this->summary->finalized === 0) {
            return '0.00'; // mismo periodo LSP prohibido: postcondicion incompatible
        }

        return number_format($this->summary->cancelled / $this->summary->finalized, 4); // unidad distinta
    }
}

/* ---- DESPUES: contrato unico ---- */
interface SummaryReader
{
    /** @return EpisodeSummary|null Lectura coherente o null sin datos del periodo. */
    public function read(string $serviceCodeOrEmpty): ?EpisodeSummary;
}

interface PercentageCalculator
{
    /** Lectura completa o estado sin porcentaje; mismo contrato para todo calculador. */
    public function calculate(string $periodStart, string $periodEndInclusiveUpperExclusive, string $serviceCodeOrEmpty): IndicatorReading;
}

class CancellationRateCalculator implements PercentageCalculator
{
    public function __construct(private readonly SummaryReader $reader)
    {
    }

    public function calculate(string $periodStart, string $periodEndExclusive, string $serviceCodeOrEmpty): IndicatorReading
    {
        $summary = $this->reader->read($serviceCodeOrEmpty);
        if ($summary === null || ! $summary->isCoherent()) {
            return IndicatorReading::withoutValue($summary === null ? 'SIN_DATOS' : 'LECTURA_INCOHERENTE');
        }
        if ($summary->finalized === 0) {
            return IndicatorReading::withoutValue('SIN_DATOS_EN_PERIODO');
        }

        return IndicatorReading::completed(round(100 * $summary->cancelled / $summary->finalized, 2, PHP_ROUND_HALF_UP));
    }
}

/** Sustitucion: filtrado por servicio SIN cambiar el contrato. El filtrado
 * vive en el SummaryReader inyectado: la sustitucion del lector conserva
 * precondiciones, postcondiciones y unidad; el calculador es el mismo. */
final class ServiceCancellationRateCalculator extends CancellationRateCalculator
{
}

/* ---- Dobles de lectura ---- */
final class ArraySummaryReader implements SummaryReader
{
    /** @param array<string, array{0: int, 1: int}> $byService */
    public function __construct(private array $byService)
    {
    }

    public function read(string $serviceCodeOrEmpty): ?EpisodeSummary
    {
        $key = $serviceCodeOrEmpty === '' ? '*' : $serviceCodeOrEmpty;

        return isset($this->byService[$key]) ? new EpisodeSummary(...$this->byService[$key]) : null;
    }
}

final class NullSummaryReader implements SummaryReader
{
    public function read(string $serviceCodeOrEmpty): ?EpisodeSummary
    {
        return null;
    }
}

/* ---- Evidencia ---- */
$checks = 0;
$fail = static function (string $label) use (&$checks) {
    fwrite(STDERR, "FAIL {$label}\n");
    exit(1);
};
$pass = static function (string $label) use (&$checks) {
    $checks++;
};

try {
    (new InpatientCancellationRate(new EpisodeSummary(0, 0)))->calculate();
    $fail('ANTES: denominador 0 con Inpatient debia lanzar');
} catch (RuntimeException) {
    $pass('ANTES: Inpatient exige denominador > 0 (precondicion extra)');
}

$before = (new CancelledRatioOfInpatient(new EpisodeSummary(0, 0)))->calculate();
$before === '0.00'
    ? $pass('ANTES: Ratio devuelve "0.00" con denominador 0 (postcondicion incompatible)')
    : $fail('ANTES: Ratio debia devolver 0.00');

($data = ['*' => [40, 10], 'HOSP-UCI' => [20, 5]]);
$base = new CancellationRateCalculator(new ArraySummaryReader($data));
$svc = new ServiceCancellationRateCalculator(new ArraySummaryReader($data));

foreach ([
    ['', 25.0],
    ['HOSP-UCI', 25.0],
] as [$code, $expected]) {
    $reading = ($code === '' ? $base : $svc)->calculate('2026-01-01T00:00:00-06:00', '2026-02-01T00:00:00-06:00', $code);
    $reading->completed === true && $reading->percentage === $expected
        ? $pass('DESPUES: TCI-1.0 % con servicio "' . ($code ?: 'todos') . "\" = {$expected}")
        : $fail('DESPUES: calculo con servicio ' . ($code ?: 'todos') . ' no coincide');
}

$noService = $base->calculate('2026-01-01T00:00:00-06:00', '2026-02-01T00:00:00-06:00', 'SIN-ESTE-SERVICIO');
$noService->completed === false && $noService->percentage === null && $noService->state === 'SIN_DATOS'
    ? $pass('DESPUES: servicio sin filas -> SIN_DATOS sin porcentaje')
    : $fail('DESPUES: servicio sin filas debia dar SIN_DATOS');

$incoherent = new CancellationRateCalculator(new ArraySummaryReader(['HOSP-URG' => [10, 12]]));
$incoherent->calculate('2026-01-01T00:00:00-06:00', '2026-02-01T00:00:00-06:00', 'HOSP-URG')->state === 'LECTURA_INCOHERENTE'
    ? $pass('DESPUES: read incoherente (N_cancelado > N_finalizado) da estado sin porcentaje')
    : $fail('DESPUES: lectura incoherente debia hacerse notar sin porcentaje');

$empty = new CancellationRateCalculator(new NullSummaryReader());
$empty->calculate('2026-01-01T00:00:00-06:00', '2026-02-01T00:00:00-06:00', '')->state === 'SIN_DATOS'
    ? $pass('DESPUES: reader-nullutch = sin datos del periodo, sin porcentaje')
    : $fail('DESPUES: reader-null debia considerar SIN_DATOS (sin porcentaje)');

echo "LSP ejemplo OK: {$checks} comprobaciones; misma unidad (porcentaje con 2 decimales), misma unidad ";
echo "para sustituciones y no lanzamiento con denominador 0.\n";
exit(0);

