<?php

declare(strict_types=1);

namespace MicroHis\CatalogoLab\Persistence;

use MicroHis\CatalogoLab\Application\LabTestRepository;
use MicroHis\CatalogoLab\Application\PersistenceFailure;
use MicroHis\CatalogoLab\Domain\LabTest;
use Closure;
use PDO;
use PDOException;

/** Adaptador PDO de lab_tests y local_access_audits. Solo sentencias preparadas. */
final class PdoLabTestRepository implements LabTestRepository
{
    private ?PDO $connection = null;

    /** @param PDO|Closure(): PDO $pdo con un Closure la conexión se abre en el primer uso: una base caída es PersistenceFailure, no un error al construir */
    public function __construct(private readonly PDO|Closure $pdo) {}

    public function latestVersionNo(string $code): int
    {
        return (int) $this->read('SELECT MAX(version_no) FROM lab_tests WHERE code = ?', [$code])->fetchColumn();
    }

    public function hasActiveOverlap(string $code, string $validFrom): bool
    {
        return $this->read("SELECT 1 FROM lab_tests WHERE code = ? AND status = 'active' AND (valid_to IS NULL OR valid_to > ?)",
            [$code, $validFrom])->fetchColumn() !== false;
    }

    public function save(LabTest $t, string $actor): void
    {
        try {
            $this->pdo()->beginTransaction();
            $this->query('INSERT INTO lab_tests (lab_test_id, code, name, specimen_type, min_volume_ml, result_type, unit, reference_low,
                reference_high, critical_low, critical_high, version_no, valid_from, created_by_user_ref_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $t->id, $t->code, $t->name, $t->specimenType, $t->minVolumeMl, $t->resultType, $t->unit, $t->referenceLow,
                $t->referenceHigh, $t->criticalLow, $t->criticalHigh, $t->versionNo, $t->validFrom, $actor,
            ]);
            $this->query("INSERT INTO local_access_audits (correlation_id, actor_user_ref_id, module, action, resource_type, resource_id, occurred_at)
                VALUES (?, ?, 'catalogo_laboratorio', 'prueba_lab.registrada', 'lab_test', ?, ?)", [bin2hex(random_bytes(16)), $actor, $t->id, date(DATE_ATOM)]);
            $this->pdo()->commit();
        } catch (PDOException $error) {
            $this->connection?->inTransaction() && $this->connection->rollBack();
            // Solo la unicidad código-versión o la exclusión de vigencias (PostgreSQL 23P01) significan «otra alta ganó».
            throw new PersistenceFailure(in_array((string) $error->getCode(), ['23000', '23505', '23P01'], true), $error);
        }
    }

    public function findActive(string $date, string $search): array
    {
        $sql = "SELECT code, name, specimen_type, result_type, unit, reference_low, reference_high, version_no, valid_from
            FROM lab_tests WHERE status = 'active' AND valid_from <= ? AND (valid_to IS NULL OR valid_to > ?)
            AND (? = '' OR code = UPPER(?) OR LOWER(name) LIKE LOWER(?)) ORDER BY code";

        return $this->read($sql, [$date, $date, $search, $search, "%{$search}%"])->fetchAll(PDO::FETCH_ASSOC);
    }

    private function query(string $sql, array $params): \PDOStatement
    {
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /** Las lecturas fuera de save() también traducen la falla de la base. */
    private function read(string $sql, array $params): \PDOStatement
    {
        try {
            return $this->query($sql, $params);
        } catch (PDOException $error) {
            throw new PersistenceFailure(false, $error);
        }
    }

    private function pdo(): PDO
    {
        if ($this->connection === null) {
            $this->connection = $this->pdo instanceof Closure ? ($this->pdo)() : $this->pdo;
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return $this->connection;
    }
}
