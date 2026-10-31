<?php

declare(strict_types=1);

namespace MicroHis\ReportesOperativos\Domain;

use MicroHis\ReportesOperativos\Application\ReportSource;

/** Primer reporte operativo: citas agrupadas por fecha, servicio (especialidad) y estado. */
final class CitasPorServicioReport implements ReportDefinition
{
    public function id(): string
    {
        return 'citas-por-servicio';
    }

    public function name(): string
    {
        return 'Citas por servicio';
    }

    public function filters(): array
    {
        return ['date_from', 'date_to', 'status', 'doctor_profile_id', 'per_page', 'order'];
    }

    public function columns(): array
    {
        return ['fecha', 'servicio', 'estado', 'total'];
    }

    public function rows(ReportSource $source, ReportFilters $filters): array
    {
        return $source->citas($filters);
    }
}
