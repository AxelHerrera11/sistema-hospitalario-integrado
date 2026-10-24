<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import api from '@/plugins/axios';
import { can } from '@/router/permissions';
import { useAuthStore } from '@/stores/auth';
import { TIPOS_RESULTADO, campoInvalido, codigoDeError, cuerpoRegistro, mensajeDeCampo, mensajeDeError, rangoLegible } from '@/modules/catalogo-lab/catalogo';

const auth = useAuthStore();
const puedeAdministrar = computed(() => can(auth.user, 'laboratorio.catalogo.administrar'));
const hoy = new Date().toLocaleDateString('en-CA'); // AAAA-MM-DD en la zona del navegador
const busqueda = ref('');
const fecha = ref(hoy);
const pruebas = ref(null); // null = cargando
const error = ref('');
const cargando = ref(false);
const invalido = ref(null);
const errorCampo = ref('');
const aviso = ref('');
const vacio = () => ({ code: '', name: '', specimen_type: '', min_volume_ml: '', result_type: 'numeric', unit: '',
    critical_low: '', reference_low: '', reference_high: '', critical_high: '', valid_from: hoy });
const form = reactive(vacio());

const fallo = (e) => mensajeDeError(codigoDeError(e.response?.data), e.response?.status);
watch(form, () => {
    invalido.value = null;
    errorCampo.value = '';
});

async function consultar() {
    pruebas.value = null;
    error.value = '';
    try {
        const { data } = await api.get('/lab-tests', { params: { date: fecha.value, search: busqueda.value.trim() } });
        pruebas.value = data.data.lab_tests;
    } catch (e) {
        pruebas.value = [];
        error.value = fallo(e);
    }
}

async function registrar() {
    aviso.value = '';
    const campo = campoInvalido(form, hoy);
    if (campo) {
        errorCampo.value = mensajeDeCampo(campo, form);
        invalido.value = campo;
        return;
    }
    cargando.value = true;
    try {
        const { data } = await api.post('/lab-tests', cuerpoRegistro(form));
        Object.assign(form, vacio());
        await consultar();
        aviso.value = `Prueba ${data.data.code} registrada (versión ${data.data.version_no}).`;
    } catch (e) {
        errorCampo.value = fallo(e);
        invalido.value = e.response?.data?.error?.field ?? null;
    } finally {
        cargando.value = false;
    }
}

onMounted(consultar);
</script>

<template>
    <section class="lab" aria-labelledby="lab-titulo">
        <h1 id="lab-titulo">Catálogo de pruebas de laboratorio</h1>

        <form class="lab__card lab__filtros" role="search" @submit.prevent="consultar">
            <label>Código o nombre <input id="lab-busqueda" v-model="busqueda" autocomplete="off"></label>
            <label>Vigente al <input id="lab-fecha" v-model="fecha" type="date" required></label>
            <button type="submit">Consultar</button>
        </form>

        <p v-if="error" class="lab__error" role="alert">{{ error }}</p>
        <p v-else-if="pruebas === null" role="status">Cargando catálogo…</p>
        <p v-else-if="!pruebas.length">No hay pruebas vigentes para esa búsqueda y fecha.</p>
        <div v-else class="lab__tabla">
            <table>
                <caption class="lab__oculto">Pruebas vigentes al {{ fecha }}</caption>
                <thead>
                    <tr><th scope="col">Código</th><th scope="col">Nombre</th><th scope="col">Muestra</th><th scope="col">Resultado</th><th scope="col">Referencia</th><th scope="col">Versión</th></tr>
                </thead>
                <tbody>
                    <tr v-for="p in pruebas" :key="`${p.code}-${p.version_no}`">
                        <th scope="row">{{ p.code }}</th><td>{{ p.name }}</td><td>{{ p.specimen_type }}</td>
                        <td>{{ TIPOS_RESULTADO.find((t) => t.valor === p.result_type)?.etiqueta ?? p.result_type }}</td>
                        <td>{{ rangoLegible(p) }}</td><td>v{{ p.version_no }} desde {{ p.valid_from }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <form v-if="puedeAdministrar" class="lab__card" novalidate @submit.prevent="registrar">
            <h2>Registrar prueba</h2>
            <div class="lab__grid">
                <label>Código <input v-model="form.code" maxlength="32" :aria-invalid="String(invalido === 'code')"></label>
                <label>Nombre <input v-model="form.name" maxlength="160" :aria-invalid="String(invalido === 'name')"></label>
                <label>Tipo de muestra <input v-model="form.specimen_type" :aria-invalid="String(invalido === 'specimen_type')"></label>
                <label>Volumen mínimo (mL) <input v-model="form.min_volume_ml" type="number" step="0.1" min="0" :aria-invalid="String(invalido === 'min_volume_ml')"></label>
                <label>Tipo de resultado
                    <select v-model="form.result_type"><option v-for="t in TIPOS_RESULTADO" :key="t.valor" :value="t.valor">{{ t.etiqueta }}</option></select>
                </label>
                <label>Vigente desde <input v-model="form.valid_from" type="date" :min="hoy" :aria-invalid="String(invalido === 'valid_from')"></label>
                <template v-if="form.result_type === 'numeric'">
                    <label>Unidad <input v-model="form.unit" :aria-invalid="String(invalido === 'unit')"></label>
                    <label>Crítico bajo <input v-model="form.critical_low" type="number" step="any" :aria-invalid="String(invalido === 'reference_low')"></label>
                    <label>Referencia baja <input v-model="form.reference_low" type="number" step="any" :aria-invalid="String(invalido === 'reference_low')"></label>
                    <label>Referencia alta <input v-model="form.reference_high" type="number" step="any" :aria-invalid="String(invalido === 'reference_low')"></label>
                    <label>Crítico alto <input v-model="form.critical_high" type="number" step="any" :aria-invalid="String(invalido === 'reference_low')"></label>
                </template>
            </div>
            <p v-if="errorCampo" class="lab__error" role="alert">{{ errorCampo }}</p>
            <button type="submit" :disabled="cargando">{{ cargando ? 'Guardando…' : 'Registrar versión' }}</button>
        </form>
        <p class="lab__aviso" role="status" aria-live="polite">{{ aviso }}</p>
    </section>
</template>

<style scoped>
.lab { display: grid; gap: 1rem; max-width: 60rem; }
.lab h1 { font-size: 1.5rem; font-weight: 700; }
.lab h2 { font-size: 1.125rem; font-weight: 600; }
.lab__card { display: grid; gap: 0.5rem; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; background: #fff; }
.lab__filtros { grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr)); align-items: end; }
.lab__grid { display: grid; gap: 0.75rem; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); }
.lab__grid label, .lab__filtros label { display: grid; gap: 0.25rem; }
.lab__card input, .lab__card select { padding: 0.5rem; border: 1px solid #94a3b8; border-radius: 0.375rem; }
.lab__card [aria-invalid='true'] { border-color: #b91c1c; }
.lab__card button { justify-self: start; padding: 0.5rem 1rem; border-radius: 0.375rem; background: #1d4ed8; color: #fff; }
.lab__card button:disabled { opacity: 0.6; }
.lab__tabla { overflow-x: auto; }
.lab__tabla table { width: 100%; border-collapse: collapse; background: #fff; }
.lab__tabla th, .lab__tabla td { padding: 0.5rem; border-bottom: 1px solid #e2e8f0; text-align: left; white-space: nowrap; }
.lab__error { color: #b91c1c; }
.lab__oculto { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
</style>
