import assert from 'node:assert/strict';
import {
    failureMessage,
    prescriptionList,
    prescriptionPayload,
    validateDraft,
} from './prescripciones.js';

const draft = {
    patient_id: ' patient-1 ',
    medication_id: ' medication-1 ',
    dosage_value: '500',
    dosage_unit: ' mg ',
    route: ' oral ',
    frequency_times: '2',
    frequency_interval: '12 horas',
    duration_value: '7',
    duration_unit: ' días ',
};

assert.deepEqual(validateDraft(draft), {});
assert.deepEqual(validateDraft({ ...draft, patient_id: ' ', dosage_value: 0, frequency_times: 1.5 }), {
    patient_id: 'Este campo es obligatorio.',
    dosage_value: 'La dosis debe ser un número mayor que cero.',
    frequency_times: 'Ingrese un entero mayor que cero.',
});

assert.deepEqual(prescriptionPayload({ ...draft, frequency_interval: ' ' }), {
    medication_id: 'medication-1',
    dosage_value: 500,
    dosage_unit: 'mg',
    route: 'oral',
    frequency_times: 2,
    frequency_interval: null,
    duration_value: 7,
    duration_unit: 'días',
});

const prescriptions = [{ id: 'rx-1', status: 'draft' }];
assert.equal(prescriptionList(prescriptions), prescriptions);
assert.throws(() => prescriptionList({ results: prescriptions }), TypeError);
assert.match(failureMessage(422, { code: 'ALLERGY_CONFLICT' }), /no se guardó/);
assert.doesNotThrow(() => failureMessage(422, null));
assert.equal(failureMessage(409), 'La prescripción ya no está en estado borrador.');
assert.equal(failureMessage(422, { code: 'MEDICATION_INACTIVE', title: 'Medicamento inactivo.' }), 'Medicamento inactivo.');

console.log('OK: pruebas de reglas de prescripciones pasaron.');
