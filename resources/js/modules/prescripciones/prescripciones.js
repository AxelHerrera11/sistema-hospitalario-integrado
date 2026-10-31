export const PERMISSIONS = {
    read: 'prescripciones.ver',
    create: 'prescripciones.crear',
    sign: 'prescripciones.firmar',
};

const requiredText = ['patient_id', 'medication_id', 'dosage_unit', 'route', 'duration_unit'];

export function validateDraft(draft) {
    const errors = {};
    for (const field of requiredText) {
        if (!String(draft[field] ?? '').trim()) errors[field] = 'Este campo es obligatorio.';
    }

    if (!Number.isFinite(Number(draft.dosage_value)) || Number(draft.dosage_value) <= 0) {
        errors.dosage_value = 'La dosis debe ser un número mayor que cero.';
    }
    for (const field of ['frequency_times', 'duration_value']) {
        if (!Number.isInteger(Number(draft[field])) || Number(draft[field]) <= 0) {
            errors[field] = 'Ingrese un entero mayor que cero.';
        }
    }
    return errors;
}

export function prescriptionPayload(draft) {
    const interval = String(draft.frequency_interval ?? '').trim();
    return {
        medication_id: String(draft.medication_id).trim(),
        dosage_value: Number(draft.dosage_value),
        dosage_unit: String(draft.dosage_unit).trim(),
        route: String(draft.route).trim(),
        frequency_times: Number(draft.frequency_times),
        frequency_interval: interval || null,
        duration_value: Number(draft.duration_value),
        duration_unit: String(draft.duration_unit).trim(),
    };
}

export function prescriptionList(data) {
    if (!Array.isArray(data)) throw new TypeError('La respuesta de prescripciones no es una lista válida.');
    return data;
}

export function failureMessage(status, body = {}) {
    if (status === 422 && body?.code === 'ALLERGY_CONFLICT') {
        const severities = { mild: 'leve', moderate: 'moderada', unknown: 'desconocida', severe: 'grave', life_threatening: 'potencialmente mortal' };
        return `Conflicto de alergia. Severidad: ${severities[body.severity] ?? 'desconocida'}. La prescripción no se guardó. No hay excepción disponible.`;
    }
    // FormRequest responde `message`; los errores de dominio (problem+json) responden `title`.
    if (status === 422 && typeof body?.message === 'string') return body.message;
    if (status === 422 && typeof body?.title === 'string') return body.title;
    if (status === 401) return 'Su sesión no es válida. Inicie sesión de nuevo.';
    if (status === 403) return 'No tiene permiso para esta operación en el hospital activo.';
    if (status === 404) return 'No se encontró el paciente o la prescripción.';
    if (status === 409) return 'La prescripción ya no está en estado borrador.';
    if (status === 503) return 'No se pudo verificar una dependencia clínica; no se confirmó la operación.';
    return 'Prescripciones no está disponible. No se confirmó la operación; intente de nuevo.';
}

export function patientFromResponse(data) {
    const patient = data?.patient;
    if (!patient || typeof patient.patient_id !== 'string' || !/^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$/i.test(patient.patient_id)
        || !patient.code || !patient.given_name || !patient.family_name) {
        throw new TypeError('La respuesta del paciente no es válida.');
    }
    return { id: patient.patient_id, code: patient.code, name: `${patient.given_name} ${patient.family_name}` };
}

export function activeMedications(data) {
    if (!Array.isArray(data?.results)) throw new TypeError('La respuesta de medicamentos no es una lista válida.');
    return data.results
        .filter((medication) => medication.validity === 'active' && medication.medication_id && medication.name && medication.code)
        .map((medication) => ({ id: medication.medication_id, code: medication.code, name: medication.name }));
}

export async function findPatientPrescriptions(api, code) {
    const { data } = await api.get(`/patients/${encodeURIComponent(code.trim())}`);
    const patient = patientFromResponse(data);
    const response = await api.get(`/patients/${encodeURIComponent(patient.id)}/prescriptions`);
    return { patient, prescriptions: prescriptionList(response.data) };
}
