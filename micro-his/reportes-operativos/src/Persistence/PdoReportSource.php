<?php

declare(strict_types=1);

namespace MicroHis\ReportesOperativos\Persistence;

use Closure;
use MicroHis\ReportesOperativos\Application\ReportSource;
use MicroHis\ReportesOperativos\Domain\ReportFailure;
use MicroHis\ReportesOperativos\Domain\ReportFilters;
use PDO;
use Throwable;

/**
 * Fuente PDO sobre la base HOSPITAL (`appointments` + `specialties`). Solo lectura y sentencias
 * preparadas; la conexión es perezosa para que una base caída responda 503 y no 500.
 */
final class PdoReportSource implements ReportSource
{
    /** @param Closure(): PDO $conexion */
    public function __construct(private readonly Closure $conexion)
    {
    }

    public function citas(ReportFilters $filters): array
    {
        $sql = "SELECT to_char(a.starts_at, 'YYYY-MM-DD') AS fecha, s.name AS servicio, a.status AS estado, count(*)::text AS total
                FROM appointments a JOIN specialties s ON s.specialty_id = a.specialty_id WHERE true";
        $parametros = [];
        if ($filters->dateFrom !== null) {
            $sql .= ' AND a.starts_at >= CAST(:desde AS date)';
            $parametros['desde'] = $filters->dateFrom;
        }
        if ($filters->dateTo !== null) {
            $sql .= " AND a.starts_at < CAST(:hasta AS date) + interval '1 day'";
            $parametros['hasta'] = $filters->dateTo;
        }
        foreach (['status' => 'a.status', 'doctor_profile_id' => 'a.doctor_profile_id'] as $filtro => $columna) {
            if (isset($filters->extra[$filtro])) {
                $sql .= " AND {$columna}::text = :{$filtro}";
                $parametros[$filtro] = $filters->extra[$filtro];
            }
        }
        $orden = $filters->order === 'desc' ? 'DESC' : 'ASC';
        $sql .= " GROUP BY 1, 2, 3 ORDER BY 1 {$orden}, 2, 3";

        try {
            $sentencia = ($this->conexion)()->prepare($sql);
            $sentencia->execute($parametros);

            return $sentencia->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            throw new ReportFailure(503, 'report_source_unavailable', 'La fuente del reporte no está disponible.');
        }
    }
}
