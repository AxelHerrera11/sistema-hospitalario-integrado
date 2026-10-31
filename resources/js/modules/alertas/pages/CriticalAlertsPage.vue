<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import api from '@/plugins/axios';
import { can } from '@/router/permissions';
import { useAuthStore } from '@/stores/auth';
import { codigoDeError, cuerpoAcuse, debeRecargar, direccionLegible, idCorto, lecturaCompleta, mensajeDeError, vencimientoLegible } from '@/modules/alertas/alertas';

const auth = useAuthStore();
const supervisa = computed(() => can(auth.user, 'alertas.supervisar'));
const bandeja = ref('propia'); // propia | jefatura
const alertas = ref(null); // null = cargando; nunca «sin alertas» antes de la respuesta
const error = ref('');
const aviso = ref('');
const abierta = ref(null);
const lectura = reactive({ confirmado: false, valor: '' });
const errorAcuse = ref('');
const enviando = ref(false);
const ahora = ref(new Date());

const fallo = (e) => mensajeDeError(codigoDeError(e.response?.data), e.response?.status);

async function cargar() {
    alertas.value = null;
    error.value = '';
    ahora.value = new Date();
    try {
        const { data } = await api.get(bandeja.value === 'jefatura' ? '/critical-alerts/service-head' : '/critical-alerts');
        alertas.value = data.data.alerts;
    } catch (e) {
        alertas.value = [];
        error.value = fallo(e);
    }
}

function abrir(alerta) {
    abierta.value = alerta.critical_alert_id;
    Object.assign(lectura, { confirmado: false, valor: '' });
    errorAcuse.value = '';
}

async function acusar(alerta) {
    if (! lecturaCompleta(lectura)) {
        errorAcuse.value = 'Confirme paciente y prueba y escriba el valor que leyó.';
        return;
    }
    enviando.value = true;
    try {
        // La jefatura acusa las alertas sin médico tratante por su propia ruta (alertas.supervisar).
        const ruta = bandeja.value === 'jefatura' ? 'acknowledge-as-service-head' : 'acknowledge';
        await api.post(`/critical-alerts/${alerta.critical_alert_id}/${ruta}`, cuerpoAcuse(alerta, lectura));
        abierta.value = null;
        await cargar();
        aviso.value = 'Alerta acusada. El escalamiento se detuvo.';
    } catch (e) {
        errorAcuse.value = fallo(e);
        if (debeRecargar(codigoDeError(e.response?.data))) {
            await cargar();
        }
    } finally {
        enviando.value = false;
    }
}

onMounted(cargar);
</script>

<template>
    <section class="alr" aria-labelledby="alr-titulo">
        <h1 id="alr-titulo">Alertas críticas</h1>
        <div class="alr__barra">
            <label v-if="supervisa">Bandeja
                <select v-model="bandeja" @change="cargar">
                    <option value="propia">Mis alertas</option>
                    <option value="jefatura">Jefatura del servicio (sin médico tratante)</option>
                </select>
            </label>
            <button type="button" @click="cargar">Actualizar</button>
        </div>

        <p v-if="error" class="alr__error" role="alert">{{ error }}</p>
        <p v-else-if="alertas === null" role="status">Cargando alertas…</p>
        <p v-else-if="!alertas.length" role="status">No hay alertas pendientes de acuse.</p>
        <ul v-else class="alr__lista">
            <li v-for="a in alertas" :key="a.critical_alert_id" class="alr__item" role="alert">
                <p class="alr__valor"><strong>{{ a.measured_value }}</strong> — {{ direccionLegible(a.direction) }}</p>
                <p>Paciente <code>{{ idCorto(a.local_patient_id) }}</code> · prueba <code>{{ idCorto(a.lab_test_id) }}</code> · nivel {{ a.escalation_level }}</p>
                <p :class="{ 'alr__vencida': vencimientoLegible(a.due_at, ahora).startsWith('Vencida') }">{{ vencimientoLegible(a.due_at, ahora) }}</p>
                <button v-if="abierta !== a.critical_alert_id" type="button" @click="abrir(a)">Acusar recibo</button>
                <form v-else class="alr__acuse" novalidate @submit.prevent="acusar(a)">
                    <label><input v-model="lectura.confirmado" type="checkbox"> Confirmo paciente <code>{{ idCorto(a.local_patient_id) }}</code> y prueba <code>{{ idCorto(a.lab_test_id) }}</code></label>
                    <label>Valor leído <input v-model="lectura.valor" inputmode="decimal" autocomplete="off"></label>
                    <p v-if="errorAcuse" class="alr__error" role="alert">{{ errorAcuse }}</p>
                    <button type="submit" :disabled="enviando">{{ enviando ? 'Acusando…' : 'Confirmar acuse' }}</button>
                    <button type="button" class="alr__secundario" @click="abierta = null">Cancelar</button>
                </form>
            </li>
        </ul>
        <p class="alr__aviso" role="status" aria-live="polite">{{ aviso }}</p>
    </section>
</template>

<style scoped>
.alr { display: grid; gap: 1rem; max-width: 46rem; }
.alr h1 { font-size: 1.5rem; font-weight: 700; }
.alr__barra { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: end; }
.alr__barra label, .alr__acuse label { display: grid; gap: 0.25rem; }
.alr select, .alr input:not([type='checkbox']) { padding: 0.5rem; border: 1px solid #94a3b8; border-radius: 0.375rem; }
.alr button { padding: 0.5rem 1rem; border-radius: 0.375rem; background: #1d4ed8; color: #fff; }
.alr button:disabled { opacity: 0.6; }
.alr .alr__secundario { background: #e2e8f0; color: #0f172a; }
.alr__lista { display: grid; gap: 0.75rem; padding: 0; list-style: none; }
.alr__item { display: grid; gap: 0.25rem; padding: 0.75rem 1rem; border-left: 0.5rem solid #b91c1c; border-radius: 0.375rem; background: #fef2f2; }
.alr__item button { justify-self: start; }
.alr__valor { font-size: 1.125rem; color: #7f1d1d; }
.alr__vencida { font-weight: 700; color: #b91c1c; }
.alr__acuse { display: grid; gap: 0.5rem; }
.alr__acuse label:first-child { display: flex; gap: 0.5rem; align-items: center; }
.alr__error { color: #b91c1c; }
</style>
