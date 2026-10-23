<?php

declare(strict_types=1);

namespace MicroHis\ReportesOperativos\Domain;

use MicroHis\ReportesOperativos\Application\ReportSource;

/**
 * Contrato de un reporte. Agregar uno nuevo es crear otra implementación y
 * registrarla en el catálogo: ni el exportador ni el controlador cambian.
 */
interface ReportDefinition
{
    public function id(): string;

    public function name(): string;

    /** @return list<string> filtros aceptados */
    public function filters(): array;

    /** @return list<string> columnas del CSV y del resumen */
    public function columns(): array;

    /** @return list<array<string, string>> */
    public function rows(ReportSource $source, ReportFilters $filters): array;
}
