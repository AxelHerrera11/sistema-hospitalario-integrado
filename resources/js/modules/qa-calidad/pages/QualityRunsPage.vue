<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import api from '@/plugins/axios';
import { useAuthStore } from '@/stores/auth';
import {
    allowedActions, cuerpoPlan, cuerpoResultado, errorQa, findingText, mainReason, permisosQa, planVacio, severityLabel,
    shortSha, STATUS, TIPOS_SUITE, validatePlan, validateResult, VERDICTS,
} from '@/modules/qa-calidad/qaCalidad';

const auth = useAuthStore();
const permisos = computed(() => permisosQa(auth.user));
const ejecuciones = ref([]);
const actual = ref(null);
const plan = reactive(planVacio());
const erroresPlan = ref({});
const resultado = reactive({ suite: '', total: '', failed: '0', skipped: '0', duration_ms: '', path: '', sha256: '', bytes: '' });
const erroresResultado = ref({});
const motivo = ref('');
const cargando = ref(false);
const error = ref(null);
const aviso = ref('');

const acciones = computed(() => (actual.value ? allowedActions(actual.value, permisos.value) : []));
const pendientes = computed(() => actual.value?.suites.filter((s) => s.result === null) ?? []);

async function llamar(peticion, exito) {
    cargando.value = true;
    error.value = null;
    aviso.value = '';
    try {
        const { data } = await peticion();
        aviso.value = exito;
        return data;
    } catch (e) {
        error.value = errorQa(e.response?.status ?? 0, e.response?.data);
        return null;
    } finally {
        cargando.value = false;
    }
}

async function listar() {
    const data = await llamar(() => api.get('/qa/runs', { params: { limit: 20 } }), '');
    ejecuciones.value = data?.data ?? [];
}

async function ver(id) {
    const data = await llamar(() => api.get(`/qa/runs/${encodeURIComponent(id)}`), '');
    actual.value = data?.data ?? null;
    resultado.suite = pendientes.value[0]?.name ?? '';
}

async function planificar() {
    const cuerpo = cuerpoPlan(plan);
    erroresPlan.value = validatePlan(cuerpo);
    if (Object.keys(erroresPlan.value).length) return;
    const data = await llamar(() => api.post('/qa/runs', cuerpo), 'Ejecución planificada.');
    if (data) {
        Object.assign(plan, planVacio());
        await listar();
        await ver(data.data.id);
    }
}

async function transicion(nombre) {
    const body = nombre === 'abort' ? { transition: 'abort', reason: motivo.value.trim() } : { transition: nombre };
    if (await llamar(() => api.post(`/qa/runs/${actual.value.id}/transitions`, body), `Transición «${nombre}» aplicada.`)) {
        motivo.value = '';
        await ver(actual.value.id);
        await listar();
    }
}

async function registrar() {
    const cuerpo = cuerpoResultado(resultado.suite, resultado);
    erroresResultado.value = validateResult(cuerpo);
    if (Object.keys(erroresResultado.value).length) return;
    if (await llamar(() => api.post(`/qa/runs/${actual.value.id}/results`, cuerpo), `Resultado de ${cuerpo.suite} registrado.`)) {
        Object.assign(resultado, { total: '', duration_ms: '', path: '', sha256: '', bytes: '' });
        await ver(actual.value.id);
    }
}

async function evaluar() {
    if (await llamar(() => api.post(`/qa/runs/${actual.value.id}/gate-decisions`), 'Quality gate evaluado.')) {
        await ver(actual.value.id);
        await listar();
    }
}

onMounted(listar);
</script>

<template>
    <section class="qa" aria-labelledby="qa-titulo" :aria-busy="cargando">
        <h1 id="qa-titulo">Calidad de versiones</h1>
        <p class="qa__nota">Ejecuciones automatizadas de pruebas por versión y decisión del quality gate (sin datos clínicos).</p>
        <p class="qa__ok" role="status" aria-live="polite">{{ aviso }}</p>
        <div v-if="error" class="qa__error" role="alert">
            <strong>{{ error.title }}</strong> {{ error.message }}
            <button v-if="error.retry" type="button" @click="listar">Reintentar</button>
        </div>

        <form v-if="permisos.register" class="qa__card" novalidate @submit.prevent="planificar">
            <h2>Planificar ejecución</h2>
            <label>Versión (SemVer)<input v-model="plan.release_version" placeholder="1.4.0-rc.1" :aria-invalid="String(Boolean(erroresPlan.release_version))"></label>
            <small v-if="erroresPlan.release_version" class="qa__campo">{{ erroresPlan.release_version }}</small>
            <label>Commit (SHA de 40)<input v-model="plan.commit_sha" maxlength="40" :aria-invalid="String(Boolean(erroresPlan.commit_sha))"></label>
            <small v-if="erroresPlan.commit_sha" class="qa__campo">{{ erroresPlan.commit_sha }}</small>
            <label>Ambiente<select v-model="plan.environment"><option value="local">local</option><option value="ci">ci</option><option value="staging">staging</option></select></label>
            <fieldset>
                <legend>Suites</legend>
                <div v-for="(s, i) in plan.suites" :key="i" class="qa__fila">
                    <input v-model="s.name" :aria-label="`Nombre de la suite ${i + 1}`">
                    <select v-model="s.kind" :aria-label="`Tipo de la suite ${i + 1}`"><option v-for="t in TIPOS_SUITE" :key="t" :value="t">{{ t }}</option></select>
                    <label><input v-model="s.required" type="checkbox"> obligatoria</label>
                    <button type="button" class="qa__sec" :aria-label="`Quitar la suite ${i + 1}`" @click="plan.suites.splice(i, 1)">×</button>
                </div>
                <button type="button" class="qa__sec" @click="plan.suites.push({ name: '', kind: 'unit', required: true })">Agregar suite</button>
            </fieldset>
            <small v-if="erroresPlan.suites" class="qa__campo">{{ erroresPlan.suites }}</small>
            <button type="submit" :disabled="cargando">Planificar</button>
        </form>

        <table v-if="ejecuciones.length" class="qa__tabla">
            <caption>Últimas ejecuciones</caption>
            <thead><tr><th scope="col">Versión</th><th scope="col">Commit</th><th scope="col">Ambiente</th><th scope="col">Estado</th><th scope="col">Gate</th><th scope="col"><span class="qa__sr">Ver</span></th></tr></thead>
            <tbody>
                <tr v-for="r in ejecuciones" :key="r.id">
                    <td>{{ r.release_version }}</td><td class="qa__mono">{{ shortSha(r.commit_sha) }}</td><td>{{ r.environment }}</td>
                    <td>{{ STATUS[r.status] ?? r.status }}</td><td>{{ r.gate ? VERDICTS[r.gate.verdict]?.label : '—' }}</td>
                    <td><button type="button" class="qa__sec" @click="ver(r.id)">Detalle</button></td>
                </tr>
            </tbody>
        </table>
        <p v-else-if="!cargando && !error">Todavía no hay ejecuciones en este hospital.</p>

        <article v-if="actual" class="qa__card" aria-label="Detalle de la ejecución">
            <h2>{{ actual.release_version }} · {{ STATUS[actual.status] }}</h2>
            <div v-if="actual.gate" class="qa__gate" :class="`qa__gate--${VERDICTS[actual.gate.verdict]?.tone}`" role="status">
                <strong>{{ VERDICTS[actual.gate.verdict]?.label }}</strong> {{ VERDICTS[actual.gate.verdict]?.help }}
                <p v-if="mainReason(actual.gate)">Motivo principal: {{ mainReason(actual.gate) }}</p>
                <ul><li v-for="(f, i) in actual.gate.findings" :key="i">{{ severityLabel(f.severity) }}: {{ findingText(f) }}</li></ul>
            </div>
            <ul>
                <li v-for="s in actual.suites" :key="s.name">
                    {{ s.name }} ({{ s.kind }}{{ s.required ? ', obligatoria' : '' }}):
                    <span v-if="s.result">{{ s.result.passed }}/{{ s.result.total }} pasaron, {{ s.result.failed }} fallidas, {{ s.result.skipped }} omitidas</span>
                    <span v-else>sin resultado</span>
                </li>
            </ul>
            <div class="qa__acciones">
                <button v-if="acciones.includes('start')" type="button" :disabled="cargando" @click="transicion('start')">Iniciar</button>
                <button v-if="acciones.includes('complete')" type="button" :disabled="cargando" @click="transicion('complete')">Completar</button>
                <button v-if="acciones.includes('evaluate')" type="button" :disabled="cargando" @click="evaluar">Evaluar quality gate</button>
                <template v-if="acciones.includes('abort')">
                    <input v-model="motivo" aria-label="Motivo para abortar" placeholder="Motivo para abortar">
                    <button type="button" class="qa__sec" :disabled="cargando || !motivo.trim()" @click="transicion('abort')">Abortar</button>
                </template>
            </div>
            <form v-if="acciones.includes('record')" class="qa__resultado" novalidate @submit.prevent="registrar">
                <h3>Registrar resultado</h3>
                <label>Suite<select v-model="resultado.suite"><option v-for="s in pendientes" :key="s.name" :value="s.name">{{ s.name }}</option></select></label>
                <label v-for="c in ['total', 'failed', 'skipped', 'duration_ms', 'bytes']" :key="c">{{ c }}<input v-model="resultado[c]" inputmode="numeric" :aria-invalid="String(Boolean(erroresResultado[c]))"></label>
                <label>Evidencia (ruta)<input v-model="resultado.path" placeholder="phpunit-unit.xml"></label>
                <label>SHA-256<input v-model="resultado.sha256" maxlength="64"></label>
                <small v-for="(m, c) in erroresResultado" :key="c" class="qa__campo">{{ c }}: {{ m }}</small>
                <button type="submit" :disabled="cargando || !resultado.suite">Registrar</button>
            </form>
        </article>
    </section>
</template>

<style scoped>
.qa { display: grid; gap: 1rem; }
.qa h1 { font-size: 1.5rem; font-weight: 700; }
.qa h2, .qa h3 { font-size: 1.125rem; font-weight: 600; }
.qa__nota { color: #475569; }
.qa__card, .qa__resultado { display: grid; gap: 0.5rem; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; background: #fff; }
.qa label { display: grid; gap: 0.25rem; font-size: 0.875rem; }
.qa input, .qa select { padding: 0.375rem; border: 1px solid #94a3b8; border-radius: 0.375rem; }
.qa [aria-invalid='true'] { border-color: #b91c1c; }
.qa__fila, .qa__acciones { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
.qa__fila label { display: flex; align-items: center; }
.qa button { justify-self: start; padding: 0.375rem 0.875rem; border-radius: 0.375rem; background: #1d4ed8; color: #fff; }
.qa button:disabled { opacity: 0.6; }
.qa .qa__sec { background: #fff; color: #1d4ed8; border: 1px solid #1d4ed8; }
.qa__tabla { width: 100%; border-collapse: collapse; background: #fff; }
.qa__tabla caption { text-align: left; font-weight: 600; }
.qa__tabla th, .qa__tabla td { padding: 0.5rem; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 0.875rem; }
.qa__mono { font-family: ui-monospace, monospace; }
.qa__gate { padding: 0.75rem; border-left: 0.5rem solid; border-radius: 0.375rem; }
.qa__gate--ok { border-color: #15803d; background: #f0fdf4; }
.qa__gate--bad { border-color: #b91c1c; background: #fef2f2; }
.qa__gate--warn { border-color: #b45309; background: #fffbeb; }
.qa__error, .qa__campo { color: #b91c1c; }
.qa__ok { color: #15803d; }
.qa__sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
</style>
