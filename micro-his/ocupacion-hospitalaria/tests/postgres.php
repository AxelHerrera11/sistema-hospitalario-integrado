<?php
declare(strict_types=1);
use MicroHis\Occupancy\Persistence\PdoBedSnapshotReader;
require __DIR__ . '/../bootstrap.php';
$dsn = getenv('MICROHIS_PG_DSN') ?: throw new RuntimeException('Define MICROHIS_PG_DSN.');
$pdo = new PDO($dsn, getenv('MICROHIS_PG_USER') ?: null, getenv('MICROHIS_PG_PASSWORD') ?: null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->beginTransaction();
try {
    $pdo->exec('CREATE TEMP TABLE wards (ward_id text PRIMARY KEY, code text); CREATE TEMP TABLE beds (bed_id text PRIMARY KEY, ward_id text, code text, status text); CREATE TEMP TABLE bed_stays (bed_id text, started_at timestamptz, ended_at timestamptz)');
    $pdo->exec("INSERT INTO wards VALUES ('w1', 'MED'); INSERT INTO beds VALUES ('b1', 'w1', '01', 'available'), ('b2', 'w1', '02', 'occupied'), ('b3', 'w1', '03', 'available'); INSERT INTO bed_stays VALUES ('b1', '2026-10-13 16:00:00+00', NULL), ('b2', '2026-10-13 15:00:00+00', '2026-10-13 16:00:00+00'), ('b3', '2026-10-13 17:00:00+00', NULL)");
    $rows = (new PdoBedSnapshotReader($pdo))->readAt(new DateTimeImmutable('2026-10-13T10:00:00-06:00'));
    $activeByBed = array_column(array_map(static fn ($row) => ['id' => $row->bedId, 'active' => $row->hasActiveStay], $rows), 'active', 'id');
    if ($activeByBed !== ['b1' => true, 'b2' => false, 'b3' => false]) {
        throw new RuntimeException('PostgreSQL interval or hydration check failed.');
    }
    echo "OK: PostgreSQL adapter and temporal boundaries verified\n";
} finally {
    $pdo->rollBack();
}
