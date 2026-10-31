<?php

declare(strict_types=1);

namespace MicroHIS\Reportes\Infrastructure;

use MicroHIS\Reportes\Domain\ErrorPersistenciaException;
use MicroHIS\Reportes\Domain\Reporte;
use MicroHIS\Reportes\Domain\ReporteRepository;
use PDO;

// Adaptador Repository con PDO y sentencias preparadas. Sin reglas de negocio.
final class ReporteRepositoryPDO implements ReporteRepository
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS reportes (id TEXT PRIMARY KEY, periodo TEXT, contenido TEXT)');
    }

    public function porPeriodo(string $periodo): array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT id, periodo, contenido FROM reportes WHERE periodo = :periodo');
            $stmt->execute([':periodo' => $periodo]);
            $filas = $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw new ErrorPersistenciaException('error_persistencia: ' . $e->getMessage(), 0, $e);
        }
        return array_map(fn (array $f): Reporte => $this->hidratar($f), $filas);
    }

    public function guardar(Reporte $reporte): void
    {
        try {
            $sql = 'INSERT INTO reportes (id, periodo, contenido) VALUES (:id, :periodo, :contenido)';
            $this->pdo->prepare($sql)->execute([
                ':id' => $reporte->id, ':periodo' => $reporte->periodo, ':contenido' => json_encode($reporte->contenido),
            ]);
        } catch (\PDOException $e) {
            throw new ErrorPersistenciaException('error_persistencia: ' . $e->getMessage(), 0, $e);
        }
    }

    private function hidratar(array $fila): Reporte
    {
        return Reporte::generar((string) $fila['id'], (string) $fila['periodo'], json_decode((string) $fila['contenido'], true) ?: []);
    }
}
