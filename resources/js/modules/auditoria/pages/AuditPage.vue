<script setup>
import { onMounted, reactive, ref } from 'vue';
import api from '@/plugins/axios';
import {
    etiquetaResultado, fechaLegible, filtroInvalido, filtrosVacios, mensajeDeError, parametros, recurso, RESULTADOS, resumenPagina,
} from '@/modules/auditoria/auditoria';

const filtros = reactive(filtrosVacios());
const eventos = ref([]);
const meta = ref(null);
const cargando = ref(false);
const error = ref('');
const invalido = ref(null);
const detalle = ref(null);

async function consultar(pagina = 1) {
    invalido.value = filtroInvalido(filtros);
    if (invalido.value) {
        error.value = mensajeDeError('AUDIT_FILTER_INVALID');
        return;
    }
    cargando.value = true;
    error.value = '';
    detalle.value = null;
    try {
        const { data } = await api.get('/audit-events', { params: parametros(filtros, pagina) });
        eventos.value = data.data;
        meta.value = data.meta;
    } catch (e) {
        // Falla segura: sin respuesta válida no se muestran eventos anteriores como si fueran actuales.
        eventos.value = [];
        meta.value = null;
        invalido.value = Object.keys(e.response?.data?.errors ?? {})[0] ?? null;
        error.value = mensajeDeError(e.response?.data?.code, e.response?.status);
    } finally {
        cargando.value = false;
    }
}

function limpiar() {
    Object.assign(filtros, filtrosVacios());
    consultar(1);
}

onMounted(() => consultar(1));
</script>

<template>
    <section class="aud" aria-labelledby="aud-titulo" :aria-busy="cargando">
        <h1 id="aud-titulo">Auditoría de movimientos</h1>
        <p class="aud__nota">Bitácora del hospital de su sesión. Solo lectura: los eventos no se pueden editar ni borrar.</p>

        <form class="aud__filtros" novalidate @submit.prevent="consultar(1)">
            <label>Usuario<input v-model="filtros.usuario" maxlength="128" :aria-invalid="String(invalido === 'usuario')"></label>
            <label>Módulo<input v-model="filtros.modulo" maxlength="40" placeholder="expediente_medico" :aria-invalid="String(invalido === 'modulo')"></label>
            <label>Acción<input v-model="filtros.accion" maxlength="80" :aria-invalid="String(invalido === 'accion')"></label>
            <label>Desde<input v-model="filtros.desde" type="date" :aria-invalid="String(invalido === 'desde')"></label>
            <label>Hasta<input v-model="filtros.hasta" type="date" :aria-invalid="String(invalido === 'hasta')"></label>
            <label>Resultado
                <select v-model="filtros.resultado" :aria-invalid="String(invalido === 'resultado')">
                    <option v-for="r in RESULTADOS" :key="r.valor" :value="r.valor">{{ r.etiqueta }}</option>
                </select>
            </label>
            <label>Correlación<input v-model="filtros.correlacion" maxlength="64" :aria-invalid="String(invalido === 'correlacion')"></label>
            <div class="aud__acciones">
                <button type="submit" :disabled="cargando">Consultar</button>
                <button type="button" class="aud__secundario" :disabled="cargando" @click="limpiar">Limpiar</button>
            </div>
        </form>

        <p v-if="error" class="aud__error" role="alert">{{ error }}</p>
        <p v-else class="aud__resumen" role="status" aria-live="polite">{{ cargando ? 'Consultando…' : resumenPagina(meta) }}</p>

        <div v-if="eventos.length" class="aud__tabla">
            <table>
                <caption class="sr-only">Eventos de auditoría</caption>
                <thead>
                    <tr><th scope="col">Fecha</th><th scope="col">Usuario</th><th scope="col">Evento</th><th scope="col">Recurso</th><th scope="col">Resultado</th><th scope="col"><span class="sr-only">Detalle</span></th></tr>
                </thead>
                <tbody>
                    <tr v-for="e in eventos" :key="e.event_id">
                        <td>{{ fechaLegible(e.occurred_at) }}</td>
                        <td class="aud__mono">{{ e.actor_id }}</td>
                        <td>{{ e.event }}</td>
                        <td>{{ recurso(e) }}</td>
                        <td><span class="aud__chip" :class="`aud__chip--${e.outcome}`">{{ etiquetaResultado(e.outcome) }}</span></td>
                        <td><button type="button" class="aud__secundario" :aria-expanded="String(detalle?.event_id === e.event_id)" @click="detalle = detalle?.event_id === e.event_id ? null : e">Detalle</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <dl v-if="detalle" class="aud__detalle" aria-label="Detalle del evento">
            <dt>Correlación</dt><dd class="aud__mono">{{ detalle.correlation_id ?? '—' }}</dd>
            <dt>Motivo</dt><dd>{{ detalle.reason ?? '—' }}</dd>
            <dt>Criticidad</dt><dd>{{ detalle.criticality ?? '—' }}</dd>
            <dt>Contexto</dt><dd><pre>{{ JSON.stringify(detalle.context, null, 2) }}</pre></dd>
            <dt v-if="detalle.redacted_keys?.length">Campos ocultos</dt><dd v-if="detalle.redacted_keys?.length">{{ detalle.redacted_keys.join(', ') }}</dd>
        </dl>

        <nav v-if="meta && meta.total_pages > 1" class="aud__paginas" aria-label="Paginación de eventos">
            <button type="button" :disabled="cargando || meta.page <= 1" @click="consultar(meta.page - 1)">Anterior</button>
            <button type="button" :disabled="cargando || meta.page >= meta.total_pages" @click="consultar(meta.page + 1)">Siguiente</button>
        </nav>
    </section>
</template>

<style scoped>
.aud { display: grid; gap: 1rem; }
.aud h1 { font-size: 1.5rem; font-weight: 700; }
.aud__nota, .aud__resumen { color: #475569; }
.aud__filtros { display: grid; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); gap: 0.75rem; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; background: #fff; }
.aud__filtros label { display: grid; gap: 0.25rem; font-size: 0.875rem; }
.aud__filtros input, .aud__filtros select { padding: 0.5rem; border: 1px solid #94a3b8; border-radius: 0.375rem; }
.aud__filtros [aria-invalid='true'] { border-color: #b91c1c; }
.aud__acciones { display: flex; gap: 0.5rem; align-items: end; }
.aud button { padding: 0.5rem 1rem; border-radius: 0.375rem; background: #1d4ed8; color: #fff; }
.aud button:disabled { opacity: 0.6; }
.aud .aud__secundario { background: #fff; color: #1d4ed8; border: 1px solid #1d4ed8; }
.aud__tabla { overflow-x: auto; }
.aud table { width: 100%; border-collapse: collapse; background: #fff; }
.aud th, .aud td { padding: 0.5rem; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 0.875rem; }
.aud__mono { font-family: ui-monospace, monospace; word-break: break-all; }
.aud__chip { padding: 0.125rem 0.5rem; border-radius: 999px; font-size: 0.75rem; }
.aud__chip--success { background: #dcfce7; color: #14532d; }
.aud__chip--failure { background: #fee2e2; color: #7f1d1d; }
.aud__chip--denied { background: #fef3c7; color: #78350f; }
.aud__detalle { display: grid; grid-template-columns: max-content 1fr; gap: 0.25rem 1rem; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; background: #f8fafc; }
.aud__detalle pre { white-space: pre-wrap; font-size: 0.75rem; }
.aud__paginas { display: flex; gap: 0.5rem; }
.aud__error { color: #b91c1c; }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
</style>
