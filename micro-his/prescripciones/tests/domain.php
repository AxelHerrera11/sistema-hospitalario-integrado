<?php

declare(strict_types=1);

use MicroHis\Prescripciones\Domain\Allergy;
use MicroHis\Prescripciones\Domain\AllergyConflict;
use MicroHis\Prescripciones\Domain\AllergyConflictBlocked;
use MicroHis\Prescripciones\Domain\AllergyType;
use MicroHis\Prescripciones\Domain\MedicationSnapshot;
use MicroHis\Prescripciones\Domain\NotTreatingPhysician;
use MicroHis\Prescripciones\Domain\PrescriptionNotDraft;
use MicroHis\Prescripciones\Domain\PrescriptionRoute;
use MicroHis\Prescripciones\Domain\PrescriptionRuleViolation;
use MicroHis\Prescripciones\Domain\PrescriptionStatus;
use MicroHis\Prescripciones\Domain\Severity;

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
