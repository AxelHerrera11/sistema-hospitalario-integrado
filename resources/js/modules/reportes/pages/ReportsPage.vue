<script setup>
import { ref, watch } from 'vue';
import api from '@/plugins/axios';
import { AYUDA_INTERVALO, errorDeFiltros, leyendaDelResultado, mensajeDeError, porcentajeLegible, trazabilidad } from '@/modules/reportes/reportes';

const desde = ref('');
const hasta = ref('');
const servicio = ref('');
const resultado = ref(null);
const loading = ref(false);
const error = ref('');

// Un resultado solo vale para los filtros con los que se calculó.
watch([desde, hasta, servicio], limpiar);

function limpiar() {
    resultado.value = null;
    error.value = '';
}

/** ISO-8601 con offset -06:00, como exige el contrato del indicador. */
function isoLocal( valor ) {
    return valor ? `${valor}T00:00:00-06:00` : '';
}

async function calculate() {
    limpiar();
    error.value = errorDeFiltros({ desde: desde.value, hasta: hasta.value });

    if (error.value) {
        return;
    }

    loading.value = true;

    const body = { from: isoLocal(desde.value), until: isoLocal(hasta.value) };
    const codigo = servicio.value.trim();

    if (codigo) {
        body.service_code = codigo;
    }

    try {
        const { data } = await api.post('/reports/indicators/IND-RA-01/calculate', body);
        resultado.value = data.data;
    } catch (e) {
        const cuerpo = e.response?.data;
        error.value = mensajeDeError(cuerpo?.error?.code ?? cuerpo?.error ?? cuerpo?.code, e.response?.status);
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <section class="reports" aria-labelledby="reportes-title" :aria-busy="loading">
        <h1 id="reportes-title">Reportes</h1>
        <p>
            Tasa de cancelación de hospitalizaciones
            <strong>IND-RA-01</strong> (fórmula <strong>TCI-1.0</strong>). Solo consulta;
            el cálculo y la autoridad de los datos los resuelve el servidor.
        </p>

        <form class="reports__form" novalidate @submit.prevent="calculate">
            <div class="reports__field">
                <label for="rp-desde">Desde</label>
                <input id="rp-desde" v-model="desde" type="date" required aria-describedby="rp-ayuda" />
            </div>
            <div class="reports__field">
                <label for="rp-hasta">Hasta (no incluido)</label>
                <input id="rp-hasta" v-model="hasta" type="date" required aria-describedby="rp-ayuda" />
            </div>
            <div class="reports__field">
                <label for="rp-servicio">Sala (código, opcional)</label>
                <input id="rp-servicio" v-model="servicio" type="text" maxlength="64" />
            </div>
            <p id="rp-ayuda" class="reports__help">{{ AYUDA_INTERVALO }}</p>
            <button type="submit" :disabled="loading">{{ loading ? 'Calculando…' : 'Calcular' }}</button>
        </form>

        <p v-if="error" class="reports__error" role="alert">{{ error }}</p>

        <article v-if="resultado" class="reports__result" role="status">
            <p>{{ leyendaDelResultado(resultado) }}</p>
            <p class="reports__value">
                <strong>Tasa de cancelación:</strong>
                <span>{{ porcentajeLegible(resultado.percentage) }}</span>
            </p>
            <p><small>{{ trazabilidad(resultado) }}</small></p>
        </article>
    </section>
</template>

<style scoped>
.reports { max-width: 48rem; }
.reports h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
.reports__form { display: grid; gap: 0.75rem; grid-template-columns: repeat(3, minmax(0, 1fr)); margin: 1rem 0; align-items: end; }
.reports__field { display: flex; flex-direction: column; gap: 0.25rem; }
.reports__field label { font-weight: 600; }
.reports__field input { border: 1px solid #64748b; border-radius: 6px; padding: 0.5rem; min-height: 44px; width: 100%; background: #fff; }
.reports__help { grid-column: 1 / -1; color: #475569; font-size: 0.875rem; }
.reports__form button { grid-column: 1 / -1; justify-self: start; min-height: 44px; padding: 0.5rem 1.25rem; border-radius: 6px; background: #1e3a8a; color: #fff; font-weight: 600; }
.reports__form button:disabled { opacity: 0.6; }
.reports__form :is(input, button):focus-visible { outline: 3px solid #1d4ed8; outline-offset: 2px; }
.reports__error { border: 1px solid #b91c1c; border-radius: 6px; background: #fef2f2; color: #991b1b; padding: 0.75rem; }
.reports__result { border: 1px solid #cbd5e1; border-radius: 8px; padding: 1rem; display: grid; gap: 0.5rem; }
.reports__value span { display: block; font-size: 2rem; font-weight: 700; color: #1e3a8a; }
.reports__result small { overflow-wrap: anywhere; color: #475569; }
@media (max-width: 640px) {
    .reports__form { grid-template-columns: minmax(0, 1fr); }
    .reports__form button { width: 100%; }
}
</style>
