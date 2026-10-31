<?php

declare(strict_types=1);

namespace MicroHIS\Dashboard\Infrastructure;

use MicroHIS\Dashboard\Domain\ErrorPersistenciaException;
use MicroHIS\Dashboard\Domain\Indicador;
use MicroHIS\Dashboard\Domain\IndicadorRepository;
use PDO;

// Adaptador Repository con PDO y sentencias preparadas. Sin reglas de negocio.
final class IndicadorRepositoryPDO implements IndicadorRepository
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS indicadores (clave TEXT, periodo TEXT, valor REAL, meta REAL)');
    }

    public function porPeriodo(string $periodo): array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT clave, periodo, valor, meta FROM indicadores WHERE periodo = :periodo');
            $stmt->execute([':periodo' => $periodo]);
            $filas = $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw new ErrorPersistenciaException('error_persistencia: ' . $e->getMessage(), 0, $e);
        }
        return array_map(fn (array $f): Indicador => $this->hidratar($f), $filas);
    }

    public function guardar(Indicador $indicador): void
    {
        try {
            $sql = 'INSERT INTO indicadores (clave, periodo, valor, meta) VALUES (:clave, :periodo, :valor, :meta)';
            $this->pdo->prepare($sql)->execute([
                ':clave' => $indicador->clave, ':periodo' => $indicador->periodo,
                ':valor' => $indicador->valor, ':meta' => $indicador->meta,
            ]);
        } catch (\PDOException $e) {
            throw new ErrorPersistenciaException('error_persistencia: ' . $e->getMessage(), 0, $e);
        }
    }

    private function hidratar(array $fila): Indicador
    {
        return Indicador::registrar((string) $fila['clave'], (string) $fila['periodo'], (float) $fila['valor'], (float) $fila['meta']);
    }
}
