<script setup>
import { computed, nextTick, reactive, ref } from 'vue';
import api from '@/plugins/axios';
import { can } from '@/router/permissions';
import { useAuthStore } from '@/stores/auth';
import { banner, campoInvalido, codigoDeError, cuerpoRegistro, etiqueta, mensajeDeError, SEVERIDADES, TIPOS } from '@/modules/alergias/alergias';

const auth = useAuthStore();
const puedeRegistrar = computed(() => can(auth.user, 'alergias.registrar'));
const codigo = ref('');
const paciente = ref(null);
const alerta = ref(null);
const cargando = ref(false);
const error = ref('');
const aviso = ref('');
const invalido = ref(null);
const titulo = ref(null);
const vacio = () => ({ allergen_code: '', allergen_name: '', reaction: '', allergy_type: 'allergy', severity: '' });
const form = reactive(vacio());

const fallo = (e) => mensajeDeError(codigoDeError(e.response?.data), e.response?.status);

async function cargarAlerta() {
    try {
        const { data } = await api.get(`/patients/${paciente.value.patient_id}/allergy-alert`);
        alerta.value = data.data;
    } catch (e) {
        // Falla segura: sin lectura nunca se muestra «sin alergias», solo el aviso del dominio.
        alerta.value = { level: 'unavailable', message: e.response?.data?.error?.message ?? fallo(e), allergies: [] };
    }
}

async function buscar() {
    cargando.value = true;
    error.value = '';
    aviso.value = '';
    paciente.value = null;
    alerta.value = null;
    try {
        const { data } = await api.get(`/patients/${encodeURIComponent(codigo.value.trim())}`);
        paciente.value = data.patient;
        await cargarAlerta();
        await nextTick();
        titulo.value?.focus();
    } catch (e) {
        error.value = e.response?.status === 404 ? 'No existe un paciente con ese código.' : fallo(e);
    } finally {
        cargando.value = false;
    }
}

async function registrar() {
    aviso.value = '';
    invalido.value = campoInvalido(form);
    if (invalido.value) {
        return;
    }
    cargando.value = true;
    try {
        await api.post(`/patients/${paciente.value.patient_id}/allergies`, cuerpoRegistro(form));
        Object.assign(form, vacio());
        aviso.value = 'Alergia registrada.';
        await cargarAlerta();
    } catch (e) {
        invalido.value = e.response?.data?.error?.field ?? null;
        aviso.value = fallo(e);
    } finally {
        cargando.value = false;
    }
}
</script>

<template>
    <section class="alg" aria-labelledby="alg-titulo" :aria-busy="cargando">
        <h1 id="alg-titulo">Alergias clínicas</h1>

        <form class="alg__card" @submit.prevent="buscar">
            <label for="alg-codigo">Código del paciente</label>
            <input id="alg-codigo" v-model="codigo" autocomplete="off" required>
            <button type="submit" :disabled="cargando || !codigo.trim()">Buscar</button>
        </form>
        <p v-if="error" class="alg__error" role="alert">{{ error }}</p>

        <template v-if="paciente">
            <h2 ref="titulo" tabindex="-1">{{ paciente.given_name }} {{ paciente.family_name }} · {{ paciente.code }}</h2>

            <div v-if="alerta" class="alg__banner" :class="`alg__banner--${banner(alerta.level).clase}`" :role="banner(alerta.level).rol">
                <strong>{{ alerta.message }}</strong>
                <ul v-if="alerta.allergies.length">
                    <li v-for="a in alerta.allergies" :key="a.allergy_id">
                        {{ a.allergen_name ?? a.allergen_code }} — {{ etiqueta(SEVERIDADES, a.severity) }}, {{ etiqueta(TIPOS, a.allergy_type) }}<span v-if="a.reaction">: {{ a.reaction }}</span>
                    </li>
                </ul>
            </div>

            <form v-if="puedeRegistrar" class="alg__card" novalidate @submit.prevent="registrar">
                <h3>Registrar alergia</h3>
                <label for="alg-nombre">Alérgeno (nombre)</label>
                <input id="alg-nombre" v-model="form.allergen_name" maxlength="200" :aria-invalid="String(invalido === 'allergen_name')">
                <label for="alg-cod">Código del alérgeno (opcional si hay nombre)</label>
                <input id="alg-cod" v-model="form.allergen_code" maxlength="50">
                <label for="alg-reaccion">Reacción</label>
                <input id="alg-reaccion" v-model="form.reaction" maxlength="500">
                <label for="alg-tipo">Tipo</label>
                <select id="alg-tipo" v-model="form.allergy_type" :aria-invalid="String(invalido === 'allergy_type')">
                    <option v-for="t in TIPOS" :key="t.valor" :value="t.valor">{{ t.etiqueta }}</option>
                </select>
                <label for="alg-severidad">Severidad</label>
                <select id="alg-severidad" v-model="form.severity" :aria-invalid="String(invalido === 'severity')">
                    <option value="" disabled>Seleccione…</option>
                    <option v-for="s in SEVERIDADES" :key="s.valor" :value="s.valor">{{ s.etiqueta }}</option>
                </select>
                <p v-if="invalido" class="alg__error" role="alert">{{ mensajeDeError({ allergen_name: 'ALLERGEN_REQUIRED', allergen_code: 'ALLERGEN_REQUIRED', allergy_type: 'ALLERGY_TYPE_INVALID', severity: 'SEVERITY_INVALID' }[invalido]) }}</p>
                <button type="submit" :disabled="cargando">{{ cargando ? 'Guardando…' : 'Registrar' }}</button>
            </form>
            <p class="alg__aviso" role="status" aria-live="polite">{{ aviso }}</p>
        </template>
    </section>
</template>

<style scoped>
.alg { display: grid; gap: 1rem; max-width: 42rem; }
.alg__card { display: grid; gap: 0.5rem; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; background: #fff; }
.alg__card input, .alg__card select { padding: 0.5rem; border: 1px solid #94a3b8; border-radius: 0.375rem; }
.alg__card [aria-invalid='true'] { border-color: #b91c1c; }
.alg__banner { padding: 0.75rem 1rem; border-left: 0.5rem solid; border-radius: 0.375rem; }
.alg__banner--none { border-color: #15803d; background: #f0fdf4; }
.alg__banner--warning { border-color: #b45309; background: #fffbeb; }
.alg__banner--critical { border-color: #b91c1c; background: #fef2f2; color: #7f1d1d; }
.alg__banner--unavailable { border-color: #475569; background: #f1f5f9; }
.alg__error { color: #b91c1c; }
</style>
