<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Persistence;

use MicroHis\MedicationCatalog\Application\DuplicateMedicationCode;
use MicroHis\MedicationCatalog\Application\MedicationRepository;
use MicroHis\MedicationCatalog\Application\PersistenceFailure;
use MicroHis\MedicationCatalog\Domain\Medication;
use MicroHis\MedicationCatalog\Domain\Validity;

/** Adaptador PDO del Repository; todo valor externo viaja como parámetro de una sentencia preparada. */
final readonly class PdoMedicationCatalog implements MedicationRepository
{
    private const COLUMNS = 'medication_id, code, name, active_ingredient, pharmacological_group, validity';

    public function __construct(private \PDO $pdo)
    {
        // Los fallos deben llegar como excepción para no confundirlos con "sin resultados".
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        // SQLite solo pasa a minúsculas los caracteres ASCII; se sustituye LOWER por una versión Unicode (defecto AV3).
        // PostgreSQL ya aplica Unicode, así que solo se registra para SQLite.
        if ($this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $this->pdo->sqliteCreateFunction('LOWER', static fn (?string $value): ?string => $value === null ? null : mb_strtolower($value), 1);
        }
    }

    public function findByCode(string $code): ?Medication
    {
        return $this->one('SELECT ' . self::COLUMNS . ' FROM medications WHERE code = :code', ['code' => $code]);
    }

    public function findById(string $id): ?Medication
    {
        return $this->one('SELECT ' . self::COLUMNS . ' FROM medications WHERE medication_id = :id', ['id' => $id]);
    }

    public function insert(Medication $medication): Medication
    {
        $sql = 'INSERT INTO medications (code, name, active_ingredient, pharmacological_group, validity)
                VALUES (:code, :name, :active_ingredient, :pharmacological_group, :validity)';
        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute([
                'code' => $medication->code,
                'name' => $medication->name,
                'active_ingredient' => $medication->activeIngredient,
                'pharmacological_group' => $medication->pharmacologicalGroup,
                'validity' => $medication->validity->value,
            ]);
            $id = $this->pdo->lastInsertId();
        } catch (\PDOException $error) {
            if (in_array($error->getCode(), ['23000', '23505'], true) && stripos($error->getMessage(), 'unique') !== false) {
                throw new DuplicateMedicationCode("El código {$medication->code} ya está registrado.", previous: $error);
            }
            throw new PersistenceFailure('No se confirmó el alta del medicamento.', previous: $error);
        }
        if ($statement->rowCount() !== 1 || $id === false) {
            throw new PersistenceFailure('No se confirmó el alta del medicamento.');
        }

        return Medication::restore($id, $medication->code, $medication->name, $medication->activeIngredient,
            $medication->pharmacologicalGroup, $medication->validity);
    }

    public function search(string $term, int $limit): array
    {
        $sql = 'SELECT ' . self::COLUMNS . " FROM medications
                WHERE LOWER(code) LIKE :by_code ESCAPE '\\' OR LOWER(name) LIKE :by_name ESCAPE '\\'
                   OR LOWER(active_ingredient) LIKE :by_ingredient ESCAPE '\\'
                ORDER BY name, code LIMIT :limit";
        $pattern = '%' . addcslashes(mb_strtolower($term), '%_\\') . '%';
        try {
            $statement = $this->pdo->prepare($sql);
            foreach (['by_code', 'by_name', 'by_ingredient'] as $name) {
                $statement->bindValue($name, $pattern);
            }
            $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
            $statement->execute();

            return array_map($this->hydrate(...), $statement->fetchAll(\PDO::FETCH_ASSOC));
        } catch (\PDOException|\ValueError $error) {
            throw new PersistenceFailure('No se pudo consultar el catálogo.', previous: $error);
        }
    }

    public function updateValidity(string $id, Validity $validity): void
    {
        try {
            $statement = $this->pdo->prepare('UPDATE medications SET validity = :validity WHERE medication_id = :id');
            $statement->execute(['validity' => $validity->value, 'id' => $id]);
        } catch (\PDOException $error) {
            throw new PersistenceFailure('No se confirmó el cambio de vigencia.', previous: $error);
        }
        if ($statement->rowCount() !== 1) {
            throw new PersistenceFailure('No se confirmó el cambio de vigencia.');
        }
    }

    private function one(string $sql, array $parameters): ?Medication
    {
        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($parameters);
            $row = $statement->fetch(\PDO::FETCH_ASSOC);

            return $row === false ? null : $this->hydrate($row);
        } catch (\PDOException|\ValueError $error) {
            throw new PersistenceFailure('No se pudo consultar el catálogo.', previous: $error);
        }
    }

    private function hydrate(array $row): Medication
    {
        return Medication::restore((string) $row['medication_id'], $row['code'], $row['name'], $row['active_ingredient'],
            $row['pharmacological_group'], Validity::from($row['validity']));
    }
}
