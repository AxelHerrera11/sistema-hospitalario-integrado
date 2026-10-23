<template>
    <section class="doc" aria-labelledby="doc-title">
        <h1 id="doc-title">Médicos y especialidades</h1>
        <p v-if="!canRead" class="doc__msg doc__msg--warn" role="alert">
            Su sesión no tiene el permiso <code>{{ PERMISSIONS.availability }}</code> en el hospital activo.
        </p>

        <form v-if="canRead" class="doc__card" aria-labelledby="doc-avail-title" novalidate @submit.prevent="searchAvailability">
            <h2 id="doc-avail-title">Disponibilidad</h2>
            <label for="doc-specialty">Código de especialidad</label>
            <input id="doc-specialty" v-model="query.specialty" autocomplete="off" maxlength="20">
            <label for="doc-date">Fecha (AAAA-MM-DD)</label>
            <input id="doc-date" v-model="query.date" inputmode="numeric" maxlength="10" aria-describedby="doc-avail-error">
            <p id="doc-avail-error" class="doc__msg doc__msg--error" role="alert" :hidden="!availabilityMessage">{{ availabilityMessage }}</p>
            <button type="submit" :disabled="searching">{{ searching ? 'Consultando…' : 'Consultar' }}</button>
            <button v-if="retryAvailability" type="button" class="doc__secondary" @click="searchAvailability">Reintentar</button>
        </form>

        <p v-if="searched && !searching && !availabilityMessage && rows.length === 0" class="doc__msg" role="status">
            No hay médicos con esa especialidad vigente en la fecha indicada.
        </p>
        <table v-if="rows.length" class="doc__table">
            <caption>Franjas libres por médico</caption>
            <thead><tr><th scope="col">Médico</th><th scope="col">Estado</th><th scope="col">Franjas libres</th></tr></thead>
            <tbody>
                <tr v-for="row in rows" :key="row.id">
                    <th scope="row"><code>{{ row.id }}</code></th>
                    <td>{{ row.estado }}</td>
                    <td>{{ row.franjas.join(', ') || '—' }}</td>
                </tr>
            </tbody>
        </table>

        <form v-if="canRegister" class="doc__card" aria-labelledby="doc-reg-title" novalidate @submit.prevent="submit('register')">
            <h2 id="doc-reg-title">Registrar médico</h2>
            <template v-for="field in registerFields" :key="field.name">
                <label :for="`doc-reg-${field.name}`">{{ field.label }}</label>
                <input :id="`doc-reg-${field.name}`" v-model="register[field.name]" autocomplete="off" :aria-invalid="Boolean(registerErrors[field.name])">
                <span v-if="registerErrors[field.name]" class="doc__msg doc__msg--error" role="alert">{{ registerErrors[field.name] }}</span>
            </template>
            <p v-if="registerMessage.text" :class="['doc__msg', `doc__msg--${registerMessage.kind}`]" :role="registerMessage.kind === 'error' ? 'alert' : 'status'">{{ registerMessage.text }}</p>
            <button type="submit" :disabled="saving === 'register'">{{ saving === 'register' ? 'Guardando…' : 'Registrar' }}</button>
        </form>

        <form v-if="canAssociate" class="doc__card" aria-labelledby="doc-assoc-title" novalidate @submit.prevent="submit('associate')">
            <h2 id="doc-assoc-title">Asociar especialidad</h2>
            <template v-for="field in associateFields" :key="field.name">
                <label :for="`doc-assoc-${field.name}`">{{ field.label }}</label>
                <input :id="`doc-assoc-${field.name}`" v-model="associate[field.name]" autocomplete="off" :aria-invalid="Boolean(associateErrors[field.name])">
                <span v-if="associateErrors[field.name]" class="doc__msg doc__msg--error" role="alert">{{ associateErrors[field.name] }}</span>
            </template>
            <label class="doc__check"><input v-model="associate.is_primary" type="checkbox"> Especialidad principal</label>
            <p v-if="associateMessage.text" :class="['doc__msg', `doc__msg--${associateMessage.kind}`]" :role="associateMessage.kind === 'error' ? 'alert' : 'status'">{{ associateMessage.text }}</p>
            <button type="submit" :disabled="saving === 'associate'">{{ saving === 'associate' ? 'Guardando…' : 'Asociar' }}</button>
        </form>
    </section>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import api from '@/plugins/axios';
import { can } from '@/router/permissions';
import { useAuthStore } from '@/stores/auth';
import {
    associationErrors, availabilityError, availabilityRows, doctorErrors, failureMessage, isRetryable, PERMISSIONS,
} from '@/modules/medicos/medicos';

const auth = useAuthStore();
// Mostrar u ocultar formularios solo mejora la navegación: el servidor vuelve a exigir cada permiso.
const canRead = computed(() => can(auth.user, PERMISSIONS.availability));
const canRegister = computed(() => can(auth.user, PERMISSIONS.register));
const canAssociate = computed(() => can(auth.user, PERMISSIONS.associate));

const registerFields = [
    { name: 'local_user_ref_id', label: 'Referencia de usuario local' },
    { name: 'license_number', label: 'Número de licencia' },
    { name: 'specialty_code', label: 'Especialidad principal (código)' },
    { name: 'valid_from', label: 'Vigente desde (AAAA-MM-DD)' },
];
const associateFields = [
    { name: 'doctor_id', label: 'Identificador del médico' },
    { name: 'specialty_code', label: 'Especialidad (código)' },
    { name: 'valid_from', label: 'Vigente desde (AAAA-MM-DD)' },
    { name: 'valid_to', label: 'Vigente hasta (opcional)' },
];

const query = reactive({ specialty: '', date: '' });
const rows = ref([]);
const searching = ref(false);
const searched = ref(false);
const availabilityMessage = ref('');
const retryAvailability = ref(false);

const register = reactive({ local_user_ref_id: '', license_number: '', specialty_code: '', valid_from: '' });
const associate = reactive({ doctor_id: '', specialty_code: '', valid_from: '', valid_to: '', is_primary: false });
const registerErrors = ref({});
const associateErrors = ref({});
const registerMessage = reactive({ kind: 'ok', text: '' });
const associateMessage = reactive({ kind: 'ok', text: '' });
const saving = ref('');

async function searchAvailability() {
    availabilityMessage.value = availabilityError(query);
    retryAvailability.value = false;
    if (availabilityMessage.value) {
        return;
    }
    searching.value = true;
    try {
        const { data } = await api.get('/availability', { params: { specialty: query.specialty.trim(), date: query.date } });
        rows.value = availabilityRows(data);
        searched.value = true;
    } catch (e) {
        rows.value = [];
        availabilityMessage.value = failureMessage(e.response?.data?.code, e.response?.status);
        retryAvailability.value = isRetryable(e.response?.status);
    } finally {
        searching.value = false;
    }
}

const forms = {
    register: { draft: register, errors: registerErrors, message: registerMessage, validate: doctorErrors, done: 'Médico registrado.',
        send: () => api.post('/doctors', { ...register, license_number: register.license_number.trim() }) },
    associate: { draft: associate, errors: associateErrors, message: associateMessage, validate: associationErrors, done: 'Especialidad asociada.',
        send: () => api.post(`/doctors/${encodeURIComponent(associate.doctor_id.trim())}/specialties`, {
            specialty_code: associate.specialty_code.trim(), is_primary: associate.is_primary, valid_from: associate.valid_from, valid_to: associate.valid_to || null,
        }) },
};

async function submit(name) {
    const form = forms[name];
    form.errors.value = form.validate(form.draft);
    form.message.text = '';
    if (Object.keys(form.errors.value).length) {
        return;
    }
    saving.value = name;
    try {
        const { data } = await form.send();
        Object.assign(form.message, { kind: 'ok', text: data.doctor_profile_id ? `${form.done} Identificador: ${data.doctor_profile_id}` : form.done });
    } catch (e) {
        Object.assign(form.message, { kind: 'error', text: failureMessage(e.response?.data?.code, e.response?.status) });
    } finally {
        saving.value = '';
    }
}
</script>

<style scoped>
/* El preflight de Tailwind quita bordes, fondos y tamaños a campos, botones y títulos; se restauran con los mismos tokens que Medicamentos. */
.doc { max-width: 920px; margin: 0 auto; color: #0f172a; }
.doc h1 { font-size: 1.6rem; font-weight: 700; margin: 0.5rem 0 1rem; }
.doc h2 { font-size: 1.2rem; font-weight: 600; margin: 0 0 0.25rem; }
.doc label { font-weight: 600; }
.doc__card { display: flex; flex-direction: column; gap: 0.4rem; max-width: 28rem; margin: 1rem 0; padding: 1rem; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; }
.doc input:not([type="checkbox"]) { min-height: 44px; padding: 0.5rem 0.65rem; border: 1px solid #64748b; border-radius: 6px; background: #fff; font: inherit; width: 100%; }
.doc input[type="checkbox"] { width: 1.25rem; height: 1.25rem; }
.doc input[aria-invalid="true"] { border-color: #dc2626; box-shadow: 0 0 0 1px #dc2626; }
.doc button { min-height: 44px; padding: 0.55rem 1rem; border: 1px solid #1d4ed8; border-radius: 6px; background: #1d4ed8; color: #fff; font: inherit; font-weight: 600; cursor: pointer; }
.doc button:disabled { opacity: 0.6; cursor: progress; }
.doc .doc__secondary { background: #fff; color: #1e3a8a; }
button:focus-visible, input:focus-visible { outline: 3px solid #2563eb; outline-offset: 2px; }
.doc__check { display: flex; flex-direction: row; align-items: center; gap: 0.5rem; }
.doc__msg { margin: 0.25rem 0 0; padding: 0.5rem 0.75rem; border-radius: 6px; border-left: 4px solid #64748b; background: #f1f5f9; font-size: 0.9rem; }
.doc__msg[hidden] { display: none; }
.doc__msg--ok { background: #dcfce7; color: #14532d; border-left-color: #16a34a; }
.doc__msg--warn { background: #fef9c3; color: #713f12; border-left-color: #ca8a04; }
.doc__msg--error { background: #fee2e2; color: #7f1d1d; border-left-color: #dc2626; }
.doc__table { border-collapse: collapse; width: 100%; max-width: 40rem; background: #fff; }
.doc__table caption { text-align: left; font-weight: 600; padding-bottom: 0.25rem; }
.doc__table th, .doc__table td { border-bottom: 1px solid #cbd5e1; padding: 0.5rem; text-align: left; vertical-align: top; }
.doc__table thead th { background: #f1f5f9; }
@media (max-width: 600px) {
    .doc__card { max-width: none; padding: 0.85rem; }
    .doc form > button { width: 100%; }
}
</style>
