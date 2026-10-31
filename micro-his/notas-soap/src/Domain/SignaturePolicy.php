<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Domain;

/** Regla de firma de UC-SOAP-05: cuatro secciones con contenido y exactamente un diagnóstico principal. Sin dependencias: se prueba sin base de datos. */
final class SignaturePolicy
{
    public function assertCanSign(ClinicalNote $note): void
    {
        if ($note->isSigned()) {
            throw new SoapRuleViolation('NOTE_SIGNED', 409);
        }
        $primaries = count(array_filter($note->diagnoses, static fn (Diagnosis $d): bool => $d->type === 'primary'));
        if ($note->sections === null || $primaries !== 1) {
            throw new SoapRuleViolation('SOAP_INCOMPLETE', 422);
        }
    }
}
