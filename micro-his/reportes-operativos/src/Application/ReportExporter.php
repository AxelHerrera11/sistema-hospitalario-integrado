<?php

declare(strict_types=1);

namespace MicroHis\ReportesOperativos\Application;

use MicroHis\ReportesOperativos\Domain\ReportDefinition;

/** Puerto de exportación: hoy CSV; un PDF futuro se agrega como otra implementación. */
interface ReportExporter
{
    public function supports(string $format): bool;

    /** @param list<array<string, string>> $rows */
    public function export(ReportDefinition $definition, array $rows): string;
}
