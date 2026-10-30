<?php

declare(strict_types=1);

namespace MicroHIS\Citas\Infrastructure;

use MicroHIS\Citas\Domain\Cita;
use MicroHIS\Citas\Domain\CitaRepository;
use MicroHIS\Citas\Domain\ErrorPersistenciaException;
use PDO;

/** Adaptador Repository con PDO y sentencias preparadas. Sin reglas de negocio. */
final class CitaRepositoryPDO implements CitaRepository
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS citas (id TEXT PRIMARY KEY, paciente TEXT, doctor TEXT, desde TEXT, hasta TEXT, estado TEXT)');
    }

    public function todas(): array
    {
        try {
            $filas = $this->pdo->query('SELECT id, paciente, doctor, desde, hasta, estado FROM citas')->fetchAll();
        } catch (\PDOException $e) {
            throw new ErrorPersistenciaException('error_persistencia: ' . $e->getMessage(), 0, $e);
        }
        return array_map(static fn (array $f): Cita => Cita::agendar($f['id'], $f['paciente'], $f['doctor'], $f['desde'], $f['hasta']), $filas);
    }

    public function guardar(Cita $cita): void
    {
        try {
            $sql = 'INSERT INTO citas (id, paciente, doctor, desde, hasta, estado) VALUES (:id, :paciente, :doctor, :desde, :hasta, :estado)';
            $this->pdo->prepare($sql)->execute([
                ':id' => $cita->id, ':paciente' => $cita->paciente, ':doctor' => $cita->doctor,
                ':desde' => $cita->desde, ':hasta' => $cita->hasta, ':estado' => $cita->estado,
            ]);
        } catch (\PDOException $e) {
            throw new ErrorPersistenciaException('error_persistencia: ' . $e->getMessage(), 0, $e);
        }
    }
}
