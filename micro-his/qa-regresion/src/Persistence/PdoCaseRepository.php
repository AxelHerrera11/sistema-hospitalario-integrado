<?php
declare(strict_types=1);
namespace MicroHis\QaRegresion\Persistence;

use MicroHis\QaRegresion\Domain\{CaseRepository, ManualCase, StorageFailure};
use PDO;
use PDOException;

final class PdoCaseRepository implements CaseRepository
{
    public function __construct(private readonly PDO $pdo)
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    public function save(ManualCase $case): void
    {
        try {
            $this->pdo->prepare('INSERT INTO qa_manual_cases (id, purpose, steps, expected) VALUES (?, ?, ?, ?)')
                ->execute([$case->id, $case->purpose, $case->steps, $case->expected]);
        } catch (PDOException $error) {
            throw new StorageFailure('No se pudo guardar el caso.', 0, $error);
        }
    }
    public function find(string $id): ?ManualCase
    {
        try {
            $query = $this->pdo->prepare('SELECT id, purpose, steps, expected FROM qa_manual_cases WHERE id = ?');
            $query->execute([$id]);
            $row = $query->fetch(PDO::FETCH_ASSOC);
            return $row === false ? null : new ManualCase($row['id'], $row['purpose'], $row['steps'], $row['expected']);
        } catch (PDOException $error) {
            throw new StorageFailure('No se pudo consultar el caso.', 0, $error);
        }
    }
}
