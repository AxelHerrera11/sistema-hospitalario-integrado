<?php

declare(strict_types=1);

namespace Tests\Feature\SalasCamas;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class CatalogConstraintsTest extends TestCase
{
    #[DataProvider('invalidWrites')]
    public function test_database_rejects_invalid_catalog_write(string $case, string $state): void
    {
        $this->assertSame('testing', app()->environment());
        $connection = DB::connection('hospital');
        $this->assertSame('his_hospital_test', $connection->getDatabaseName());
        $this->assertSame('55433', (string) config('database.connections.hospital.port'));
        $pdo = $connection->getPdo();
        $this->assertSame('pgsql', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        $wardId = (string) Str::uuid();
        $code = 'S15-'.Str::uuid();
        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO wards (ward_id, code, name, location) VALUES (?, ?, 'S15', 'Test')")
                ->execute([$wardId, $code]);
            $pdo->prepare("INSERT INTO beds (ward_id, code, type) VALUES (?, 'B-1', 'standard')")
                ->execute([$wardId]);
            [$sql, $params] = match ($case) {
                'fk' => ["INSERT INTO beds (ward_id, code, type) VALUES (?, 'ORPHAN', 'standard')", [(string) Str::uuid()]],
                'ward_code' => ["INSERT INTO wards (code, name, location) VALUES (?, 'Dup', 'Test')", [$code]],
                'bed_code' => ["INSERT INTO beds (ward_id, code, type) VALUES (?, 'B-1', 'standard')", [$wardId]],
                'ward_state' => ["UPDATE wards SET status = 'broken' WHERE ward_id = ?", [$wardId]],
                'bed_state' => ["UPDATE beds SET status = 'broken' WHERE ward_id = ?", [$wardId]],
            };
            $pdo->exec('SAVEPOINT invalid_write');
            try {
                $pdo->prepare($sql)->execute($params);
                $this->fail('La base aceptó una escritura inválida: '.$case);
            } catch (PDOException $error) {
                $this->assertSame($state, $error->errorInfo[0]);
            }
            $pdo->exec('ROLLBACK TO SAVEPOINT invalid_write');
            $query = $pdo->prepare('SELECT status FROM wards WHERE ward_id = ?');
            $query->execute([$wardId]);
            $this->assertSame(['active'], $query->fetchAll(PDO::FETCH_COLUMN));
            $query = $pdo->prepare('SELECT code, status FROM beds WHERE ward_id = ?');
            $query->execute([$wardId]);
            $this->assertSame([['code' => 'B-1', 'status' => 'available']], $query->fetchAll(PDO::FETCH_ASSOC));
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
        $query = $pdo->prepare('SELECT count(*) FROM wards WHERE ward_id = ?');
        $query->execute([$wardId]);
        $this->assertSame(0, (int) $query->fetchColumn());
    }

    public static function invalidWrites(): array
    {
        return [
            'I01 foreign key' => ['fk', '23503'],
            'I02 duplicate ward' => ['ward_code', '23505'],
            'I03 duplicate bed' => ['bed_code', '23505'],
            'I04 ward state' => ['ward_state', '23514'],
            'I05 bed state' => ['bed_state', '23514'],
        ];
    }
}
