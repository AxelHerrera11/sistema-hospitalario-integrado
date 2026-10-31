import assert from 'node:assert/strict';
import {
    activeMedications,
    patientFromResponse,
    findPatientPrescriptions,
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


const id = '00000000-0000-4000-8000-000000000001';
const response = { patient: { patient_id: id, global_id: 'otro-id', code: 'P/001', given_name: 'Ana', family_name: 'López' } };
assert.deepEqual(patientFromResponse(response), { id, code: 'P/001', name: 'Ana López' });
assert.throws(() => patientFromResponse({ patient: { id } }), TypeError);
assert.throws(() => patientFromResponse({ patient: { ...response.patient, patient_id: 'P001' } }), TypeError);
const calls = [];
const api = { async get(url) {
    calls.push(url);
    return { data: calls.length === 1 ? response : prescriptions };
} };
assert.deepEqual(await findPatientPrescriptions(api, ' P/001 '), { patient: patientFromResponse(response), prescriptions });
assert.deepEqual(calls, ['/patients/P%2F001', '/patients/' + id + '/prescriptions']);
let requests = 0;
await assert.rejects(findPatientPrescriptions({ async get() { requests++; throw new Error('404'); } }, 'absent'), /404/);
assert.equal(requests, 1, 'No consulta prescripciones si no se encuentra el paciente');
const active = { medication_id: 'med-1', code: 'AMX', name: 'Amoxicilina', validity: 'active' };
assert.deepEqual(activeMedications({ results: [active, { ...active, validity: 'inactive' }, { ...active, validity: null }] }), [{ id: 'med-1', code: 'AMX', name: 'Amoxicilina' }]);
assert.deepEqual(activeMedications({ results: [{ id: 'legacy-id', code: 'AMX', name: 'Amoxicilina', validity: 'active' }] }), []);
assert.equal(prescriptionPayload({ ...draft, medication_id: activeMedications({ results: [active] })[0].id }).medication_id, active.medication_id);
assert.deepEqual(activeMedications({ results: [] }), []);
assert.throws(() => activeMedications([]), TypeError);
for (const [severity, label] of [['moderate', 'moderada'], ['unknown', 'desconocida'], ['severe', 'grave'], ['life_threatening', 'potencialmente mortal']]) {
    const message = failureMessage(422, { code: 'ALLERGY_CONFLICT', severity, exception_allowed: false });
    assert.ok(message.includes(label));
    assert.match(message, /no se guardó/);
    assert.match(message, /No hay excepción disponible/);
}
console.log('OK: reglas, paciente por código e ID interno, medicamentos vigentes y severidades de alergia.');
