<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Persistence;

use DateTimeImmutable;
use MicroHis\TrasladosAltas\Application\ConfirmacionIncierta;
use MicroHis\TrasladosAltas\Application\FalloPersistencia;
use MicroHis\TrasladosAltas\Application\RegistroInternacion;
use MicroHis\TrasladosAltas\Domain\CamaDestino;
use MicroHis\TrasladosAltas\Domain\EstadoInternacion;
use MicroHis\TrasladosAltas\Domain\PlanTraslado;
use PDO;
use PDOException;
use PDOStatement;

/**
 * Adaptador PostgreSQL de HOSPITAL (care_episodes, bed_stays, beds). Todo valor viaja como parámetro de una sentencia
 * preparada. Bloquea con FOR UPDATE el episodio, su estancia abierta y la cama destino dentro de la transacción.
 */
final class PdoRegistroInternacion implements RegistroInternacion
{
    private const MARCA = 'Y-m-d H:i:s.uP';

    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function iniciar(): void
    {
        $this->seguro(fn () => $this->pdo->beginTransaction());
    }

    public function leerPorEstancia(string $estanciaId): ?EstadoInternacion
    {
        $episodio = $this->fila('SELECT e.care_episode_id, e.type, e.status, e.started_at FROM bed_stays s
            JOIN care_episodes e ON e.care_episode_id = s.care_episode_id WHERE s.bed_stay_id = :id FOR UPDATE OF e', ['id' => $estanciaId]);
        if ($episodio === null) {
            return null;
        }
        $abierta = $this->fila('SELECT bed_stay_id, bed_id, started_at FROM bed_stays WHERE care_episode_id = :ep AND ended_at IS NULL FOR UPDATE',
            ['ep' => $episodio['care_episode_id']]);

        return new EstadoInternacion($episodio['care_episode_id'], $episodio['type'], $episodio['status'], new DateTimeImmutable($episodio['started_at']),
            $abierta['bed_stay_id'] ?? null, $abierta['bed_id'] ?? null, $abierta === null ? null : new DateTimeImmutable($abierta['started_at']));
    }

    public function ahora(): DateTimeImmutable
    {
        // clock_timestamp() y no now(): T se toma después de obtener los bloqueos, no al iniciar la transacción.
        return new DateTimeImmutable($this->fila('SELECT clock_timestamp() AS t', [])['t']);
    }

    public function leerCama(string $camaId, DateTimeImmutable $t): ?CamaDestino
    {
        $cama = $this->fila('SELECT status, NOT EXISTS (SELECT 1 FROM bed_stays s WHERE s.bed_id = b.bed_id
                AND tstzrange(s.started_at, COALESCE(s.ended_at, \'infinity\'), \'[)\') && tstzrange(CAST(:t AS timestamptz), \'infinity\', \'[)\')) AS libre
            FROM beds b WHERE b.bed_id = :id FOR UPDATE OF b', ['id' => $camaId, 't' => $t->format(self::MARCA)]);

        return $cama === null ? null : new CamaDestino($camaId, $cama['status'], (bool) $cama['libre']);
    }

    public function cerrarEstancia(string $estanciaId, DateTimeImmutable $t): void
    {
        $this->unaFila('UPDATE bed_stays SET ended_at = :t, updated_at = now() WHERE bed_stay_id = :id AND ended_at IS NULL',
            ['t' => $t->format(self::MARCA), 'id' => $estanciaId]);
    }

    public function abrirEstancia(PlanTraslado $plan, string $actorRefId): string
    {
        $fila = $this->fila('INSERT INTO bed_stays (care_episode_id, bed_id, assigned_by_user_ref_id, started_at, reason)
            VALUES (:ep, :bed, :actor, :t, :reason) RETURNING bed_stay_id', ['ep' => $plan->episodioId, 'bed' => $plan->camaDestinoId,
            'actor' => $actorRefId, 't' => $plan->t->format(self::MARCA), 'reason' => $plan->motivo]);

        return $fila['bed_stay_id'] ?? throw new FalloPersistencia('No se obtuvo el id de la estancia destino.');
    }

    public function cerrarEpisodio(string $episodioId, DateTimeImmutable $t): void
    {
        $this->unaFila("UPDATE care_episodes SET status = 'closed', ended_at = :t, updated_at = now() WHERE care_episode_id = :id AND status = 'open'",
            ['t' => $t->format(self::MARCA), 'id' => $episodioId]);
    }

    public function confirmar(): void
    {
        try {
            $this->pdo->commit();
        } catch (PDOException $error) {
            // Clases 23 (integridad), 25 (estado de transacción) y 40 (reversión): PostgreSQL informó que NO confirmó.
            // Cualquier otra falla del COMMIT (conexión perdida, sin respuesta) deja el resultado incierto.
            if (preg_match('/^(23|25|40)/', (string) $error->getCode()) === 1) {
                throw new FalloPersistencia('HOSPITAL rechazó el COMMIT.', previous: $error);
            }
            throw new ConfirmacionIncierta('No se sabe si HOSPITAL confirmó el COMMIT.', previous: $error);
        }
    }

    public function revertir(): void
    {
        try {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
        } catch (PDOException) {
            // Si la conexión se perdió, PostgreSQL ya descartó la transacción no confirmada.
        }
    }

    /** @return array<string, mixed>|null */
    private function fila(string $sql, array $parametros): ?array
    {
        return $this->seguro(function () use ($sql, $parametros) {
            $sentencia = $this->ejecutar($sql, $parametros);

            return $sentencia->fetch(PDO::FETCH_ASSOC) ?: null;
        });
    }

    /** La escritura debe afectar exactamente una fila; si no, otra transacción cambió el dato y se revierte. */
    private function unaFila(string $sql, array $parametros): void
    {
        if ($this->seguro(fn () => $this->ejecutar($sql, $parametros)->rowCount()) !== 1) {
            throw new FalloPersistencia('La escritura no afectó exactamente una fila.');
        }
    }

    private function ejecutar(string $sql, array $parametros): PDOStatement
    {
        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute($parametros);

        return $sentencia;
    }

    private function seguro(callable $operacion): mixed
    {
        try {
            return $operacion();
        } catch (PDOException $error) {
            throw new FalloPersistencia('HOSPITAL no ejecutó la sentencia.', previous: $error);
        }
    }
}
