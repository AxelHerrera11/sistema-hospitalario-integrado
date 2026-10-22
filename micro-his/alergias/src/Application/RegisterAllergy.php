<?php

declare(strict_types=1);

namespace MicroHis\Alergias\Application;

use Closure;
use MicroHis\Alergias\Domain\Allergy;
use MicroHis\Alergias\Domain\DomainRuleViolation;

/** UC-ALG-01 Registrar alergia: paciente existente, sin duplicar una alergia activa equivalente, con auditoría. */
final class RegisterAllergy
{
    /** @param Closure(): string $newId */
    public function __construct(private readonly AllergyStore $store, private readonly Closure $newId) {}

    /** @param array<string, mixed> $data */
    public function execute(string $patientId, array $data, string $actor): Allergy
    {
        if (!$this->store->patientExists($patientId)) {
            throw new DomainRuleViolation('PATIENT_NOT_FOUND', 'patient_id');
        }
        $allergy = Allergy::register(($this->newId)(), $patientId, $data);
        try {
            $this->store->save($allergy, $actor);
        } catch (PersistenceFailure $failure) {
            throw $failure->duplicate ? new DomainRuleViolation('ALLERGY_ALREADY_ACTIVE', 'allergen_name') : $failure;
        }

        return $allergy;
    }
}
