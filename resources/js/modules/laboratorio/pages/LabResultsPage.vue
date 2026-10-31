<template>
    <section class="lab-page" aria-labelledby="lab-title">
        <header class="lab-page__header">
            <div><p class="lab-page__eyebrow">ASII-19 · Ingreso de resultados</p><h1 id="lab-title">Resultados pendientes</h1><p>Captura y corrección controlada con auditoría.</p></div>
            <span class="lab-page__status" :data-state="result?.status">{{ result?.status ?? (loaded ? 'sin resultados' : 'cargando') }}</span>
        </header>
        <p v-if="message" class="lab-page__message" role="status">{{ message }}</p>
        <p v-if="error" class="lab-page__error" role="alert">{{ error }}</p>
        <div v-if="result" class="lab-page__grid">
            <article class="lab-page__card">
                <dl><div><dt>Resultado</dt><dd>{{ result.id }}</dd></div><div><dt>Paciente</dt><dd>{{ result.patient_code }}</dd></div><div><dt>Prueba</dt><dd>{{ result.test_code }}</dd></div><div><dt>Unidad esperada</dt><dd>{{ result.expected_unit }}</dd></div><div><dt>Versión</dt><dd>{{ result.version }}</dd></div></dl>
                <form v-if="result.status === 'pendiente'" @submit.prevent="capture"><h2>Capturar resultado</h2><label>Valor<input v-model="form.value" required /></label><label>Unidad<input v-model="form.unit" required /></label><label>Observación<textarea v-model="form.observation" rows="3" /></label><button :disabled="busy">Guardar captura</button></form>
                <form v-else @submit.prevent="correct"><h2>Corrección controlada</h2><label>Nuevo valor<input v-model="form.value" required /></label><label>Unidad<input v-model="form.unit" required /></label><label>Motivo<input v-model="form.reason" required /></label><button :disabled="busy">Guardar corrección</button></form>
            </article>
            <aside class="lab-page__card"><h2>Auditoría</h2><p v-if="! audit.length">Todavía no hay eventos.</p><ol v-else><li v-for="(event, index) in audit" :key="index"><strong>{{ event.action }}</strong> · versión {{ event.metadata?.version }}<small>{{ event.occurred_at }}</small></li></ol></aside>
        </div>
    </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import api from '@/plugins/axios';

const result = ref(null); const audit = ref([]); const loaded = ref(false); const busy = ref(false); const message = ref(''); const error = ref('');
const form = reactive({ value: '', unit: 'mg/dL', observation: '', reason: '' });
async function load() { try { const { data } = await api.get('/lab-results'); const { audit: events = [], ...row } = data.data ?? {}; result.value = row.id ? row : null; audit.value = events; loaded.value = true; } catch (e) { error.value = e.response?.data?.error ?? 'No fue posible cargar el resultado.'; } }
async function send(path, payload) { busy.value = true; message.value = ''; error.value = ''; try { const { data } = await api.post(path, payload); message.value = data.message; form.value = ''; form.observation = ''; form.reason = ''; await load(); } catch (e) { error.value = e.response?.data?.error ?? 'La operación fue rechazada.'; } finally { busy.value = false; } }
function capture() { return send(`/lab-results/${result.value.id}/capture`, { value: form.value, unit: form.unit, observation: form.observation }); }
function correct() { return send(`/lab-results/${result.value.id}/correct`, { value: form.value, unit: form.unit, reason: form.reason }); }
onMounted(load);
</script>

<style scoped>
.lab-page{max-width:1000px;margin:auto}.lab-page__header{display:flex;justify-content:space-between;gap:1rem;align-items:start;margin-bottom:1.25rem}.lab-page__eyebrow{margin:0;color:#2563eb;font-weight:700;font-size:.85rem}.lab-page h1{margin:.25rem 0}.lab-page h2{font-size:1.1rem}.lab-page__status{padding:.35rem .6rem;border:1px solid #cbd5e1;border-radius:.35rem;font-weight:700}.lab-page__status[data-state="pendiente"]{color:#92400e;background:#fef3c7}.lab-page__status[data-state="capturado"],.lab-page__status[data-state="corregido"]{color:#166534;background:#dcfce7}.lab-page__grid{display:grid;grid-template-columns:1.3fr .7fr;gap:1rem}.lab-page__card{padding:1.25rem;background:#fff;border:1px solid #cbd5e1;border-radius:.5rem}.lab-page dl{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem}.lab-page dt{font-size:.8rem;color:#64748b}.lab-page dd{margin:.2rem 0 0;font-weight:700}.lab-page form{display:grid;gap:.75rem;margin-top:1.5rem}.lab-page label{display:grid;gap:.3rem;font-weight:600}.lab-page input,.lab-page textarea{padding:.6rem;border:1px solid #94a3b8;border-radius:.3rem;font:inherit}.lab-page button{padding:.65rem 1rem;border:0;border-radius:.3rem;background:#2563eb;color:#fff;font-weight:700;cursor:pointer}.lab-page button:disabled{opacity:.6}.lab-page__message{padding:.7rem;background:#dcfce7;color:#166534}.lab-page__error{padding:.7rem;background:#fee2e2;color:#991b1b}.lab-page ol{padding-left:1.2rem}.lab-page li{margin:.7rem 0}.lab-page small{display:block;color:#64748b}@media(max-width:700px){.lab-page__grid{grid-template-columns:1fr}.lab-page__header{display:block}.lab-page__status{display:inline-block;margin-top:.5rem}}
</style>
