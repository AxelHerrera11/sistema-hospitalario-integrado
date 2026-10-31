<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import api from '@/plugins/axios';
import { can } from '@/router/permissions';
import { useAuthStore } from '@/stores/auth';
import {
    debeOcultar, estadoDesdeApi, etiquetaEstado, etiquetaPaciente, etiquetaSexo, etiquetaTipo, formatearFecha,
    formatearNacimiento, siguienteReintento, validarCodigo,
} from '@/modules/expediente/expediente';

const auth = useAuthStore();
const puedeAbrir = computed(() => can(auth.user, 'expediente.abrir'));
const codigo = ref('');
const errorCampo = ref('');
const cargando = ref(false);
const estado = ref(null);
const aviso = ref('');
const expediente = ref(null);
const mostrado = ref(null);
const ultima = ref(null);
const intentos = ref(0);
const titulo = ref(null);

// H-01 del prototipo: si el código escrito cambia, el expediente anterior deja de mostrarse.
watch(codigo, (valor) => {
    if (debeOcultar(mostrado.value, valor)) {
        expediente.value = null;
        mostrado.value = null;
        estado.value = null;
        aviso.value = '';
    }
});

// Acción del usuario: limpia el aviso anterior y reinicia el contador de reintentos.
function accion(operacion) {
    aviso.value = '';
    intentos.value = 0;
    ejecutar(operacion);
}

function reintentar() {
    intentos.value += 1;
    ejecutar(ultima.value);
}

async function ejecutar(operacion) {
    const entrada = validarCodigo(codigo.value);
    errorCampo.value = entrada.mensaje;
    if (! entrada.valido) {
        return;
    }
    ultima.value = operacion;
    cargando.value = true;
    const ruta = `/patients/${encodeURIComponent(entrada.codigo)}/medical-record`;
    try {
        const { status, data } = operacion === 'abrir' ? await api.post(ruta) : await api.get(ruta);
        if (operacion === 'abrir') {
            // La apertura devuelve solo el expediente; la línea de tiempo (si hay vínculo) se carga con la consulta.
            const abierto = estadoDesdeApi(operacion, status, data);
            aviso.value = `${abierto.titulo}: ${abierto.mensaje}`;
            return ejecutar('consultar');
        }
        estado.value = estadoDesdeApi(operacion, status, data);
        expediente.value = data;
        mostrado.value = entrada.codigo;
        await nextTick();
        titulo.value?.focus();
    } catch (e) {
        expediente.value = null;
        mostrado.value = null;
        estado.value = estadoDesdeApi(operacion, e.response?.status ?? 0, e.response?.data ?? {});
    } finally {
        cargando.value = false;
    }
}

const reintento = computed(() => (estado.value?.puedeReintentar ? siguienteReintento(intentos.value) : null));
</script>

<template>
    <section class="exp" aria-labelledby="exp-titulo" :aria-busy="cargando">
        <h1 id="exp-titulo">Expediente médico</h1>
        <p class="exp__nota">Consulta del expediente longitudinal. Solo el personal con vínculo asistencial vigente ve los episodios; cada acceso queda en la bitácora.</p>

        <form class="exp__card" novalidate @submit.prevent="accion('consultar')">
            <label for="exp-codigo">Código del paciente</label>
            <input id="exp-codigo" v-model="codigo" autocomplete="off" maxlength="40" :aria-invalid="String(Boolean(errorCampo))" aria-describedby="exp-ayuda">
            <small id="exp-ayuda">Por ejemplo, P-0001.</small>
            <p v-if="errorCampo" class="exp__error" role="alert">{{ errorCampo }}</p>
            <div class="exp__acciones">
                <button type="submit" :disabled="cargando">Consultar</button>
                <button v-if="puedeAbrir" type="button" class="exp__secundario" :disabled="cargando" @click="accion('abrir')">Abrir expediente</button>
            </div>
        </form>

        <p class="exp__ok" role="status" aria-live="polite">{{ aviso }}</p>
        <div v-if="estado && estado.tipo !== 'exito'" class="exp__estado" :class="`exp__estado--${estado.tipo}`" :role="estado.tipo === 'vacio' ? 'status' : 'alert'">
            <strong>{{ estado.titulo }}</strong>
            <p>{{ estado.mensaje }}</p>
            <p v-if="estado.traza" class="exp__traza">Código de seguimiento: {{ estado.traza }}</p>
            <template v-if="reintento">
                <button v-if="reintento.permitido" type="button" :disabled="cargando" @click="reintentar">Reintentar</button>
                <p>{{ reintento.aviso }}</p>
            </template>
        </div>

        <article v-if="expediente" class="exp__card">
            <h2 ref="titulo" tabindex="-1">{{ expediente.patient.display_name }} · {{ expediente.patient.code }}</h2>
            <dl class="exp__datos">
                <dt>Expediente</dt><dd>{{ expediente.record.record_number }} ({{ etiquetaEstado(expediente.record.status) }})</dd>
                <dt>Abierto</dt><dd>{{ formatearFecha(expediente.record.opened_at) }}</dd>
                <dt>Nacimiento</dt><dd>{{ formatearNacimiento(expediente.patient.birth_date) }}</dd>
                <dt>Sexo</dt><dd>{{ etiquetaSexo(expediente.patient.sex) }}</dd>
                <dt>Paciente</dt><dd>{{ etiquetaPaciente(expediente.patient.status) }}</dd>
                <dt>Episodios</dt><dd>{{ expediente.summary.episodes }} ({{ expediente.summary.open_episodes }} abiertos)</dd>
            </dl>
            <h3>Línea de tiempo</h3>
            <ol class="exp__linea">
                <li v-for="e in expediente.episodes" :key="e.care_episode_id">
                    <strong>{{ etiquetaTipo(e.type) }}</strong> — {{ etiquetaEstado(e.status) }}
                    <span>{{ formatearFecha(e.started_at) }}<template v-if="e.ended_at"> a {{ formatearFecha(e.ended_at) }}</template></span>
                    <span v-if="e.reason">Motivo: {{ e.reason }}</span>
                </li>
            </ol>
        </article>
    </section>
</template>

<style scoped>
.exp { display: grid; gap: 1rem; max-width: 48rem; }
.exp h1 { font-size: 1.5rem; font-weight: 700; }
.exp h2, .exp h3 { font-size: 1.125rem; font-weight: 600; }
.exp__nota, .exp small { color: #475569; }
.exp__card { display: grid; gap: 0.5rem; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; background: #fff; }
.exp__card input { padding: 0.5rem; border: 1px solid #94a3b8; border-radius: 0.375rem; }
.exp__card [aria-invalid='true'] { border-color: #b91c1c; }
.exp__acciones { display: flex; gap: 0.5rem; }
.exp button { justify-self: start; padding: 0.5rem 1rem; border-radius: 0.375rem; background: #1d4ed8; color: #fff; }
.exp button:disabled { opacity: 0.6; }
.exp .exp__secundario { background: #fff; color: #1d4ed8; border: 1px solid #1d4ed8; }
.exp__estado { display: grid; gap: 0.25rem; padding: 0.75rem 1rem; border-left: 0.5rem solid; border-radius: 0.375rem; }
.exp__estado--denegado { border-color: #b45309; background: #fffbeb; }
.exp__estado--error { border-color: #b91c1c; background: #fef2f2; }
.exp__estado--vacio { border-color: #475569; background: #f1f5f9; }
.exp__traza { font-family: ui-monospace, monospace; font-size: 0.875rem; }
.exp__ok { color: #15803d; }
.exp__datos { display: grid; grid-template-columns: max-content 1fr; gap: 0.25rem 1rem; }
.exp__linea { display: grid; gap: 0.5rem; padding-left: 1.25rem; list-style: decimal; }
.exp__linea li { display: grid; }
.exp__error { color: #b91c1c; }
</style>
