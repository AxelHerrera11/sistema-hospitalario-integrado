<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Domain;

/** Regla de corrección de UC-SOAP-06: solo se corrige una nota firmada y con un motivo escrito. Sin dependencias: se prueba sin base de datos. */
final class CorrectionPolicy
{
    public const MAX_REASON_LENGTH = 500;

    public function assertCanCorrect(ClinicalNote $note, string $reason): void
    {
        if (!$note->isSigned()) {
            throw new SoapRuleViolation('NOTE_NOT_SIGNED', 409);
        }
        if (preg_match('/^[\s\p{Z}\p{Cf}]*$/u', $reason) === 1 || mb_strlen($reason) > self::MAX_REASON_LENGTH) {
            throw new SoapRuleViolation('CORRECTION_REASON_INVALID', 422);
        }
    }
}
