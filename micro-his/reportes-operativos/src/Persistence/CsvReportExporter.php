<?php

declare(strict_types=1);

namespace MicroHis\ReportesOperativos\Persistence;

use MicroHis\ReportesOperativos\Application\ReportExporter;
use MicroHis\ReportesOperativos\Domain\ReportDefinition;

/**
 * Exporta a CSV UTF-8 con BOM (Excel abre los acentos), encabezados desde las
 * columnas del reporte y comillas solo cuando el valor las necesita.
 */
final class CsvReportExporter implements ReportExporter
{
    public function supports(string $format): bool
    {
        return strtolower($format) === 'csv';
    }

    public function export(ReportDefinition $definition, array $rows): string
    {
        $lines = [implode(',', array_map($this->cell(...), $definition->columns()))];
        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(
                fn (string $column): string => $this->cell($row[$column] ?? ''),
                $definition->columns(),
            ));
        }

        return "\xEF\xBB\xBF" . implode("\n", $lines) . "\n";
    }

    private function cell(string $value): string
    {
        // Evita inyección de fórmulas al abrir el CSV en una hoja de cálculo (hallazgo RTF-01, semana 13).
        if (preg_match('/^[=+\-@\t\r]/', $value) === 1) {
            $value = "'" . $value;
        }
        return preg_match('/[",\n]/', $value) === 1 ? '"' . str_replace('"', '""', $value) . '"' : $value;
    }
}
