<?php

declare(strict_types=1);

use MicroHis\Prescripciones\Domain\Allergy;
use MicroHis\Prescripciones\Domain\AllergyAssessment;
use MicroHis\Prescripciones\Domain\AllergyConflict;
use MicroHis\Prescripciones\Domain\AllergyConflictBlocked;
use MicroHis\Prescripciones\Domain\AllergyMatcher;
use MicroHis\Prescripciones\Domain\AllergyType;
use MicroHis\Prescripciones\Domain\MedicationSnapshot;
use MicroHis\Prescripciones\Domain\NotTreatingPhysician;
use MicroHis\Prescripciones\Domain\Prescription;
use MicroHis\Prescripciones\Domain\PrescriptionNotDraft;
use MicroHis\Prescripciones\Domain\PrescriptionRoute;
use MicroHis\Prescripciones\Domain\PrescriptionRuleViolation;
use MicroHis\Prescripciones\Domain\PrescriptionStatus;
use MicroHis\Prescripciones\Domain\Severity;

require_once __DIR__.'/../bootstrap.php';

// --- Dominio base (tests existentes) ---

// La severidad replica los valores admitidos por la columna allergies.severity.
check('severidad: los cinco valores del esquema', array_map(static fn (Severity $s): string => $s->value, Severity::cases()) ===
    ['mild', 'moderate', 'severe', 'life_threatening', 'unknown']);

// El tipo de alergia replica los valores admitidos por la columna allergies.allergy_type.
check('tipo de alergia: los tres valores del esquema', array_map(static fn (AllergyType $t): string => $t->value, AllergyType::cases()) ===
    ['allergy', 'intolerance', 'unknown']);

// SD-PRE-01: es grave la alergia que no es intolerancia y tiene severidad severe o life_threatening.
check('SD-PRE-01: la alergia severe es grave', (new Allergy('PEN', null, AllergyType::Allergy, Severity::Severe))->isGrave());
check('SD-PRE-01: la alergia life_threatening es grave', (new Allergy('PEN', null, AllergyType::Allergy, Severity::LifeThreatening))->isGrave());
check('SD-PRE-01: la intolerancia severe no es grave', ! (new Allergy('PEN', null, AllergyType::Intolerance, Severity::Severe))->isGrave());
check('SD-PRE-01: la alergia unknown no es grave', ! (new Allergy('PEN', null, AllergyType::Allergy, Severity::Unknown))->isGrave());

// La etiqueta usa el código si no queda vacío tras recortar; si no, el nombre.
check('alergia: etiqueta por código', (new Allergy('PEN', null, AllergyType::Allergy, Severity::Severe))->label() === 'PEN');
check('alergia: etiqueta por nombre', (new Allergy(null, 'Penicilina', AllergyType::Allergy, Severity::Mild))->label() === 'Penicilina');
check('alergia: un código en blanco cae al nombre', (new Allergy('  ', 'Penicilina', AllergyType::Allergy, Severity::Mild))->label() === 'Penicilina');
check('alergia: etiqueta vacía sin datos', (new Allergy(null, null, AllergyType::Unknown, Severity::Unknown))->label() === '');

// La normalización SD-PRE-02 recorta, pasa a minúsculas y quita acentos.
check('SD-PRE-02: la normalización quita acentos y espacios', Allergy::normalize('  Ámoxicilina  ') === 'amoxicilina');

// El conflicto viaja al JSON con el tipo, la severidad y el dato del medicamento que coincidió.
$conflict = new AllergyConflict('PEN', AllergyType::Allergy, Severity::Severe, AllergyConflict::ACTIVE_INGREDIENT);
check('conflicto: forma JSON con allergy_type', $conflict->toArray() === [
    'allergen' => 'PEN',
    'allergy_type' => 'allergy',
    'severity' => 'severe',
    'matched_on' => 'active_ingredient',
]);
check('conflicto: la clave de cobertura normaliza el alérgeno', $conflict->key() === 'pen|active_ingredient');
check('conflicto: el conflicto grave no admite excepción', $conflict->isGrave());
check('conflicto: la intolerancia grave no es bloqueante sin excepción',
    ! (new AllergyConflict('LACT', AllergyType::Intolerance, Severity::Severe, AllergyConflict::PHARMACOLOGICAL_GROUP))->isGrave());

// La vía solo admite los seis valores del CHECK de la tabla H5.
check('vía: oral es válida', PrescriptionRoute::fromInput('Oral')->value === 'oral');
check('vía: se recorta y pasa a minúsculas', PrescriptionRoute::fromInput(' INTRAVENOSA ')->value === 'intravenosa');
$invalidRoute = thrown(static fn () => PrescriptionRoute::fromInput('oral oral'), PrescriptionRuleViolation::class);
check('vía: valor inválido viola la regla del campo route', $invalidRoute instanceof PrescriptionRuleViolation && $invalidRoute->field === 'route');

// El ciclo de vida de la prescripción son draft y signed.
check('estado: draft y signed', array_map(static fn (PrescriptionStatus $s): string => $s->value, PrescriptionStatus::cases()) === ['draft', 'signed']);

// El conflicto bloqueante lleva los conflictos y si admite excepción (422 ALLERGY_CONFLICT).
$blocked = thrown(static fn () => throw new AllergyConflictBlocked([$conflict], false), AllergyConflictBlocked::class);
check('conflicto bloqueante: lleva los conflictos', $blocked instanceof AllergyConflictBlocked && $blocked->conflicts === [$conflict]);
check('conflicto bloqueante: un conflicto grave no admite excepción', $blocked instanceof AllergyConflictBlocked && $blocked->exceptionAllowed === false);
$allowed = thrown(static fn () => throw new AllergyConflictBlocked([$conflict], true), AllergyConflictBlocked::class);
check('conflicto bloqueante: un conflicto no grave admite excepción', $allowed instanceof AllergyConflictBlocked && $allowed->exceptionAllowed === true);

// Las reglas de la firma: solo un borrador y solo el médico tratante.
check('firma: un borrador ya firmado o anulado da 409', str_contains((new PrescriptionNotDraft)->getMessage(), 'ya está firmada'));
check('firma: un médico que no es tratante da 403', str_contains((new NotTreatingPhysician)->getMessage(), 'tratante'));

// El snapshot del medicamento es la copia de solo lectura que entrega el catálogo.
$snapshot = new MedicationSnapshot('m-1', 'AMX-500', 'Amoxicilina 500 mg', 'Amoxicilina', 'Antibiótico', true);
check('medicamento: conserva los datos del catálogo', $snapshot->activeIngredient === 'Amoxicilina' && $snapshot->active);

// --- AllergyMatcher (nuevo) ---

$matcher = new AllergyMatcher();
$med = new MedicationSnapshot('m-1', 'AMX-500', 'Amoxicilina 500 mg', 'Amoxicilina', 'Antibiótico', true);

// Coincide por principio activo (normalizado): 'Amoxicilina' normalizado = 'amoxicilina'.
$allergies = [new Allergy('Amoxicilina', null, AllergyType::Allergy, Severity::Moderate)];
$conflicts = $matcher->match($allergies, $med);
check('matcher: coincide por principio activo normalizado', count($conflicts) === 1 && $conflicts[0]->matchedOn === AllergyConflict::ACTIVE_INGREDIENT);

// Coincide por grupo farmacológico (normalizado).
$allergies2 = [new Allergy('ANTIBIÓTICO', null, AllergyType::Allergy, Severity::Mild)];
$conflicts2 = $matcher->match($allergies2, $med);
check('matcher: coincide por grupo farmacológico normalizado', count($conflicts2) === 1 && $conflicts2[0]->matchedOn === AllergyConflict::PHARMACOLOGICAL_GROUP);

// No coincide si el alérgeno normalizado no es igual.
$allergies3 = [new Allergy('OTRO', null, AllergyType::Allergy, Severity::Mild)];
$conflicts3 = $matcher->match($allergies3, $med);
check('matcher: sin coincidencia si no iguala principio ni grupo', count($conflicts3) === 0);

// Alérgeno vacío normalizado no coincide.
$allergies4 = [new Allergy('  ', null, AllergyType::Allergy, Severity::Mild)];
$conflicts4 = $matcher->match($allergies4, $med);
check('matcher: alérgeno en blanco no coincide', count($conflicts4) === 0);

// Devuelve lista vacía si no hay alergias.
$conflicts5 = $matcher->match([], $med);
check('matcher: sin alergias → lista vacía', count($conflicts5) === 0);

// --- AllergyAssessment (nuevo) ---

$assessment = new AllergyAssessment();

// Conflicto grave → bloqueado, sin excepción.
$graveConflicts = [new AllergyConflict('PEN', AllergyType::Allergy, Severity::Severe, AllergyConflict::ACTIVE_INGREDIENT)];
$resultGrave = $assessment->assess($graveConflicts);
check('assessment: conflicto grave bloqueado sin excepción', $resultGrave['blocked'] === true && $resultGrave['exceptionAllowed'] === false && count($resultGrave['conflicts']) === 1);

// Conflicto no grave → bloqueado pero admite excepción.
$mildConflicts = [new AllergyConflict('PEN', AllergyType::Allergy, Severity::Mild, AllergyConflict::ACTIVE_INGREDIENT)];
$resultMild = $assessment->assess($mildConflicts);
check('assessment: conflicto no grave bloqueado con excepción', $resultMild['blocked'] === true && $resultMild['exceptionAllowed'] === true);

// Sin conflictos → no bloqueado.
$resultNone = $assessment->assess([]);
check('assessment: sin conflictos → no bloqueado', $resultNone['blocked'] === false && $resultNone['exceptionAllowed'] === false);

// Intolerancia grave → no grave (por tipo), admite excepción.
$intoleranceGrave = [new AllergyConflict('LACT', AllergyType::Intolerance, Severity::LifeThreatening, AllergyConflict::PHARMACOLOGICAL_GROUP)];
$resultIntol = $assessment->assess($intoleranceGrave);
check('assessment: intolerancia life_threatening admite excepción', $resultIntol['blocked'] === true && $resultIntol['exceptionAllowed'] === true);

// --- Prescription aggregate (nuevo) ---

$medSnap = new MedicationSnapshot('m-1', 'AMX-500', 'Amoxicilina 500 mg', 'Amoxicilina', 'Antibiótico', true);

// Crear borrador válido.
$rx = Prescription::createDraft(
    'rx-1',
    'patient-1',
    'doctor-1',
    'episode-1',
    $medSnap,
    PrescriptionRoute::fromInput('Oral'),
    '500 mg',
    'Cada 8 horas',
    '7',
    'Tomar con alimentos'
);
check('prescripción: crear borrador válido', $rx->isDraft() && $rx->id === 'rx-1' && $rx->patientId === 'patient-1');

// Crear borrador falla con dosis vacía.
$errDose = thrown(static fn () => Prescription::createDraft('rx', 'p', 'd', 'e', $medSnap, PrescriptionRoute::fromInput('Oral'), '', 'f', '1'), PrescriptionRuleViolation::class);
check('prescripción: dosis vacía viola regla', $errDose instanceof PrescriptionRuleViolation && $errDose->field === 'dose');

// Crear borrador falla con frecuencia vacía.
$errFreq = thrown(static fn () => Prescription::createDraft('rx', 'p', 'd', 'e', $medSnap, PrescriptionRoute::fromInput('Oral'), '1', '', '1'), PrescriptionRuleViolation::class);
check('prescripción: frecuencia vacía viola regla', $errFreq instanceof PrescriptionRuleViolation && $errFreq->field === 'frequency');

// Crear borrador falla con duración inválida.
$errDur = thrown(static fn () => Prescription::createDraft('rx', 'p', 'd', 'e', $medSnap, PrescriptionRoute::fromInput('Oral'), '1', 'f', '0'), PrescriptionRuleViolation::class);
check('prescripción: duración 0 viola regla', $errDur instanceof PrescriptionRuleViolation && $errDur->field === 'durationDays');

// Firmar borrador válido sin conflictos → OK.
$signed = $rx->sign('doctor-1', new \DateTimeImmutable('2026-10-20 10:00:00'), []);
check('prescripción: firmar borrador sin conflictos', $signed->isSigned() && $signed->signedBy === 'doctor-1' && $signed->allergyConflictsAtSign === []);

// Firmar de nuevo (ya firmada) → PrescriptionNotDraft.
$errAlreadySigned = thrown(static fn () => $signed->sign('doctor-1', new \DateTimeImmutable('2026-10-20 11:00:00'), []), PrescriptionNotDraft::class);
check('prescripción: firmar ya firmada da 409', $errAlreadySigned instanceof PrescriptionNotDraft);

// Otro médico intenta firmar → NotTreatingPhysician.
$errNotTreating = thrown(static fn () => $rx->sign('doctor-2', new \DateTimeImmutable('2026-10-20 10:00:00'), []), NotTreatingPhysician::class);
check('prescripción: médico no tratante da 403', $errNotTreating instanceof NotTreatingPhysician);

// Firmar con conflicto grave → AllergyConflictBlocked sin excepción (SD-PRE-01).
$errGraveConflict = thrown(
    static fn () => $rx->sign('doctor-1', new \DateTimeImmutable('2026-10-20 10:00:00'), $graveConflicts),
    AllergyConflictBlocked::class
);
check('prescripción: conflicto grave al firmar bloquea sin excepción', $errGraveConflict instanceof AllergyConflictBlocked && $errGraveConflict->exceptionAllowed === false);

// Firmar con conflicto no grave → NO lanza (exceptionAllowed=true); la capa de aplicación valida el permiso.
$rxMild = Prescription::createDraft('rx-mild', 'p', 'doctor-1', 'e', $medSnap, PrescriptionRoute::fromInput('Oral'), '1', 'f', '1');
$signedMild = $rxMild->sign('doctor-1', new \DateTimeImmutable('2026-10-20 10:00:00'), $mildConflicts);
check('prescripción: conflicto no grave admite excepción (no lanza en dominio)', $signedMild->isSigned());

// La prescripción firmada guarda los conflictos detectados al firmar.
check('prescripción: guarda conflictos al firmar', $signedMild->allergyConflictsAtSign !== null && count($signedMild->allergyConflictsAtSign) === 1);

// Assessment confirma que conflicto no grave admite excepción.
$allowedToSign = (new AllergyAssessment())->assess($mildConflicts);
check('prescripción: assessment dice que conflicto no grave admite excepción', $allowedToSign['exceptionAllowed'] === true);

finish();