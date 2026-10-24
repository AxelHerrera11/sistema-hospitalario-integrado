<script setup>
import { computed, nextTick, reactive, ref } from 'vue';
import api from '@/plugins/axios';
import { campoInvalido, codigoDeError, cuerpoAdmision, debeRecargarCamas, mensajeDeCampo, mensajeDeError, tiposDeCama } from '@/modules/admision/admision';

const codigo = ref('');
const paciente = ref(null);
const camas = ref(null); // null = cargando; nunca «no hay camas» antes de la respuesta
const tipos = ref([]);
const tipo = ref('');
const form = reactive({ bed_id: '', reason: '' });
const cargando = ref(false);
const error = ref('');
const invalido = ref(null);
const errorCampo = ref('');
const ingreso = ref(null);
const titulo = ref(null);
const camaElegida = computed(() => camas.value?.find((cama) => cama.bed_id === form.bed_id));

const fallo = (e) => mensajeDeError(codigoDeError(e.response?.data), e.response?.status);

async function cargarCamas() {
    camas.value = null;
    try {
        const { data } = await api.get('/beds/available', { params: tipo.value ? { type: tipo.value } : {} });
        camas.value = data.data.beds;
        if (! tipo.value) {
            tipos.value = tiposDeCama(camas.value);
        }
        if (! camaElegida.value) {
            form.bed_id = '';
        }
    } catch (e) {
        camas.value = [];
        error.value = fallo(e);
    }
}

async function buscar() {
    cargando.value = true;
    error.value = '';
    ingreso.value = null;
    paciente.value = null;
    try {
        const { data } = await api.get(`/patients/${encodeURIComponent(codigo.value.trim())}`);
        paciente.value = data.patient;
        await cargarCamas();
        await nextTick();
        titulo.value?.focus();
    } catch (e) {
        error.value = e.response?.status === 404 ? 'No existe un paciente con ese código.' : fallo(e);
    } finally {
        cargando.value = false;
    }
}

async function admitir() {
    const campos = { patient_id: paciente.value?.patient_id, ...form };
    invalido.value = campoInvalido(campos);
    errorCampo.value = invalido.value ? mensajeDeCampo(invalido.value) : '';
    if (invalido.value) {
        return;
    }
    cargando.value = true;
    try {
        const { data } = await api.post('/admissions', cuerpoAdmision(campos));
        ingreso.value = { ...data.data, codigoCama: camaElegida.value?.code };
        Object.assign(form, { bed_id: '', reason: '' });
        await cargarCamas();
    } catch (e) {
        const codigoError = codigoDeError(e.response?.data);
        // El mensaje sale del código del servidor; si otra admisión tomó la cama, se recarga la lista.
        invalido.value = e.response?.data?.error?.field ?? null;
        errorCampo.value = fallo(e);
        if (debeRecargarCamas(codigoError)) {
            await cargarCamas();
        }
    } finally {
        cargando.value = false;
    }
}
</script>

<template>
    <section class="adm" aria-labelledby="adm-titulo" :aria-busy="cargando">
        <h1 id="adm-titulo">Admisión y asignación de cama</h1>

        <form class="adm__card" @submit.prevent="buscar">
            <label for="adm-codigo">Código del paciente</label>
            <input id="adm-codigo" v-model="codigo" autocomplete="off" required>
            <button type="submit" :disabled="cargando || !codigo.trim()">Buscar</button>
        </form>
        <p v-if="error" class="adm__error" role="alert">{{ error }}</p>

        <template v-if="paciente">
            <h2 ref="titulo" tabindex="-1">{{ paciente.given_name }} {{ paciente.family_name }} · {{ paciente.code }}</h2>

            <p v-if="ingreso" class="adm__exito" role="status">
                Ingreso registrado en la cama <strong>{{ ingreso.codigoCama }}</strong> el <time :datetime="ingreso.started_at">{{ new Date(ingreso.started_at).toLocaleString() }}</time>.
            </p>

            <form v-else class="adm__card" novalidate @submit.prevent="admitir">
                <label for="adm-tipo">Tipo de cama</label>
                <select id="adm-tipo" v-model="tipo" @change="cargarCamas">
                    <option value="">Todas</option>
                    <option v-for="t in tipos" :key="t" :value="t">{{ t }}</option>
                </select>

                <fieldset :aria-invalid="String(invalido === 'bed_id')">
                    <legend>Cama disponible</legend>
                    <p v-if="camas === null" role="status">Cargando camas…</p>
                    <p v-else-if="!camas.length">No hay camas disponibles{{ tipo ? ' de este tipo' : '' }}.</p>
                    <label v-for="cama in camas" :key="cama.bed_id" class="adm__cama">
                        <input v-model="form.bed_id" type="radio" name="cama" :value="cama.bed_id">
                        {{ cama.code }} <span class="adm__tipo">{{ cama.type }}</span>
                    </label>
                </fieldset>

                <label for="adm-motivo">Motivo de ingreso</label>
                <textarea id="adm-motivo" v-model="form.reason" rows="3" maxlength="500" :aria-invalid="String(invalido === 'reason')"></textarea>

                <p v-if="errorCampo" class="adm__error" role="alert">{{ errorCampo }}</p>
                <button type="submit" :disabled="cargando">{{ cargando ? 'Admitiendo…' : 'Admitir y asignar cama' }}</button>
            </form>
        </template>
    </section>
</template>

<style scoped>
.adm { display: grid; gap: 1rem; max-width: 42rem; }
.adm h1 { font-size: 1.5rem; font-weight: 700; }
.adm h2 { font-size: 1.125rem; font-weight: 600; }
.adm__card { display: grid; gap: 0.5rem; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; background: #fff; }
.adm__card input:not([type='radio']), .adm__card select, .adm__card textarea { padding: 0.5rem; border: 1px solid #94a3b8; border-radius: 0.375rem; }
.adm__card [aria-invalid='true'] { border-color: #b91c1c; }
.adm__card fieldset { display: grid; gap: 0.25rem; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.375rem; }
.adm__card button { justify-self: start; padding: 0.5rem 1rem; border-radius: 0.375rem; background: #1d4ed8; color: #fff; }
.adm__card button:disabled { opacity: 0.6; }
.adm__cama { display: flex; gap: 0.5rem; align-items: center; }
.adm__tipo { color: #475569; font-size: 0.875rem; }
.adm__exito { padding: 0.75rem 1rem; border-left: 0.5rem solid #15803d; border-radius: 0.375rem; background: #f0fdf4; }
.adm__error { color: #b91c1c; }
</style>
