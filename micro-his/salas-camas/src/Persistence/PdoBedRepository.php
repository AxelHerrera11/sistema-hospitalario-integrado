<?php
declare(strict_types=1);

namespace MicroHis\SalasCamas\Persistence;

use MicroHis\SalasCamas\Application\{BedRepository, PersistenceFailure};
use MicroHis\SalasCamas\Domain\Bed;
use Closure;
use PDO;
use PDOException;

final class PdoBedRepository implements BedRepository
{
    public function __construct(private readonly PDO|Closure $connection) {}

    public function listByWardId(string $wardId): array
    {
        try {
            $s = $this->pdo()->prepare(
                'SELECT bed_id, ward_id, code, type, status FROM beds WHERE ward_id = :ward_id ORDER BY code, bed_id',
            );
            $s->execute([':ward_id' => $wardId]);
            return array_map(
                static fn (array $r): Bed => new Bed(
                    (string) $r['bed_id'],
                    (string) $r['ward_id'],
                    (string) $r['code'],
                    (string) $r['type'],
                    (string) $r['status'],
                ),
                $s->fetchAll(PDO::FETCH_ASSOC),
            );
        } catch (PDOException $e) {
            throw new PersistenceFailure('Error al consultar camas de la ward.', 0, $e);
        }
    }

    public function insertBed(Bed $bed): Bed
    {
        try {
            $s = $this->pdo()->prepare('INSERT INTO beds (ward_id, code, type, status) VALUES (:ward, :code, :type, :status) RETURNING bed_id');
            $s->execute([':ward' => $bed->wardId, ':code' => $bed->code, ':type' => $bed->type, ':status' => $bed->status]);
            return new Bed((string)$s->fetchColumn(), $bed->wardId, $bed->code, $bed->type, $bed->status);
        } catch (PDOException $e) {
            throw new PersistenceFailure('Error al crear cama.', 0, $e);
        }
    }

    private function pdo(): PDO
    {
        return $this->connection instanceof PDO ? $this->connection : ($this->connection)();
    }
}
