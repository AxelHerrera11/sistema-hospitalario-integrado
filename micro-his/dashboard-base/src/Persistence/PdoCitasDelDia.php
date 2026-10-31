<?php

declare(strict_types=1);

namespace MicroHis\DashboardBase\Persistence;

use Closure;
use MicroHis\DashboardBase\Application\CitasDelDia;
use MicroHis\DashboardBase\Application\FuenteNoDisponible;
use PDO;
use Throwable;

/** Conteo de citas por estado en la base HOSPITAL (solo lectura, sentencia preparada, conexión perezosa). */
final class PdoCitasDelDia implements CitasDelDia
{
    /** @param Closure(): PDO $conexion @param (Closure(): string)|null $zona zona del hospital: el día se corta en ella, no en la de la sesión */
    public function __construct(private readonly Closure $conexion, private readonly ?Closure $zona = null)
    {
    }

    public function porEstado(string $fecha): array
    {
        try {
            $sentencia = ($this->conexion)()->prepare(
                "SELECT status, count(*) AS total FROM appointments
                 WHERE starts_at >= CAST(:desde AS date)::timestamp AT TIME ZONE :zona AND starts_at < (CAST(:hasta AS date) + 1)::timestamp AT TIME ZONE :zona2
                 GROUP BY status"
            );
            // Nombres distintos: con preparación nativa, PDO no permite repetir un parámetro nombrado.
            $zona = $this->zona === null ? 'UTC' : ($this->zona)();
            $sentencia->execute(['desde' => $fecha, 'hasta' => $fecha, 'zona' => $zona, 'zona2' => $zona]);

            return array_map('intval', $sentencia->fetchAll(PDO::FETCH_KEY_PAIR));
        } catch (Throwable $error) {
            throw new FuenteNoDisponible('La base de citas no esta disponible.', previous: $error);
        }
    }
}
