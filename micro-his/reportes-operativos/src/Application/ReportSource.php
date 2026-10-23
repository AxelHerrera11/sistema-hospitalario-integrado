<?php

declare(strict_types=1);

namespace MicroHis\ReportesOperativos\Application;

use MicroHis\ReportesOperativos\Domain\ReportFilters;

/** Puerto de datos: la implementación puede ser en memoria, PDO o un doble de prueba. */
interface ReportSource
{
    /** @return list<array<string, string>> */
    public function citas(ReportFilters $filters): array;
}
