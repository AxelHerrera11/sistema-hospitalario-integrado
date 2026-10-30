<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Persistence;

use MicroHis\Prescripciones\Application\AllergyChecker;
use MicroHis\Prescripciones\Application\Exception\AllergyCheckUnavailable;
use MicroHis\Prescripciones\Domain\Allergy;
use MicroHis\Prescripciones\Domain\AllergyType;
use MicroHis\Prescripciones\Domain\Severity;
use PDO;
use PDOException;
use ValueError;
final readonly class PdoAllergyChecker implements AllergyChecker
{
    public function __construct(private PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    public function activeAllergies(string $patientId): array
    {
        try {
            $statement = $this->pdo->prepare(
                "SELECT allergen_code, allergen_name, allergy_type, severity FROM allergies
                 WHERE local_patient_id = :patient AND status = 'active' ORDER BY allergen_name, allergen_code"
            );
            $statement->execute(['patient' => $patientId]);
            return array_map(static fn (array $row): Allergy => new Allergy(
                $row['allergen_code'] !== null ? (string) $row['allergen_code'] : null,
                $row['allergen_name'] !== null ? (string) $row['allergen_name'] : null,
                AllergyType::from((string) $row['allergy_type']),
                Severity::from((string) $row['severity']),
            ), $statement->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException|ValueError $error) {
            throw new AllergyCheckUnavailable(previous: $error);
        }
    }
}