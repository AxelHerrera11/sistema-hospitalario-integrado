<script setup>
import { computed, nextTick, onUnmounted, reactive, ref } from 'vue';
import api from '@/plugins/axios';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const canReceive = computed(() => auth.user?.permissions?.includes('muestras.recibir'));
const canRead = computed(() => auth.user?.permissions?.includes('muestras.ver'));
const canPrint = computed(() => auth.user?.permissions?.includes('muestras.etiquetar'));
const orderSearch = ref('');
const orders = ref(null);

const form = reactive({ item_id: '', patient_code: '', patient_name: '', type: 'blood',
    container: '', volume: 0, temperature: 20, collected_at: '',
    hemolizada: false, coagulada: false, derramada: false, sin_etiqueta: false });
const sample = ref(null);
const code = ref('');
const busy = ref(false);
const error = ref('');
const validation = ref({});
const uncertain = ref(false);
const pending = ref(null);
const errorSummary = ref(null);
function preventLostReception(event) {
    if (uncertain.value || busy.value) { event.preventDefault(); event.returnValue = ''; }
}
window.addEventListener('beforeunload', preventLostReception);
onUnmounted(() => window.removeEventListener('beforeunload', preventLostReception));

async function perform(action, mayHaveSaved = false) {
    busy.value = true;
    error.value = '';
    validation.value = {};
    try { await action(); }
    catch (e) {
        error.value = e.response?.data?.message ?? 'No se pudo confirmar la respuesta del servidor.';
        validation.value = e.response?.data?.errors ?? {};
        if (mayHaveSaved && (!e.response || e.response.status >= 500)) uncertain.value = true;
        await nextTick();
        errorSummary.value?.focus();
    } finally { busy.value = false; }
}
function receive() {
    if (busy.value || uncertain.value || sample.value) return;
    perform(async () => {
        pending.value = { ...form, request_key: crypto.randomUUID(),
            collected_at: new Date(form.collected_at).toISOString().replace('.000Z', '+00:00') };
        sample.value = (await api.post('/lab/specimens', pending.value)).data.data;
        pending.value = null;
    }, true);
}
function recover() {
    if (busy.value || !pending.value) return;
    perform(async () => {
        sample.value = (await api.post('/lab/specimens', pending.value)).data.data;
        uncertain.value = false;
        pending.value = null;
    }, true);
}
function lookup() {
    if (busy.value || uncertain.value || !code.value.trim()) return;
    perform(async () => { sample.value = (await api.get(`/lab/specimens/${encodeURIComponent(code.value.trim())}`)).data.data; });
}
function findOrders() {
    if (busy.value || uncertain.value || orderSearch.value.trim().length < 2) return;
    perform(async () => {
        orders.value = (await api.get('/lab/specimens/pending-orders', { params: { search: orderSearch.value.trim() } })).data.data;
    });
}
function printLabel() {
    if (busy.value || !canPrint.value || !sample.value?.code) return;
    perform(async () => {
        const label = (await api.get(`/lab/specimens/${encodeURIComponent(sample.value.code)}/label`)).data.data;
        if (label.code !== sample.value.code) throw new Error('La etiqueta no corresponde a la muestra');
        window.print();
    });
}
async function nextSample() {
    if (uncertain.value) return;
    sample.value = null; pending.value = null; error.value = '';
    orders.value = null; orderSearch.value = '';
    Object.assign(form, { item_id: '', patient_code: '', patient_name: '', type: 'blood', container: '',
        volume: 0, temperature: 20, collected_at: '', hemolizada: false, coagulada: false, derramada: false, sin_etiqueta: false });
    await nextTick();
    document.getElementById('item')?.focus();
}
</script>

<template>
    <main class="reception">
        <h1>Recepción de muestras</h1>
        <p>Verifique nombre y código del paciente contra la etiqueta antes de registrar.</p>
        <section v-if="canReceive" aria-labelledby="orders-title">
            <h2 id="orders-title">Localizar orden pendiente</h2>
            <form @submit.prevent="findOrders">
                <label for="order-search">Ítem o código del paciente (mínimo dos caracteres)</label>
                <input id="order-search" v-model="orderSearch" minlength="2" maxlength="100" required>
                <button :disabled="busy || uncertain">Buscar orden</button>
            </form>
            <p v-if="orders !== null && !orders.length" role="status">No hay órdenes pendientes que coincidan.</p>
            <ul v-if="orders?.length" class="orders" aria-label="Órdenes pendientes">
                <li v-for="order in orders" :key="order.item_id">
                    <strong>{{ order.item_id }} — {{ order.patient_name }} ({{ order.patient_code }})</strong>
                    <p>Requiere {{ order.type }}, {{ order.container }}, mínimo {{ order.min_volume }} ml;
                        estabilidad {{ order.max_minutes }} min y {{ order.min_temperature }}–{{ order.max_temperature }} °C.</p>
                    <button :disabled="busy || uncertain || !!sample" @click="form.item_id = order.item_id">Seleccionar ítem {{ order.item_id }}</button>
                </li>
            </ul>
        </section>
        <p role="alert" v-if="error" ref="errorSummary" tabindex="-1">{{ error }}</p>
        <ul v-if="Object.keys(validation).length" aria-label="Campos que debe corregir">
            <li v-for="(messages, field) in validation" :key="field">{{ field }}: {{ messages.join(' ') }}</li>
        </ul>
        <p v-if="uncertain" role="alert">Respuesta sin confirmar. Recupere el resultado antes de iniciar otra recepción.</p>
        <button v-if="uncertain && pending" @click="recover" :disabled="busy">Recuperar la misma recepción</button>
        <form v-if="canReceive" @submit.prevent="receive" :aria-busy="busy">
            <fieldset :disabled="busy || !!sample || uncertain">
                <legend>Identificación y observaciones</legend>
                <label for="item">Ítem de orden</label><input id="item" v-model="form.item_id" required maxlength="100">
                <label><input v-model="form.sin_etiqueta" type="checkbox"> Sin etiqueta de identificación (se registrará rechazo)</label>
                <label for="patient">Código del paciente</label><input id="patient" v-model="form.patient_code" :required="!form.sin_etiqueta" maxlength="100">
                <label for="name">Nombre completo</label><input id="name" v-model="form.patient_name" :required="!form.sin_etiqueta" maxlength="200">
                <label for="type">Tipo de muestra</label>
                <select id="type" v-model="form.type"><option value="blood">Sangre</option><option value="urine">Orina</option><option value="swab">Hisopado</option></select>
                <label for="container">Contenedor observado</label><input id="container" v-model="form.container" required maxlength="100">
                <label for="volume">Volumen (ml)</label><input id="volume" v-model.number="form.volume" type="number" min="0" max="1000" step="0.1" required>
                <label for="temperature">Temperatura (°C)</label><input id="temperature" v-model.number="form.temperature" type="number" min="-80" max="100" step="0.1" required>
                <label for="collected">Fecha y hora local de toma</label><input id="collected" v-model="form.collected_at" type="datetime-local" required>
                <label><input v-model="form.hemolizada" type="checkbox"> Hemolizada</label>
                <label><input v-model="form.coagulada" type="checkbox"> Coagulada</label>
                <label><input v-model="form.derramada" type="checkbox"> Derramada</label>
                <button type="submit">{{ busy ? 'Guardando…' : 'Registrar recepción' }}</button>
            </fieldset>
        </form>
        <section aria-live="polite" class="result" v-if="sample">
            <h2>{{ sample.status === 'accepted' ? 'Muestra aceptada' : 'Muestra rechazada' }}</h2>
            <p v-if="sample.reason">Motivo: {{ sample.reason }}</p>
            <div class="label" v-if="sample.code"><strong>{{ sample.code }}</strong><p>Ítem: {{ sample.item_id }}</p><p>{{ sample.received_at }}</p></div>
            <button v-if="sample.code && canPrint" @click="printLabel" :disabled="busy">Imprimir etiqueta</button>
            <p v-if="sample.code">Si falla la impresión, use este mismo botón. La muestra ya está guardada.</p>
        </section>
        <form v-if="canRead" @submit.prevent="lookup">
            <label for="code">Consultar o reimprimir por código</label>
            <input id="code" v-model="code" maxlength="26" required>
            <button :disabled="busy">Consultar</button>
        </form>
        <button v-if="canReceive" @click="nextSample" :disabled="busy || uncertain">Iniciar otra muestra</button>
    </main>
</template>

<style scoped>
.reception { max-width: 48rem; margin: auto; padding: 1rem; color: #172033; background: white; }
.reception, .reception * { box-sizing: border-box; min-width: 0; }
fieldset { display: grid; gap: .6rem; border: 1px solid #596579; }
input, select, button { font: inherit; min-height: 2.75rem; max-width: 100%; padding: .4rem; }
button { cursor: pointer; margin-block: .5rem; }
:focus-visible { outline: 3px solid #184bc2; outline-offset: 2px; }
.label { overflow-wrap: anywhere; border: 2px solid #172033; padding: 1rem; }
.result { overflow-wrap: anywhere; }
@media (max-width: 600px) {
    fieldset input, fieldset select, fieldset button { width: 100%; }
    .orders { padding-left: 1rem; }
}
.orders { padding-left: 1.2rem; overflow-wrap: anywhere; }
@media (max-width: 400px) {
    .reception { padding: .5rem; }
    fieldset { padding: .5rem; }
    .reception form > input, .reception form > button { width: 100%; }
}
@media print {
    .reception > :not(.result), .result > :not(.label) { display: none; }
}
</style>
