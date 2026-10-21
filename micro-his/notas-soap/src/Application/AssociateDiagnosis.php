<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Application;

use MicroHis\NotasSoap\Domain\Diagnosis;
use MicroHis\NotasSoap\Domain\DiagnosisPolicy;
use MicroHis\NotasSoap\Domain\SoapRuleViolation;

/** UC-SOAP-02 a 04: asociación de un diagnóstico CIE-10 a la nota (RF-SOAP-03 a RF-SOAP-07). */
final class AssociateDiagnosis
{
    public function __construct(
        private readonly TreatingPhysicianGuard $guard,
        private readonly Icd10Catalog $catalog,
        private readonly DiagnosisPolicy $policy,
        private readonly ClinicalNoteRepository $notes,
    ) {
    }

    public function execute(string $userId, string $noteId, Diagnosis $diagnosis): void
    {
        $note = $this->notes->find($noteId) ?? throw new SoapRuleViolation('NOTE_NOT_FOUND', 404);
        $this->guard->assertAllowed($userId, $note->episodeId, $note->authorId);
        $this->policy->assertCanAdd($note, $diagnosis);
        try {
            $description = $this->catalog->description($diagnosis->code);
        } catch (CatalogUnavailable) {
            throw new SoapRuleViolation('ICD10_UNAVAILABLE', 503);
        }
        $this->notes->addDiagnosis($note, $diagnosis, $description ?? throw new SoapRuleViolation('DIAGNOSIS_CODE_INVALID', 422));
    }
}
