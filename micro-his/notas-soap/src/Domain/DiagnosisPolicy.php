<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Domain;

/** Reglas clínicas de RF-SOAP-05 y RF-SOAP-07. Sin dependencias: se prueba sin base de datos. */
final class DiagnosisPolicy
{
    public function assertCanAdd(ClinicalNote $note, Diagnosis $new): void
    {
        if ($note->isSigned()) {
            throw new SoapRuleViolation('NOTE_SIGNED', 409);
        }
        foreach ($note->diagnoses as $current) {
            if ($current->code === $new->code) {
                throw new SoapRuleViolation('DIAGNOSIS_DUPLICATED', 409);
            }
            if ($current->type === 'primary' && $new->type === 'primary') {
                throw new SoapRuleViolation('PRIMARY_DIAGNOSIS_EXISTS', 409);
            }
        }
    }
}
