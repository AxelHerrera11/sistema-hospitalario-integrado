<?php

declare(strict_types=1);

namespace MicroHis\Occupancy\Persistence;

use MicroHis\Occupancy\Application\BedSnapshotReader;
use MicroHis\Occupancy\Application\PersistenceFailure;
use MicroHis\Occupancy\Domain\BedSnapshot;

final readonly class PdoBedSnapshotReader implements BedSnapshotReader
{
    public function __construct(private \PDO $pdo)
    {
    }

    public function readAt(\DateTimeImmutable $cutoff): array
    {
        $sql = <<<'SQL'
            SELECT b.bed_id, w.ward_id, w.code AS ward_code, b.status,
                   EXISTS (
                       SELECT 1 FROM bed_stays s
                       WHERE s.bed_id = b.bed_id
                         AND s.started_at <= :cutoff_start
                         AND (s.ended_at IS NULL OR s.ended_at > :cutoff_end)
                   ) AS has_active_stay
            FROM beds b
            JOIN wards w ON w.ward_id = b.ward_id
            ORDER BY w.code, b.code
            SQL;

        try {
            $statement = $this->pdo->prepare($sql);
            $value = $cutoff->format(DATE_ATOM);
            $statement->execute(['cutoff_start' => $value, 'cutoff_end' => $value]);

            return array_map(static fn (array $row): BedSnapshot => new BedSnapshot(
                (string) $row['bed_id'], (string) $row['ward_id'], $row['ward_code'],
                $row['status'], in_array($row['has_active_stay'], [true, 1, '1', 't', 'true'], true),
            ), $statement->fetchAll(\PDO::FETCH_ASSOC));
        } catch (\PDOException $error) {
            throw new PersistenceFailure('Hospital occupancy is unavailable.', previous: $error);
        }
    }
}
