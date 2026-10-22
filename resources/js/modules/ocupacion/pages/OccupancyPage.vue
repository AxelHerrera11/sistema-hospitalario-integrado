<script setup>
import { onMounted, ref } from 'vue';
import api from '@/plugins/axios';
import { estaDesactualizada, mensajeDeError, metricasGlobales, tasaLegible } from '@/modules/ocupacion/ocupacion';

const snapshot = ref(null);
const loading = ref(false);
const error = ref('');
const denied = ref(false);
const desactualizada = ref(false);
const requestId = ref('');

async function load() {
    loading.value = true;
    error.value = '';
    denied.value = false;

    try {
        const { data } = await api.get('/occupancy');
        snapshot.value = data.data;
        requestId.value = data.meta?.request_id ?? '';
        desactualizada.value = estaDesactualizada(data.data?.cutoff_at, new Date());
    } catch (e) {
        const status = e.response?.status;
        const code = e.response?.data?.error?.code;
        denied.value = status === 403;
        error.value = mensajeDeError(code, status);

        if (snapshot.value) {
            desactualizada.value = true;
        }
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

<template>
    <section aria-labelledby="ocupacion-title" :aria-busy="loading">
        <h1 id="ocupacion-title">Ocupación hospitalaria</h1>
        <p>Fotografía de ocupación por sala en el corte indicado. Solo lectura.</p>

        <button type="button" :disabled="loading" @click="load">Actualizar</button>

        <p v-if="loading" role="status">Cargando ocupación…</p>
        <p v-else-if="error" role="alert">
            {{ error }}
            <span v-if="snapshot"> Se muestra la última fotografía conocida.</span>
        </p>
        <p v-else-if="denied" role="alert">No tienes permiso para consultar la ocupación.</p>

        <template v-if="snapshot">
            <p>
                Corte: <time :datetime="snapshot.cutoff_at">{{ snapshot.cutoff_at }}</time>
                <span v-if="desactualizada" role="status">— fotografía desactualizada</span>
            </p>
            <p v-if="requestId">Identificador de la solicitud: <code>{{ requestId }}</code></p>

            <h2>Resumen global</h2>
            <ul class="ocupacion__metricas">
                <li v-for="metrica in metricasGlobales(snapshot.global)" :key="metrica.clave">
                    <span class="ocupacion__etiqueta">{{ metrica.etiqueta }}</span>
                    <strong>{{ metrica.valor }}</strong>
                </li>
                <li>
                    <span class="ocupacion__etiqueta">Tasa de ocupación</span>
                    <strong>{{ tasaLegible(snapshot.global?.occupancy_rate) }}</strong>
                </li>
            </ul>

            <h2>Ocupación por sala</h2>
            <p v-if="! snapshot.wards?.length">No hay salas registradas para este hospital.</p>
            <table v-else>
                <caption class="ocupacion__oculto">
                    Camas ocupadas, disponibles y en mantenimiento por sala en el corte actual
                </caption>
                <thead>
                    <tr>
                        <th scope="col">Sala</th>
                        <th scope="col">Inventario</th>
                        <th scope="col">Ocupadas</th>
                        <th scope="col">Disponibles</th>
                        <th scope="col">Mantenimiento</th>
                        <th scope="col">Inconsistencias</th>
                        <th scope="col">Tasa</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="ward in snapshot.wards" :key="ward.ward_id ?? ward.ward_code">
                        <th scope="row">{{ ward.ward_code }}</th>
                        <td>{{ ward.total_beds }}</td>
                        <td>{{ ward.occupied }}</td>
                        <td>{{ ward.available }}</td>
                        <td>{{ ward.maintenance }}</td>
                        <td>{{ ward.inconsistencies }}</td>
                        <td>{{ tasaLegible(ward.occupancy_rate) }}</td>
                    </tr>
                </tbody>
            </table>

            <p class="ocupacion__nota">
                Las inconsistencias entre el estado operativo de la cama y su estancia vigente se
                muestran; no se corrigen ni se ocultan. La pantalla no muestra datos de pacientes.
            </p>
        </template>
    </section>
</template>

<style scoped>
.ocupacion__metricas {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 0;
    list-style: none;
}

.ocupacion__metricas li {
    display: flex;
    flex-direction: column;
    min-width: 11rem;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    background: #ffffff;
}

.ocupacion__etiqueta {
    font-size: 0.875rem;
    color: #475569;
}

.ocupacion__nota {
    color: #475569;
    font-size: 0.875rem;
}

.ocupacion__oculto {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
}
</style>
