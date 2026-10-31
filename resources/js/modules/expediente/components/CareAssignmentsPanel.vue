<script setup>
import { computed, reactive, ref, watch } from 'vue';
import api from '@/plugins/axios';
import {
    erroresDeAsignacion, etiquetaTipo, formatearFecha, mensajeDeAsignacion, opcionesDeMedico,
} from '@/modules/expediente/expediente';

// Médicos tratantes (expediente.asignar): crea y termina el vínculo asistencial que habilita la consulta clínica.
const props = defineProps({ codigo: { type: String, required: true } });

const datos = ref(null);
const cargando = ref(false);
const aviso = ref('');
const error = ref('');
const errores = ref({});
const form = reactive({ care_episode_id: '', medico: '', reason: '' });
const medicos = computed(() => opcionesDeMedico(datos.value?.doctors));
const asignadosA = (episodio) => (datos.value?.assignments ?? []).filter((a) => a.care_episode_id === episodio);
const ruta = () => `/patients/${encodeURIComponent(props.codigo)}/care-assignments`;

async function pedir(peticion, exito) {
    cargando.value = true;
    error.value = '';
    try {
        await peticion();
        aviso.value = exito;
        datos.value = (await api.get(ruta())).data;
        return true;
    } catch (e) {
        aviso.value = '';
        error.value = mensajeDeAsignacion(e.response?.data?.code, e.response?.status);
        return false;
    } finally {
        cargando.value = false;
    }
}

function cargar() {
    aviso.value = '';
    pedir(async () => {}, '');
}

async function asignar() {
    errores.value = erroresDeAsignacion(form);
    if (Object.keys(errores.value).length) {
        return;
    }
    const [doctor_profile_id, specialty_id] = form.medico.split('|');
    const nombre = medicos.value.find((m) => m.valor === form.medico)?.etiqueta ?? '';
    const ok = await pedir(
        () => api.post(ruta(), { care_episode_id: form.care_episode_id, doctor_profile_id, specialty_id, reason: form.reason.trim() }),
        `Asignado: ${nombre}.`,
    );
    if (ok) {
        Object.assign(form, { care_episode_id: '', medico: '', reason: '' });
    }
}

function finalizar(asignacion) {
    if (! window.confirm(`¿Finalizar la atención de ${asignacion.display_name}? Dejará de ver el expediente.`)) {
        return;
    }
    pedir(() => api.delete(`${ruta()}/${encodeURIComponent(asignacion.care_assignment_id)}`), `${asignacion.display_name} ya no es médico tratante.`);
}

// Otro paciente: el panel se vacía para no mostrar asignaciones ajenas.
watch(() => props.codigo, () => {
    datos.value = null;
    aviso.value = '';
    error.value = '';
});
</script>

<template>
    <section class="exp__card" aria-labelledby="ma-titulo">
        <h2 id="ma-titulo">Médicos tratantes</h2>
        <p class="exp__nota">Solo el médico asignado a un episodio abierto puede consultar el expediente.</p>
        <button type="button" class="exp__secundario" :disabled="cargando" @click="cargar">Ver episodios abiertos de {{ codigo }}</button>
        <p class="exp__ok" role="status" aria-live="polite">{{ aviso }}</p>
        <p v-if="error" class="exp__error" role="alert">{{ error }}</p>

        <template v-if="datos">
            <p v-if="datos.episodes.length === 0">{{ datos.patient.display_name }} no tiene episodios abiertos.</p>
            <ul class="ma__episodios">
                <li v-for="e in datos.episodes" :key="e.care_episode_id">
                    <strong>{{ etiquetaTipo(e.type) }}</strong> desde {{ formatearFecha(e.started_at) }}
                    <span v-if="e.service_code"> · {{ e.service_code }}</span>
                    <ul>
                        <li v-if="asignadosA(e.care_episode_id).length === 0" class="exp__nota">Sin médico tratante.</li>
                        <li v-for="a in asignadosA(e.care_episode_id)" :key="a.care_assignment_id" class="ma__asignado">
                            {{ a.display_name }}<span v-if="a.specialty"> · {{ a.specialty }}</span>
                            <button type="button" class="exp__secundario" :disabled="cargando" @click="finalizar(a)">Finalizar</button>
                        </li>
                    </ul>
                </li>
            </ul>

            <form v-if="datos.episodes.length" class="ma__form" novalidate @submit.prevent="asignar">
                <label for="ma-episodio">Episodio</label>
                <select id="ma-episodio" v-model="form.care_episode_id" :aria-invalid="String(Boolean(errores.care_episode_id))">
                    <option value="" disabled>Elija un episodio…</option>
                    <option v-for="e in datos.episodes" :key="e.care_episode_id" :value="e.care_episode_id">{{ etiquetaTipo(e.type) }} · {{ formatearFecha(e.started_at) }}</option>
                </select>
                <label for="ma-medico">Médico y especialidad</label>
                <select id="ma-medico" v-model="form.medico" :aria-invalid="String(Boolean(errores.medico))">
                    <option value="" disabled>{{ medicos.length ? 'Elija un médico…' : 'No hay médicos con especialidad vigente' }}</option>
                    <option v-for="m in medicos" :key="m.valor" :value="m.valor">{{ m.etiqueta }}</option>
                </select>
                <label for="ma-motivo">Motivo de la asignación</label>
                <input id="ma-motivo" v-model="form.reason" maxlength="200" :aria-invalid="String(Boolean(errores.reason))" placeholder="Por ejemplo, médico tratante del ingreso">
                <p v-for="(m, campo) in errores" :key="campo" class="exp__error">{{ m }}</p>
                <button type="submit" :disabled="cargando">Asignar médico tratante</button>
            </form>
        </template>
    </section>
</template>

<style scoped>
.exp__card { display: grid; gap: 0.5rem; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; background: #fff; }
.exp__card h2 { font-size: 1.125rem; font-weight: 600; }
.exp__nota { color: #475569; }
.exp__ok { color: #15803d; }
.exp__error { color: #b91c1c; }
button { justify-self: start; padding: 0.5rem 1rem; border-radius: 0.375rem; background: #1d4ed8; color: #fff; }
button:disabled { opacity: 0.6; }
.exp__secundario { background: #fff; color: #1d4ed8; border: 1px solid #1d4ed8; }
.ma__episodios { display: grid; gap: 0.75rem; }
.ma__episodios ul { display: grid; gap: 0.35rem; margin-top: 0.35rem; padding-left: 1rem; }
.ma__asignado { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.ma__asignado button { padding: 0.25rem 0.75rem; }
.ma__form { display: grid; gap: 0.35rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0; }
.ma__form select, .ma__form input { padding: 0.5rem; border: 1px solid #94a3b8; border-radius: 0.375rem; background: #fff; }
.ma__form [aria-invalid='true'] { border-color: #b91c1c; }
</style>
