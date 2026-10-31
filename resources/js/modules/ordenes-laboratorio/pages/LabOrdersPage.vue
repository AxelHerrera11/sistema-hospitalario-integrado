<script setup>
import { onMounted, reactive, ref } from 'vue';
import api from '@/plugins/axios';

const orders = ref([]); const loading = ref(false); const error = ref(''); const notice = ref('');
const form = reactive({ local_patient_id: '', care_episode_id: '', lab_test_id: '', priority: 'ROUTINE', idempotency_key: '' });
const message = (e) => e.response?.data?.error?.code ?? 'No fue posible completar la operación.';

async function load() {
    loading.value = true; error.value = '';
    try { orders.value = (await api.get('/lab-orders')).data.data.lab_orders; } catch (e) { error.value = message(e); } finally { loading.value = false; }
}
async function create() {
    notice.value = ''; error.value = '';
    try {
        const payload = { ...form, items: [{ lab_test_id: form.lab_test_id }] };
        await api.post('/lab-orders', payload, { headers: { 'Idempotency-Key': form.idempotency_key || crypto.randomUUID() } }); notice.value = 'Orden emitida en HOSPITAL.'; Object.assign(form, { local_patient_id: '', care_episode_id: '', lab_test_id: '', idempotency_key: '' }); await load();
    } catch (e) { error.value = message(e); }
}
async function advance(order) {
    const next = { REQUESTED: 'COLLECTED', COLLECTED: 'IN_PROCESS', IN_PROCESS: 'RESULTED', RESULTED: 'VALIDATED' }[order.status];
    if (!next) return;
    try { await api.patch(`/lab-orders/${order.lab_order_id}/status`, { status: next }); await load(); } catch (e) { error.value = message(e); }
}
async function cancel(order) {
    const reason = window.prompt('Motivo de cancelación (mínimo 5 caracteres):');
    if (!reason) return;
    try { await api.post(`/lab-orders/${order.lab_order_id}/cancellation`, { reason }, { headers: { 'Idempotency-Key': crypto.randomUUID() } }); await load(); } catch (e) { error.value = message(e); }
}
onMounted(load);
</script>

<template>
    <section class="orders" aria-labelledby="orders-title">
        <h1 id="orders-title">Órdenes de laboratorio</h1>
        <p>Emisión y seguimiento persistidos en HOSPITAL. Los identificadores deben corresponder al paciente, episodio y prueba existentes.</p>
        <form class="orders__card orders__grid" @submit.prevent="create">
            <label>Paciente (UUID)<input v-model="form.local_patient_id" required></label>
            <label>Episodio (UUID)<input v-model="form.care_episode_id" required></label>
            <label>Prueba (UUID)<input v-model="form.lab_test_id" required></label>
            <label>Prioridad<select v-model="form.priority"><option value="ROUTINE">Rutina</option><option value="URGENT">Urgente</option><option value="STAT">STAT</option></select></label>
            <button type="submit">Emitir orden</button>
        </form>
        <p v-if="error" class="orders__error" role="alert">{{ error }}</p><p v-if="notice" role="status">{{ notice }}</p>
        <p v-if="loading" role="status">Consultando órdenes…</p>
        <table v-else class="orders__table"><caption>Órdenes del hospital actual</caption><thead><tr><th>Paciente</th><th>Prioridad</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            <tr v-for="order in orders" :key="order.lab_order_id"><td>{{ order.local_patient_id }}</td><td>{{ order.priority }}</td><td>{{ order.status }}</td><td><button v-if="!['VALIDATED','CANCELLED'].includes(order.status)" @click="advance(order)">Avanzar</button><button v-if="!['VALIDATED','CANCELLED'].includes(order.status)" @click="cancel(order)">Cancelar</button></td></tr>
            <tr v-if="!orders.length"><td colspan="4">No hay órdenes registradas.</td></tr>
        </tbody></table>
    </section>
</template>

<style scoped>
.orders { display: grid; gap: 1rem; max-width: 70rem; }.orders__card { padding: 1rem; border: 1px solid #e2e8f0; border-radius: .5rem; background: #fff; }.orders__grid { display: grid; gap: .75rem; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); align-items: end; }.orders label { display: grid; gap: .25rem; }.orders input, .orders select { padding: .5rem; border: 1px solid #94a3b8; border-radius: .375rem; }.orders button { padding: .5rem .75rem; border: 0; border-radius: .375rem; background: #1d4ed8; color: #fff; cursor: pointer; margin-right: .4rem; }.orders__table { width: 100%; border-collapse: collapse; background: #fff; }.orders__table th, .orders__table td { padding: .6rem; border-bottom: 1px solid #e2e8f0; text-align: left; }.orders__error { color: #b91c1c; }
</style>
