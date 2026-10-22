<?php

declare(strict_types=1);

namespace MicroHis\Alergias\Persistence;

use MicroHis\Alergias\Application\AllergyStore;
use MicroHis\Alergias\Application\PersistenceFailure;
use MicroHis\Alergias\Domain\Allergy;
use PDO;
use PDOException;

/** Adaptador PDO de allergies y local_access_audits; solo lee local_patients (dueño: ASII-03). */
final class PdoAllergyStore implements AllergyStore
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function patientExists(string $patientId): bool
    {
        return $this->query('SELECT 1 FROM local_patients WHERE local_patient_id = ?', [$patientId])->fetchColumn() !== false;
    }

    public function save(Allergy $a, string $actor): void
    {
        try {
            $this->pdo->beginTransaction();
            $this->query('INSERT INTO allergies (allergy_id, local_patient_id, verified_by_user_ref_id, allergen_code, allergen_name, reaction,
                allergy_type, severity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [$a->id, $a->patientId, $actor, $a->allergenCode, $a->allergenName,
                $a->reaction, $a->type, $a->severity]);
            $this->query("INSERT INTO local_access_audits (correlation_id, actor_user_ref_id, module, action, resource_type, resource_id, local_patient_id,
                occurred_at) VALUES (?, ?, 'alergias', 'alergia.registrada', 'allergy', ?, ?, ?)", [bin2hex(random_bytes(16)), $actor, $a->id, $a->patientId, date(DATE_ATOM)]);
            $this->pdo->commit();
        } catch (PDOException $error) {
            $this->pdo->inTransaction() && $this->pdo->rollBack();
            throw new PersistenceFailure(str_starts_with((string) $error->getCode(), '23'), $error);
        }
    }

    public function activeFor(string $patientId): array
    {
        try {
            return $this->query("SELECT allergy_id, allergen_code, allergen_name, reaction, allergy_type, severity FROM allergies
                WHERE local_patient_id = ? AND status = 'active' ORDER BY allergen_name", [$patientId])->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
            throw new PersistenceFailure(false, $error);
        }
    }

    private function query(string $sql, array $params): \PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement;
    }
}
