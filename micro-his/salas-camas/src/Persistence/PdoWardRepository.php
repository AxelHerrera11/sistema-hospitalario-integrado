<?php
declare(strict_types=1);

namespace MicroHis\SalasCamas\Persistence;

use MicroHis\SalasCamas\Application\{PersistenceFailure, WardRepository};
use MicroHis\SalasCamas\Domain\Ward;
use PDO;
use PDOException;

final class PdoWardRepository implements WardRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function findByCode(string $code): ?Ward
    {
        try {
            $s = $this->pdo->prepare('SELECT ward_id, code, name, location, status FROM wards WHERE code = :code');
            $s->execute([':code' => $code]);
            $r = $s->fetch(PDO::FETCH_ASSOC);
            return $r ? new Ward((string)$r['ward_id'], (string)$r['code'], (string)$r['name'], (string)$r['location'], (string)$r['status']) : null;
        } catch (PDOException $e) {
            throw new PersistenceFailure('Error al consultar sala.', 0, $e);
        }
    }

    public function insertWard(Ward $ward): Ward
    {
        try {
            $s = $this->pdo->prepare('INSERT INTO wards (code, name, location, status) VALUES (:code, :name, :location, :status) RETURNING ward_id');
            $s->execute([':code' => $ward->code, ':name' => $ward->name, ':location' => $ward->location, ':status' => $ward->status]);
            return new Ward((string)$s->fetchColumn(), $ward->code, $ward->name, $ward->location, $ward->status);
        } catch (PDOException $e) {
            throw new PersistenceFailure('Error al crear sala.', 0, $e);
        }
    }
}
