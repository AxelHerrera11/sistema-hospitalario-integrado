<?php
declare(strict_types=1);

namespace MicroHis\SalasCamas\Persistence;

use MicroHis\SalasCamas\Application\{BedRepository, PersistenceFailure};
use MicroHis\SalasCamas\Domain\Bed;
use PDO;
use PDOException;

final class PdoBedRepository implements BedRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function insertBed(Bed $bed): Bed
    {
        try {
            $s = $this->pdo->prepare('INSERT INTO beds (ward_id, code, type, status) VALUES (:ward, :code, :type, :status) RETURNING bed_id');
            $s->execute([':ward' => $bed->wardId, ':code' => $bed->code, ':type' => $bed->type, ':status' => $bed->status]);
            return new Bed((string)$s->fetchColumn(), $bed->wardId, $bed->code, $bed->type, $bed->status);
        } catch (PDOException $e) {
            throw new PersistenceFailure('Error al crear cama.', 0, $e);
        }
    }
}
