<script setup>
import { computed, ref } from 'vue';
import api from '@/plugins/axios';
import { errorDeFiltros, leyendaDelResultado, mensajeDeError, porcentajeLegible, trazabilidad } from '@/modules/reportes/reportes';

const desde = ref('');
const hasta = ref('');
const servicio = ref('');
const resultado = ref(null);
const loading = ref(false);
const error = ref('');
const denied = ref(false);

const filtroExiste = computed(() => errorDeFiltros({ desde: desde.value, hasta: hasta.value }));

/** ISO-8601 con offset -06:00, como exige el contrato del indicador. */
function isoLocal( valor ) {
    return valor ? `${valor}T00:00:00-06:00` : '';
}

async function calculate() {
    error.value = errorDeFiltros({ desde: desde.value, hasta: hasta.value });

    if (error.value) {
        return;
    }

    loading.value = true;
    denied.value = false;
    resultado.value = null;

    const body = { from: isoLocal(desde.value), until: isoLocal(hasta.value) };
    const codigo = servicio.value.trim();

    if (codigo) {
        body.service_code = codigo;
    }

    try {
        const { data } = await api.post('/reports/indicators/IND-RA-01/calculate', body);
        resultado.value = data.data;
    } catch (e) {
        const estado = e.response?.status;
        denied.value = estado === 403;
        error.value = mensajeDeError(e.response?.data?.error?.code, estado);
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <section aria-labelledby="reportes-title" :aria-busy="loading">
        <h1 id="reportes-title">Reportes</h1>
        <p>
            Tasa de cancelación de hospitalizaciones
            <strong>IND-RA-01</strong> (fórmula <strong>TCI-1.0</strong>). Solo consulta;
            el cálculo y la autoridad de los datos los resuelve el servidor.
        </p>

        <form @submit.prevent="calculate">
            <label for="rp-desde">Desde</label>
            <input id="rp-desde" v-model="desde" type="date" required />

            <label for="rp-hasta">Hasta</label>
            <input id="rp-hasta" v-model="hasta" type="date" required />

            <label for="rp-servicio">Sala (código, opcional)</label>
            <input id="rp-servicio" v-model="servicio" type="text" maxlength="64" />

            <button type="submit" :disabled="loading">{{ loading ? 'Calculando…' : 'Calcular' }}</button>
        </form>

        <p v-if="filtroExiste" role="status">{{ filtroExiste }}</p>
        <p v-else-if="error" role="alert">{{ error }}<span v-if="denied"> No tienes permiso para consultar reportes.</span></p>

        <template v-if="resultado">
            <p>
                {{ leyendaDelResultado(resultado) }}
            </p>
            <p>
                <strong>Tasa de cancelación:</strong>
                {{ porcentajeLegible(resultado.percentage) }}
            </p>
            <p><small>{{ trazabilidad(resultado) }}</small></p>
        </template>
    </section>
</template>
