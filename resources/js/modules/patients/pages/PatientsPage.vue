<template>
    <section class="patients" aria-labelledby="patients-title">
        <h1 id="patients-title">Pacientes</h1>
        <p v-if="!canRead && !canRegister && !canEdit" role="alert">La sesión no tiene permisos de Pacientes en el hospital activo.</p>
        <nav v-if="canRead || canRegister" class="patients__nav" aria-label="Pacientes">
            <RouterLink :to="{ name: 'patients' }">Buscar</RouterLink>
            <RouterLink v-if="canRegister" :to="{ name: 'patients-new' }">Nuevo paciente</RouterLink>
        </nav>
        <form v-if="canRead && route.name === 'patients'" class="patients__card" @submit.prevent="searchPatient">
            <label for="patient-code">Buscar por código</label>
            <input id="patient-code" v-model.trim="search" autocomplete="off">
            <button type="submit" :disabled="loading">{{ loading ? 'Buscando…' : 'Buscar paciente' }}</button>
        </form>
        <p v-if="loading" role="status">Consultando la API de Pacientes…</p>
        <p v-if="message" :class="['patients__error', { 'patients__success': success }]" :role="success ? 'status' : 'alert'">{{ message }}</p>
        <p v-if="(route.name === 'patients-new' || route.name === 'patients-edit') && !allowedForm" role="alert">La sesión no tiene permiso para esta operación.</p>
        <article v-if="canRead && route.name === 'patients-detail' && patient" class="patients__card">
            <h2>Detalle del paciente</h2>
            <dl>
                <dt>Código</dt><dd>{{ patient.code }}</dd>
                <dt>Nombre</dt><dd>{{ patient.given_name }} {{ patient.family_name }}</dd>
                <dt>Fecha de nacimiento</dt><dd>{{ patient.birth_date }}</dd>
                <dt>Estado</dt><dd>{{ patient.status }}</dd>
            </dl>
            <div class="patients__actions">
                <button v-if="canEdit" type="button" @click="go('patients-edit', patient.code)">Editar</button>
                <button class="patients__secondary" type="button" @click="go('patients')">Nueva búsqueda</button>
            </div>
        </article>
        <form v-if="allowedForm" class="patients__card" @submit.prevent="save">
            <h2>{{ isCreate ? 'Registrar paciente' : 'Editar paciente' }}</h2>
            <div class="patients__form">
                <label v-for="field in visibleFields" :key="field.key" :for="`patient-${field.key}`">
                    {{ field.label }}
                    <select v-if="field.key === 'sex' || field.key === 'status'" :id="`patient-${field.key}`" v-model="form[field.key]" :aria-invalid="Boolean(errors[field.key])">
                        <option v-for="option in options[field.key]" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                    <input v-else :id="`patient-${field.key}`" v-model.trim="form[field.key]" :type="field.key === 'birth_date' ? 'date' : 'text'" :required="field.required" :aria-invalid="Boolean(errors[field.key])" :aria-describedby="errors[field.key] ? `error-${field.key}` : null">
                    <small v-if="errors[field.key]" :id="`error-${field.key}`" class="patients__error">{{ errors[field.key] }}</small>
                </label>
            </div>
            <div class="patients__actions">
                <button type="submit" :disabled="loading">{{ loading ? 'Guardando…' : 'Guardar paciente' }}</button>
                <button class="patients__secondary" type="button" @click="go('patients')">Cancelar</button>
            </div>
        </form>
    </section>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/plugins/axios';
import { can } from '@/router/permissions';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const canRead = computed(() => can(auth.user, 'pacientes.ver'));
const canRegister = computed(() => can(auth.user, 'pacientes.registrar'));
const canEdit = computed(() => can(auth.user, 'pacientes.editar'));
const isCreate = computed(() => route.name === 'patients-new');
const allowedForm = computed(() => isCreate.value ? canRegister.value : route.name === 'patients-edit' && canEdit.value);
const patient = ref(null);
const search = ref('');
const loading = ref(false);
const message = ref('');
const success = ref(false);
const errors = reactive({});
const form = reactive({ code: '', global_id: '', given_name: '', family_name: '', birth_date: '', sex: '', contact: '', status: 'active' });
const fields = [
    { key: 'code', label: 'Código', required: true }, { key: 'global_id', label: 'Global ID', required: true },
    { key: 'given_name', label: 'Nombres', required: true }, { key: 'family_name', label: 'Apellidos', required: true },
    { key: 'birth_date', label: 'Fecha de nacimiento', required: true }, { key: 'sex', label: 'Sexo', required: true },
    { key: 'contact', label: 'Contacto', required: false }, { key: 'status', label: 'Estado', required: false },
];
const visibleFields = computed(() => isCreate.value ? fields : fields.filter(({ key }) => !['code', 'global_id'].includes(key)));
const options = {
    sex: [{ value: '', label: 'Seleccionar' }, ...['female:Femenino', 'male:Masculino', 'other:Otro', 'unknown:Desconocido'].map((item) => {
        const [value, label] = item.split(':'); return { value, label };
    })],
    status: ['active:Activo', 'inactive:Inactivo', 'deceased:Fallecido'].map((item) => {
        const [value, label] = item.split(':'); return { value, label };
    }),
};
const statusMessage = (status) => ({
    401: 'La sesión no es válida. Inicia sesión nuevamente.',
    403: 'No tienes permiso para esta operación en el hospital activo.',
    404: 'No se encontró el paciente solicitado.',
    409: 'El código o identificador global ya está registrado.',
    422: 'Revisa los datos indicados y vuelve a intentar.',
})[status] ?? 'No se pudo completar la operación. Intenta nuevamente.';

function go(name, code) {
    router.push({ name, ...(code ? { query: { code } } : {}) });
}

function resetErrors() {
    Object.keys(errors).forEach((key) => delete errors[key]);
    message.value = '';
    success.value = false;
}

async function loadPatient(code) {
    loading.value = true;
    resetErrors();
    try {
        const { data } = await api.get(`/patients/${encodeURIComponent(code)}`);
        patient.value = data.patient;
        Object.assign(form, data.patient);
        return true;
    } catch (error) {
        patient.value = null;
        message.value = statusMessage(error.response?.status);
        return false;
    } finally {
        loading.value = false;
    }
}

async function searchPatient() {
    if (!search.value || loading.value) return;
    if (await loadPatient(search.value)) go('patients-detail', search.value);
}

watch(() => [route.name, route.query.code], async ([name, queryCode]) => {
    if (name !== 'patients-detail' || !success.value) resetErrors();
    if (name === 'patients-new') Object.assign(form, { code: '', global_id: '', given_name: '', family_name: '', birth_date: '', sex: '', contact: '', status: 'active' });
    if (name === 'patients-edit' || name === 'patients-detail') {
        const code = String(queryCode ?? patient.value?.code ?? '');
        if (!code) {
            message.value = 'Busca un paciente antes de abrir su detalle o edición.';
            return;
        }
        search.value = code;
        if (patient.value?.code !== code && !(await loadPatient(code))) return;
        if (name === 'patients-edit') Object.assign(form, patient.value);
    }
}, { immediate: true });

async function save() {
    if (loading.value) return;
    resetErrors();
    loading.value = true;
    const payload = isCreate.value
        ? Object.fromEntries(fields.map(({ key }) => [key, form[key] || null]))
        : Object.fromEntries(fields.filter(({ key }) => !['code', 'global_id'].includes(key)).map(({ key }) => [key, form[key] || null]));
    try {
        const response = isCreate.value
            ? await api.post('/patients', payload)
            : await api.patch(`/patients/${encodeURIComponent(patient.value.code)}`, payload);
        patient.value = response.data.patient;
        success.value = true;
        message.value = isCreate.value ? 'Paciente registrado correctamente.' : 'Paciente actualizado correctamente.';
        go('patients-detail', patient.value.code);
    } catch (error) {
        const status = error.response?.status;
        message.value = statusMessage(status);
        const field = error.response?.data?.field;
        if (field && field in form) errors[field] = message.value;
        if (status === 404) patient.value = null;
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
.patients{max-width:900px;margin:0 auto;color:#0f172a}.patients h1{font-size:1.8rem;margin:.5rem 0 1rem}.patients h2{font-size:1.25rem}.patients__notice{padding:.75rem;background:#fff7ed;border:1px solid #c2410c;border-radius:.4rem;color:#7c2d12}.patients__nav,.patients__actions{display:flex;flex-wrap:wrap;gap:.75rem;margin:1rem 0}.patients__nav a{padding:.5rem;color:#1d4ed8}.patients__card{margin:1rem 0;padding:1.25rem;background:white;border:1px solid #cbd5e1;border-radius:.5rem}.patients__card form,.patients__card input,.patients__card select{width:100%}.patients label{display:grid;gap:.35rem;font-weight:600}.patients input,.patients select{min-height:44px;padding:.55rem;border:1px solid #64748b;border-radius:.3rem;font:inherit}.patients button{min-height:44px;padding:.6rem 1rem;border:1px solid #1d4ed8;border-radius:.35rem;background:#1d4ed8;color:white;font:inherit;font-weight:600;cursor:pointer}.patients button:focus-visible,.patients a:focus-visible,.patients input:focus-visible,.patients select:focus-visible{outline:3px solid #f59e0b;outline-offset:2px}.patients__secondary{background:white!important;color:#1e3a8a!important}.patients__form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.patients dl{display:grid;grid-template-columns:minmax(10rem,1fr) 2fr;gap:.5rem}.patients dt{font-weight:600}.patients dd{margin:0;overflow-wrap:anywhere}.patients__error{color:#991b1b}.patients__hint{color:#334155}.patients__permissions{padding-left:1.25rem}@media(max-width:600px){.patients__form{grid-template-columns:1fr}.patients__actions{flex-direction:column}.patients__actions button,.patients form>button{width:100%}.patients dl{grid-template-columns:1fr;gap:.15rem}.patients dd{margin-bottom:.6rem}.patients__card{padding:1rem}}
.patients__success{color:#14532d}
</style>
