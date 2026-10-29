<?php

declare(strict_types=1);

namespace MicroHis\Reportes\Persistence;

use MicroHis\Reportes\Application\EpisodeSummaryReader;
use PDO;

/** Adaptador PDO del puerto de lectura; una sola consulta agregada preparada,
 * sin seleccionar datos personales. Funciona sobre PostgreSQL (timestamptz)
 * y sobre SQLite (texto) en pruebas. */
final class PdoEpisodeSummaryReader implements EpisodeSummaryReader
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function read(string $fromInclusive, string $untilExclusive, string $serviceOrEmpty): ?array
    {
        $sql = "SELECT count(*) AS total,
            COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled
            FROM care_episodes
            WHERE type = 'inpatient' AND status IN ('closed', 'cancelled')
            AND ended_at >= ? AND ended_at < ?";
        $params = [$fromInclusive, $untilExclusive];
        if ($serviceOrEmpty !== '') {
            $sql .= ' AND service_code = ?';
            $params[] = $serviceOrEmpty;
        }

        try {
            $row = $this->pdo->prepare($sql);
            $row->execute($params);
            $agg = $row->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('lectura_no_disponible', 0, $e);
        }

        return $agg === false || $agg['total'] === null ? null : [
            'finalized' => (int) $agg['total'],
            'cancelled' => (int) $agg['cancelled'],
        ];
    }
}
