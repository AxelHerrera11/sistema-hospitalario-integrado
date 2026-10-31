<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MicroHis\ReportesOperativos\Application\ReportSource;
use MicroHis\ReportesOperativos\Domain\ReportDefinition;
use MicroHis\ReportesOperativos\Domain\ReportFailure;
use MicroHis\ReportesOperativos\Domain\ReportFilters;
use MicroHis\ReportesOperativos\Persistence\CsvReportExporter;

$failures = 0;
$total = 0;

function check(string $label, bool $condition): void
{
    global $failures, $total;
    $total++;
    echo ($condition ? 'PASS' : 'FAIL') . "  {$label}\n";
    $failures += $condition ? 0 : 1;
}

function failure(callable $action): ?ReportFailure
{
    try {
        $action();
    } catch (ReportFailure $error) {
        return $error;
    }

    return null;
}

$citas = [
    ['appointment_id' => 'a-1', 'starts_at' => '2026-10-03T09:00:00Z', 'status' => 'scheduled', 'reason' => 'Control'],
    ['appointment_id' => 'a-2', 'starts_at' => '2026-10-10T10:00:00Z', 'status' => 'scheduled', 'reason' => 'Control, "seguimiento"'],
];

$source = new class ($citas) implements ReportSource {
    /** @param list<array<string, string>> $citas */
    public function __construct(private readonly array $citas)
    {
    }

    public function citas(ReportFilters $filters): array
    {
        return array_values(array_filter($this->citas, static fn (array $row): bool =>
            substr($row['starts_at'], 0, 10) >= $filters->dateFrom
            && substr($row['starts_at'], 0, 10) <= $filters->dateTo));
    }
};

$definition = new class implements ReportDefinition {
    public function id(): string { return 'citas'; }
    public function name(): string { return 'Citas de prueba'; }
    public function filters(): array { return ['date_from', 'date_to']; }
    public function columns(): array { return ['appointment_id', 'starts_at', 'status', 'reason']; }
    public function rows(ReportSource $source, ReportFilters $filters): array { return $source->citas($filters); }
};

$exporter = new CsvReportExporter();
$month = ['date_from' => '2026-10-01', 'date_to' => '2026-10-31'];

$error = failure(static fn () => ReportFilters::fromQuery([]));
check('sin fechas devuelve report_filter_invalid', $error?->status === 422 && $error?->errorCode === 'report_filter_invalid');

$error = failure(static fn () => ReportFilters::fromQuery(['date_from' => '2026-02-30']));
check('fecha invalida devuelve report_filter_invalid', $error?->errorCode === 'report_filter_invalid');

$error = failure(static fn () => ReportFilters::fromQuery(['date_from' => '2026-01-01', 'date_to' => '2027-01-03']));
check('rango mayor a 366 dias devuelve report_range_too_long', $error?->status === 422 && $error?->errorCode === 'report_range_too_long');

$error = failure(static fn () => ReportFilters::fromQuery(['date_from' => '2026-01-10', 'date_to' => '2026-01-01']));
check('rango invertido devuelve report_filter_invalid', $error?->errorCode === 'report_filter_invalid');

$error = failure(static fn () => ReportFilters::fromQuery($month + ['per_page' => 101]));
check('per_page fuera de rango devuelve report_filter_invalid', $error?->errorCode === 'report_filter_invalid');

$error = failure(static fn () => ReportFilters::fromQuery($month + ['order' => 'random']));
check('order invalido devuelve report_filter_invalid', $error?->errorCode === 'report_filter_invalid');

$filters = ReportFilters::fromQuery($month);
check('filtros conservan rango y orden', $filters->dateFrom === '2026-10-01' && $filters->order === 'asc');
check('filtros exponen los valores aplicados', $filters->toArray()['date_to'] === '2026-10-31');

$rows = $definition->rows($source, $filters);
check('la fuente devuelve solo el rango pedido', count($rows) === 2);

$csv = $exporter->export($definition, $rows);
check('csv inicia con BOM UTF-8', str_starts_with($csv, "\xEF\xBB\xBF"));
check('csv incluye los encabezados', str_contains($csv, "appointment_id,starts_at,status,reason\n"));
check('csv escapa comas y comillas', str_contains($csv, '"Control, ""seguimiento"""'));
check('csv termina cada fila con salto de linea', str_ends_with($csv, "\n") && substr_count($csv, "\n") === 3);

check('el exportador acepta csv', $exporter->supports('CSV'));
check('el exportador rechaza pdf', !$exporter->supports('pdf'));

$formula = $exporter->export($definition, [['appointment_id' => 'a-3', 'starts_at' => '2026-10-11', 'status' => 'scheduled', 'reason' => '=HYPERLINK("x")']]);
check('csv neutraliza formulas (inyeccion CSV)', str_contains($formula, '"\'=HYPERLINK(""x"")"'));

$porServicio = new \MicroHis\ReportesOperativos\Domain\CitasPorServicioReport();
check('citas por servicio declara sus columnas', $porServicio->columns() === ['fecha', 'servicio', 'estado', 'total']);

echo "\n" . ($failures === 0 ? "OK: {$total} verificaciones aprobadas" : "ERROR: {$failures} de {$total} verificaciones fallaron") . "\n";
exit($failures === 0 ? 0 : 1);
