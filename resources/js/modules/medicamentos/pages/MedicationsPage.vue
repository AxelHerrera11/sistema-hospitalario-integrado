<template>
    <section class="meds" aria-labelledby="meds-title">
        <h1 id="meds-title">Catálogo de medicamentos</h1>
        <p v-if="!canRead" class="meds__msg meds__msg--warn" role="alert">
            Su sesión no tiene el permiso <code>medicamentos.ver</code> en el hospital activo.
        </p>

        <form v-if="canRead" class="meds__card" role="search" @submit.prevent="search">
            <label for="meds-q">Buscar por código, nombre o principio activo</label>
            <div class="meds__row">
                <input id="meds-q" v-model="term" autocomplete="off" maxlength="60" :aria-invalid="Boolean(searchError)" aria-describedby="meds-q-help">
                <button type="submit" :disabled="searching">{{ searching ? 'Buscando…' : 'Buscar' }}</button>
            </div>
            <p id="meds-q-help" class="meds__hint">De 2 a 60 caracteres; no distingue mayúsculas.</p>
        </form>
        <p v-if="searchError" class="meds__msg meds__msg--error" role="alert">
            {{ searchError }} <button v-if="searchRetry" type="button" class="meds__secondary" @click="search">Reintentar</button>
        </p>
        <p v-else-if="searched && !searching && results.length === 0" class="meds__msg" role="status">No hay medicamentos que coincidan.</p>

        <ul v-if="results.length" class="meds__list" aria-label="Resultados">
            <li v-for="item in results" :key="item.medication_id" class="meds__item">
                <button type="button" class="meds__pick" :aria-pressed="selected?.medication_id === item.medication_id" @click="select(item)">
                    <strong>{{ item.code }}</strong> · {{ item.name }}
                    <span :class="['meds__badge', `meds__badge--${item.validity}`]">{{ validityLabel(item.validity) }}</span>
                </button>
            </li>
        </ul>

        <article v-if="selected" class="meds__card" aria-live="polite">
            <h2>{{ selected.name }}</h2>
            <dl class="meds__detail">
                <dt>Código</dt><dd>{{ selected.code }}</dd>
                <dt>Principio activo</dt><dd>{{ selected.active_ingredient }}</dd>
                <dt>Grupo farmacológico</dt><dd>{{ selected.pharmacological_group }}</dd>
                <dt>Vigencia</dt><dd><span :class="['meds__badge', `meds__badge--${selected.validity}`]">{{ validityLabel(selected.validity) }}</span></dd>
            </dl>
            <button v-if="canChange" ref="validityButton" type="button" class="meds__secondary" :disabled="changing" @click="askValidity">
                {{ changing ? 'Guardando…' : (selected.validity === 'active' ? 'Marcar como no vigente' : 'Marcar como vigente') }}
            </button>
            <p v-if="validityMessage" :class="['meds__msg', validityOk ? 'meds__msg--ok' : 'meds__msg--error']" role="status">{{ validityMessage }}</p>
            <dialog ref="validityDialog" class="meds__dialog" aria-labelledby="meds-vigencia-titulo">
                <h2 id="meds-vigencia-titulo">{{ confirmation.title }}</h2>
                <dl class="meds__detail">
                    <dt>Código</dt><dd>{{ selected.code }}</dd>
                    <dt>Nombre</dt><dd>{{ selected.name }}</dd>
                    <dt>Principio activo</dt><dd>{{ selected.active_ingredient }}</dd>
                    <dt>Nuevo estado</dt><dd>{{ confirmation.target }}</dd>
                </dl>
                <p>El registro no se borra y sigue disponible para consulta.</p>
                <div class="meds__row">
                    <button type="button" class="meds__secondary" @click="validityDialog.close()">Cancelar</button>
                    <button type="button" @click="confirmValidity">{{ confirmation.action }}</button>
                </div>
            </dialog>
        </article>

        <form v-if="canRegister" class="meds__card" novalidate @submit.prevent="register">
            <h2>Registrar medicamento</h2>
            <div class="meds__grid">
            <div v-for="field in fields" :key="field.key" class="meds__field">
                <label :for="`meds-${field.key}`">{{ field.label }}</label>
                <input :id="`meds-${field.key}`" v-model="draft[field.key]" :maxlength="field.max" autocomplete="off"
                       :aria-invalid="Boolean(errors[field.key])" :aria-describedby="errors[field.key] ? `meds-${field.key}-err` : null">
                <p v-if="errors[field.key]" :id="`meds-${field.key}-err`" class="meds__field-error">{{ errors[field.key] }}</p>
            </div>
            </div>
            <button type="submit" :disabled="submitting">{{ submitting ? 'Registrando…' : 'Registrar' }}</button>
            <p v-if="registerMessage" :class="['meds__msg', registerOk ? 'meds__msg--ok' : 'meds__msg--error']" role="status">
                {{ registerMessage }} <button v-if="registerRetry" type="button" class="meds__secondary" @click="register">Reintentar</button>
            </p>
        </form>
    </section>
</template>

<script setup>
import { computed, reactive, ref, nextTick } from 'vue';
import api from '@/plugins/axios';
import { can } from '@/router/permissions';
import { useAuthStore } from '@/stores/auth';
import { failureMessage, isRetryable, nextValidity, PERMISSIONS, searchTermError, validateDraft, validityConfirmation, validityLabel } from '@/modules/medicamentos/medicamentos';

const auth = useAuthStore();
// Mostrar u ocultar acciones solo mejora la navegación: el servidor vuelve a exigir cada permiso.
const canRead = computed(() => can(auth.user, PERMISSIONS.read));
const canRegister = computed(() => can(auth.user, PERMISSIONS.register));
const canChange = computed(() => can(auth.user, PERMISSIONS.changeValidity));

const fields = [
    { key: 'code', label: 'Código', max: 20 },
    { key: 'name', label: 'Nombre', max: 120 },
    { key: 'active_ingredient', label: 'Principio activo', max: 120 },
    { key: 'pharmacological_group', label: 'Grupo farmacológico', max: 120 },
];
const term = ref('');
const results = ref([]);
const searched = ref(false);
const searching = ref(false);
const searchError = ref('');
const searchRetry = ref(false);
const selected = ref(null);
const changing = ref(false);
const validityMessage = ref('');
const validityOk = ref(false);
const validityDialog = ref(null);
const validityButton = ref(null);
const confirmation = computed(() => (selected.value ? validityConfirmation(selected.value) : {}));
const draft = reactive({ code: '', name: '', active_ingredient: '', pharmacological_group: '' });
const errors = reactive({});
const submitting = ref(false);
const registerMessage = ref('');
const registerOk = ref(false);
const registerRetry = ref(false);

const statusOf = (error) => error.response?.status ?? 0;

async function search() {
    if (searching.value) return;
    searchError.value = searchTermError(term.value) ?? '';
    searchRetry.value = false;
    if (searchError.value) return;
    searching.value = true;
    try {
        const { data } = await api.get('/medications', { params: { q: term.value.trim() } });
        results.value = data.results ?? [];
        searched.value = true;
    } catch (error) {
        results.value = [];
        searchError.value = failureMessage(statusOf(error), error.response?.data);
        searchRetry.value = isRetryable(statusOf(error));
    } finally {
        searching.value = false;
    }
}

async function select(item) {
    validityMessage.value = '';
    try {
        const { data } = await api.get(`/medications/${item.medication_id}`);
        selected.value = data.medication;
    } catch (error) {
        selected.value = null;
        searchError.value = failureMessage(statusOf(error), error.response?.data);
    }
}

function askValidity() {
    validityMessage.value = '';
    validityDialog.value.showModal();
}

function confirmValidity() {
    validityDialog.value.close();
    toggleValidity();
}

async function toggleValidity() {
    if (changing.value || !selected.value) return;
    changing.value = true;
    validityMessage.value = '';
    try {
        const { data } = await api.patch(`/medications/${selected.value.medication_id}/validity`, { validity: nextValidity(selected.value.validity) });
        selected.value = data.medication;
        results.value = results.value.map((item) => (item.medication_id === data.medication.medication_id ? data.medication : item));
        validityOk.value = true;
        validityMessage.value = `Vigencia actualizada: ${validityLabel(data.medication.validity)}.`;
    } catch (error) {
        validityOk.value = false;
        validityMessage.value = failureMessage(statusOf(error), error.response?.data);
    } finally {
        changing.value = false;
        // El botón se deshabilita mientras guarda y pierde el foco que le devolvió el diálogo.
        await nextTick();
        validityButton.value?.focus();
    }
}

async function register() {
    if (submitting.value) return;
    registerMessage.value = '';
    registerRetry.value = false;
    Object.keys(errors).forEach((key) => delete errors[key]);
    Object.assign(errors, validateDraft(draft));
    if (Object.keys(errors).length) return;
    submitting.value = true;
    try {
        const { data } = await api.post('/medications', { ...draft });
        registerOk.value = true;
        registerMessage.value = `Registrado ${data.medication.code} (${validityLabel(data.medication.validity)}).`;
        Object.keys(draft).forEach((key) => { draft[key] = ''; });
    } catch (error) {
        const status = statusOf(error);
        registerOk.value = false;
        registerMessage.value = failureMessage(status, error.response?.data);
        registerRetry.value = isRetryable(status);
        if (status === 409) errors.code = registerMessage.value;
        if (status === 422 && error.response?.data?.field) errors[error.response.data.field] = registerMessage.value;
    } finally {
        submitting.value = false;
    }
}
</script>

<style scoped>
/* El preflight de Tailwind quita bordes, fondos y tamaños a campos, botones y títulos; se restauran con los tokens de Pacientes. */
.meds { max-width: 920px; margin: 0 auto; color: #0f172a; }
.meds h1 { font-size: 1.6rem; font-weight: 700; margin: 0.5rem 0 1rem; }
.meds h2 { font-size: 1.2rem; font-weight: 600; margin: 0 0 0.75rem; }
.meds label { font-weight: 600; }
.meds__card { border: 1px solid #cbd5e1; border-radius: 8px; padding: 1rem; margin: 1rem 0; background: #fff; }
.meds__row { display: flex; gap: 0.5rem; }
.meds input { min-height: 44px; padding: 0.5rem 0.65rem; border: 1px solid #64748b; border-radius: 6px; background: #fff; font: inherit; }
.meds__row input, .meds__field input { flex: 1; width: 100%; }
.meds button { min-height: 44px; padding: 0.55rem 1rem; border: 1px solid #1d4ed8; border-radius: 6px; background: #1d4ed8;
    color: #fff; font: inherit; font-weight: 600; cursor: pointer; }
.meds button:disabled { opacity: 0.6; cursor: progress; }
/* Jerarquía: el primario envía (Buscar, Registrar); las acciones secundarias van en contorno y los resultados como filas. */
.meds .meds__secondary { background: #fff; color: #1e3a8a; }
/* La hoja base pone margin: 0 y anula el centrado nativo de dialog. */
.meds__dialog { position: fixed; inset: 0; margin: auto; width: min(28rem, calc(100vw - 2rem)); height: fit-content; max-height: calc(100vh - 2rem); max-height: calc(100dvh - 2rem); overflow-y: auto; overflow-wrap: anywhere; border: 1px solid #cbd5e1; border-radius: 8px; padding: 1rem; }
.meds__dialog::backdrop { background: rgb(15 23 42 / 0.5); }
.meds .meds__pick { background: #fff; color: #0f172a; border-color: #cbd5e1; font-weight: 400; }
.meds .meds__pick:hover { border-color: #1d4ed8; background: #eff6ff; }
.meds .meds__pick[aria-pressed="true"] { border-color: #1d4ed8; background: #eff6ff; box-shadow: inset 4px 0 0 #1d4ed8; }
.meds__grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 1rem; }
.meds__field { display: grid; gap: 0.25rem; margin-bottom: 0.75rem; align-content: start; }
.meds__hint { color: #475569; font-size: 0.9rem; margin: 0.35rem 0 0; }
.meds__list { list-style: none; padding: 0; margin: 0.75rem 0; display: grid; gap: 0.4rem; }
.meds__pick { width: 100%; text-align: left; display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; min-height: 2.75rem; }
/* La vigencia se distingue por texto, color y trazo (continuo o discontinuo), no solo por color. */
.meds__badge { display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 999px; padding: 0.1rem 0.6rem;
    font-size: 0.85rem; font-weight: 600; border: 1px solid; }
.meds__badge::before { content: ""; width: 0.5rem; height: 0.5rem; border-radius: 50%; background: currentColor; }
.meds__badge--active { background: #dcfce7; color: #14532d; border-color: #16a34a; }
.meds__badge--inactive { background: #f1f5f9; color: #334155; border: 1px dashed #64748b; }
.meds__badge--inactive::before { background: transparent; border: 2px solid currentColor; }
.meds__detail { display: grid; grid-template-columns: max-content 1fr; gap: 0.35rem 1.25rem; margin: 0 0 1rem; }
.meds__detail dt { color: #475569; }
.meds__detail dd { margin: 0; overflow-wrap: anywhere; }
.meds__msg { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 0.75rem; margin: 0.75rem 0 0; padding: 0.6rem 0.85rem;
    border-radius: 6px; border-left: 4px solid #64748b; background: #f1f5f9; }
.meds__msg--ok { background: #dcfce7; color: #14532d; border-left-color: #16a34a; }
.meds__msg--warn { background: #fef9c3; color: #713f12; border-left-color: #ca8a04; }
.meds__msg--error { background: #fee2e2; color: #7f1d1d; border-left-color: #dc2626; }
.meds__field-error { margin: 0; color: #991b1b; font-size: 0.9rem; }
.meds input[aria-invalid="true"] { border-color: #dc2626; box-shadow: 0 0 0 1px #dc2626; }
button:focus-visible, input:focus-visible { outline: 3px solid #2563eb; outline-offset: 2px; }
@media (max-width: 600px) {
    .meds__row { flex-direction: column; }
    .meds__grid { grid-template-columns: 1fr; }
    .meds__card { padding: 0.85rem; }
    .meds form > button, .meds article > button { width: 100%; }
    .meds__detail { grid-template-columns: 1fr; gap: 0; }
    .meds__detail dt { font-size: 0.85rem; }
    .meds__detail dd { margin-bottom: 0.5rem; }
}
</style>
