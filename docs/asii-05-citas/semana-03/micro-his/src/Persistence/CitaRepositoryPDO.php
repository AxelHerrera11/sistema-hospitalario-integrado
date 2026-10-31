<?php

declare(strict_types=1);

namespace MicroHIS\Citas\Persistence;

use MicroHIS\Citas\Domain\Cita;
use MicroHIS\Citas\Domain\CitaRepository;
use MicroHIS\Citas\Domain\ErrorPersistenciaException;

/**
 * Adaptador PDO. Usa siempre sentencias preparadas; nunca concatena valores.
 */
final class CitaRepositoryPDO implements CitaRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS citas (id TEXT PRIMARY KEY, paciente TEXT NOT NULL, doctor TEXT NOT NULL, desde TEXT NOT NULL, hasta TEXT NOT NULL, estado TEXT NOT NULL)');
    }

    public function todas(): array
    {
        try {
            $filas = $this->pdo->query('SELECT id, paciente, doctor, desde, hasta FROM citas')->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new ErrorPersistenciaException('No se pudieron leer las citas', 0, $e);
        }
        return array_map(
            static fn (array $f): Cita => Cita::crear((string) $f['id'], (string) $f['paciente'], (string) $f['doctor'], (string) $f['desde'], (string) $f['hasta']),
            $filas
        );
    }

    public function guardar(Cita $cita): void
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO citas (id, paciente, doctor, desde, hasta, estado) VALUES (:id, :paciente, :doctor, :desde, :hasta, :estado)');
            $stmt->execute([
                ':id' => $cita->id,
                ':paciente' => $cita->paciente,
                ':doctor' => $cita->doctor,
                ':desde' => $cita->desde,
                ':hasta' => $cita->hasta,
                ':estado' => $cita->estado(),
            ]);
        } catch (\PDOException $e) {
            throw new ErrorPersistenciaException('No se pudo guardar la cita', 0, $e);
        }
    }

    public function buscar(string $id): ?Cita
    {
        try {
            $stmt = $this->pdo->prepare('SELECT id, paciente, doctor, desde, hasta FROM citas WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new ErrorPersistenciaException('No se pudo buscar la cita', 0, $e);
        }
        if ($fila === false) {
            return null;
        }
        return Cita::crear((string) $fila['id'], (string) $fila['paciente'], (string) $fila['doctor'], (string) $fila['desde'], (string) $fila['hasta']);
    }
}
