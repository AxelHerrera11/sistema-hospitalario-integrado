<template>
    <section class="prescriptions" aria-labelledby="prescriptions-title">
        <h1 id="prescriptions-title">Prescripciones</h1>
        <p v-if="!canRead" class="prescriptions__message prescriptions__message--error" role="alert">
            Su sesión no tiene el permiso <code>prescripciones.ver</code> en el hospital activo.
        </p>

        <form v-if="canRead" class="prescriptions__card" @submit.prevent="searchPatient">
            <label for="prescriptions-patient">Código del paciente</label>
            <div class="prescriptions__row">
                <input id="prescriptions-patient" v-model="patientCode" @input="clearPatient" :disabled="submitting || Boolean(signingId)" autocomplete="off" required>
                <button type="submit" :disabled="loading || submitting || Boolean(signingId)">{{ loading ? 'Cargando…' : 'Buscar paciente' }}</button>
            </div>
            <p v-if="loadError" class="prescriptions__message prescriptions__message--error" role="alert">{{ loadError }}</p>
        </form>

        <p v-if="patient" role="status">{{ patient.name }} · {{ patient.code }}</p>

        <form v-if="canCreate && patient" class="prescriptions__card" novalidate @submit.prevent="create">
            <h2>Crear prescripción</h2>
            <p v-if="!canReadMedications" role="alert">Se requiere el permiso medicamentos.ver para seleccionar medicamentos.</p>
            <div v-else class="prescriptions__field">
                <label for="medication-search">Buscar medicamento por nombre o código</label>
                <div class="prescriptions__row">
                    <input id="medication-search" v-model="medicationTerm" minlength="2" maxlength="60" @input="clearMedications" :disabled="submitting">
                    <button type="button" @click="loadMedications" :disabled="medicationsLoading || submitting || !medicationTerm.trim()">
                        {{ medicationsLoading ? 'Buscando…' : 'Buscar medicamento' }}
                    </button>
                </div>
                <p v-if="medicationError" role="alert">{{ medicationError }}</p>
                <p v-if="medicationsSearched && !medicationsLoading && !medicationError && !medications.length" role="status">No hay medicamentos vigentes para esta búsqueda.</p>
                <label for="prescriptions-medication">Medicamento vigente</label>
                <select id="prescriptions-medication" v-model="draft.medication_id" :disabled="medicationsLoading || submitting"
                        :aria-invalid="Boolean(errors.medication_id)" :aria-describedby="errors.medication_id ? 'medication-error' : null">
                    <option value="">Seleccione un medicamento</option>
                    <option v-for="medication in medications" :key="medication.id" :value="medication.id">{{ medication.name }} · {{ medication.code }}</option>
                </select>
                <p id="medication-error" v-if="errors.medication_id" class="prescriptions__field-error" role="alert">{{ errors.medication_id }}</p>
            </div>
            <div class="prescriptions__grid">
                <div v-for="field in fields" :key="field.key" class="prescriptions__field">
                    <label :for="`prescriptions-${field.key}`">{{ field.label }}</label>
                    <input :id="`prescriptions-${field.key}`" v-model="draft[field.key]" :type="field.type"
                           :step="field.step" :min="field.type === 'number' ? '0' : null"
                           :aria-invalid="Boolean(errors[field.key])"
                           :aria-describedby="errors[field.key] ? `prescriptions-${field.key}-error` : null">
                    <p v-if="errors[field.key]" :id="`prescriptions-${field.key}-error`" class="prescriptions__field-error">{{ errors[field.key] }}</p>
                </div>
            </div>
            <button type="submit" :disabled="submitting">{{ submitting ? 'Guardando…' : 'Crear borrador' }}</button>
            <p v-if="createMessage" :class="['prescriptions__message', createError ? 'prescriptions__message--error' : '']"
               :role="createError ? 'alert' : 'status'">{{ createMessage }}</p>
        </form>

        <p v-if="searched && !loadError && !loading && prescriptions.length === 0" class="prescriptions__message" role="status">
            No hay prescripciones para este paciente.
        </p>
        <ul v-if="prescriptions.length" class="prescriptions__list" aria-label="Prescripciones del paciente">
            <li v-for="prescription in prescriptions" :key="prescription.id">
                <article class="prescriptions__card">
                    <h2>Prescripción {{ prescription.id }}</h2>
                    <p>Medicamento: {{ prescription.medication_id }}</p>
                    <p>Dosis: {{ prescription.dosage_value }} {{ prescription.dosage_unit }} · {{ prescription.route }}</p>
                    <p>Estado: {{ prescription.status }}</p>
                    <p v-if="prescription.warnings?.length" class="prescriptions__message prescriptions__message--warning" role="status">
                        {{ prescription.warnings.join(', ') }}
                    </p>
                    <button v-if="canSign && prescription.status === 'draft'" type="button"
                            :disabled="signingId === prescription.id" @click="sign(prescription.id)">
                        {{ signingId === prescription.id ? 'Firmando…' : 'Firmar' }}
                    </button>
                </article>
            </li>
        </ul>
        <p v-if="signError" class="prescriptions__message prescriptions__message--error" role="alert">{{ signError }}</p>
    </section>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import api from '@/plugins/axios';
import { can } from '@/router/permissions';
import { useAuthStore } from '@/stores/auth';
import { activeMedications, findPatientPrescriptions, failureMessage, prescriptionList, prescriptionPayload, PERMISSIONS, validateDraft } from '@/modules/prescripciones/prescripciones';

const auth = useAuthStore();
const canRead = computed(() => can(auth.user, PERMISSIONS.read));
const canCreate = computed(() => can(auth.user, PERMISSIONS.create));
const canSign = computed(() => can(auth.user, PERMISSIONS.sign));
const canReadMedications = computed(() => can(auth.user, 'medicamentos.ver'));
const fields = [
    { key: 'dosage_value', label: 'Dosis', type: 'number', step: '0.001' },
    { key: 'dosage_unit', label: 'Unidad', type: 'text' },
    { key: 'route', label: 'Vía', type: 'text' },
    { key: 'frequency_times', label: 'Veces por intervalo', type: 'number', step: '1' },
    { key: 'frequency_interval', label: 'Intervalo', type: 'text' },
    { key: 'duration_value', label: 'Duración', type: 'number', step: '1' },
    { key: 'duration_unit', label: 'Unidad de duración', type: 'text' },
];
const patientCode = ref('');
const patient = ref(null);
const patientId = computed(() => patient.value?.id ?? '');
const medicationTerm = ref('');
const medications = ref([]);
const medicationsLoading = ref(false);
const medicationsSearched = ref(false);
const medicationError = ref('');
let patientRequest = 0;
let medicationRequest = 0;
const draft = reactive({ medication_id: '', ...Object.fromEntries(fields.map(({ key }) => [key, ''])) });
const errors = reactive({});
const prescriptions = ref([]);
const searched = ref(false);
const loading = ref(false);
const submitting = ref(false);
const signingId = ref('');
const loadError = ref('');
const createMessage = ref('');
const createError = ref(false);
const signError = ref('');
const statusOf = (error) => error.response?.status ?? 0;
const errorMessage = (error) => error instanceof TypeError ? error.message : failureMessage(statusOf(error), error.response?.data);

function clearMedications() {
    medicationRequest++;
    medications.value = [];
    draft.medication_id = '';
    medicationsSearched.value = false;
    medicationError.value = '';
    medicationsLoading.value = false;
}

async function loadMedications() {
    if (!canReadMedications.value || medicationsLoading.value) return;
    if (medicationTerm.value.trim().length < 2 || medicationTerm.value.trim().length > 60) {
        medicationError.value = 'La búsqueda admite de 2 a 60 caracteres.';
        return;
    }
    const request = ++medicationRequest;
    medicationsLoading.value = true;
    medicationsSearched.value = false;
    medications.value = [];
    draft.medication_id = '';
    medicationError.value = '';
    try {
        const { data } = await api.get('/medications', { params: { q: medicationTerm.value.trim() } });
        if (request !== medicationRequest) return;
        medications.value = activeMedications(data);
        medicationsSearched.value = true;
    } catch (error) {
        if (request === medicationRequest) medicationError.value = errorMessage(error);
    } finally {
        if (request === medicationRequest) medicationsLoading.value = false;
    }
}

function clearPatient() {
    patientRequest++;
    patient.value = null;
    prescriptions.value = [];
    searched.value = false;
    loading.value = false;
    loadError.value = '';
    createMessage.value = '';
    signError.value = '';
    Object.keys(draft).forEach((key) => { draft[key] = ''; });
    Object.keys(errors).forEach((key) => delete errors[key]);
    clearMedications();
}

async function searchPatient() {
    if (loading.value || !patientCode.value.trim()) return;
    clearPatient();
    const request = ++patientRequest;
    loading.value = true;
    try {
        const result = await findPatientPrescriptions(api, patientCode.value);
        if (request !== patientRequest) return;
        patient.value = result.patient;
        prescriptions.value = result.prescriptions;
        searched.value = true;
    } catch (error) {
        if (request === patientRequest) loadError.value = errorMessage(error);
    } finally {
        if (request === patientRequest) loading.value = false;
    }
}

async function load() {
    if (loading.value || !patientId.value.trim()) return;
    loading.value = true;
    loadError.value = '';
    try {
        const { data } = await api.get(`/patients/${encodeURIComponent(patientId.value.trim())}/prescriptions`);
        prescriptions.value = prescriptionList(data);
        searched.value = true;
    } catch (error) {
        prescriptions.value = [];
        loadError.value = errorMessage(error);
    } finally {
        loading.value = false;
    }
}

async function create() {
    if (submitting.value || !patient.value || !canReadMedications.value || !medications.value.some((item) => item.id === draft.medication_id)) return;
    createMessage.value = '';
    createError.value = false;
    Object.keys(errors).forEach((key) => delete errors[key]);
    Object.assign(errors, validateDraft({ ...draft, patient_id: patientId.value }));
    if (Object.keys(errors).length) return;
    submitting.value = true;
    try {
        await api.post(`/patients/${encodeURIComponent(patientId.value.trim())}/prescriptions`, prescriptionPayload(draft));
        createMessage.value = 'Prescripción creada como borrador.';
        Object.keys(draft).forEach((key) => { draft[key] = ''; });
        await load();
    } catch (error) {
        createError.value = true;
        createMessage.value = errorMessage(error);
    } finally {
        submitting.value = false;
    }
}

async function sign(id) {
    if (signingId.value) return;
    signingId.value = id;
    signError.value = '';
    try {
        await api.post(`/prescriptions/${encodeURIComponent(id)}/sign`);
        await load();
    } catch (error) {
        signError.value = errorMessage(error);
    } finally {
        signingId.value = '';
    }
}
</script>

<style scoped>
.prescriptions { max-width: 920px; margin: 0 auto; color: #0f172a; }
.prescriptions h1 { font-size: 1.6rem; font-weight: 700; margin: 0.5rem 0 1rem; }
.prescriptions h2 { font-size: 1.2rem; font-weight: 600; }
.prescriptions label { font-weight: 600; }
.prescriptions__card { margin: 1rem 0; padding: 1rem; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; }
.prescriptions__row { display: flex; gap: 0.5rem; }
.prescriptions :is(input, select) { min-height: 44px; width: 100%; padding: 0.5rem; border: 1px solid #64748b; border-radius: 6px; font: inherit; }
.prescriptions button { min-height: 44px; padding: 0.55rem 1rem; border: 1px solid #1d4ed8; border-radius: 6px; background: #1d4ed8; color: #fff; font: inherit; font-weight: 600; }
.prescriptions button:disabled { opacity: 0.6; }
.prescriptions__grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 1rem; }
.prescriptions__field { display: grid; gap: 0.25rem; margin-bottom: 0.75rem; }
.prescriptions__list { display: grid; gap: 0.5rem; padding: 0; list-style: none; }
.prescriptions__message { margin: 0.75rem 0 0; padding: 0.6rem 0.85rem; border-left: 4px solid #64748b; background: #f1f5f9; }
.prescriptions__message--error { color: #7f1d1d; border-color: #dc2626; background: #fee2e2; }
.prescriptions__message--warning { color: #713f12; border-color: #ca8a04; background: #fef9c3; }
.prescriptions__field-error { color: #991b1b; font-size: 0.9rem; }
.prescriptions :is(input, select)[aria-invalid="true"] { border-color: #dc2626; }
.prescriptions :is(button, input, select):focus-visible { outline: 3px solid #2563eb; outline-offset: 2px; }
@media (max-width: 600px) { .prescriptions__row { flex-direction: column; } .prescriptions__grid { grid-template-columns: 1fr; } }
</style>
