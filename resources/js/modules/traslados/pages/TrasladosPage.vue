<template>
    <section class="ta" aria-labelledby="ta-titulo">
        <h1 id="ta-titulo">Traslados y altas</h1>
        <p v-if="!puedeConsultar" class="ta__msg ta__msg--warn" role="alert">
            Su sesión no tiene el permiso <code>traslados.ver</code> en el hospital activo.
        </p>

        <form v-if="puedeConsultar" class="ta__card" @submit.prevent="consultar">
            <label for="ta-estancia">Estancia (cama actual)</label>
            <input id="ta-estancia" v-model="estancia" autocomplete="off" required :aria-invalid="String(invalido === 'estancia')">
            <button type="submit" :disabled="ocupado">Consultar</button>
        </form>

        <section v-if="situacion" class="ta__card" aria-labelledby="ta-situacion">
            <h2 id="ta-situacion" ref="tituloSituacion" tabindex="-1">Situación actual</h2>
            <dl class="ta__datos">
                <dt>Episodio</dt><dd>{{ situacion.estado === 'open' ? 'abierto' : 'cerrado' }}</dd>
                <dt>Cama actual</dt><dd>{{ situacion.cama_actual_id ?? '—' }}</dd>
                <dt>Estancia vigente</dt><dd>{{ situacion.estancia_consultada_abierta ? 'sí' : 'no (los datos cambiaron)' }}</dd>
            </dl>
            <div v-if="operable" class="ta__acciones">
                <button v-if="puedeTrasladar" type="button" @click="elegir('traslado')">Trasladar</button>
                <button v-if="puedeDarAlta" type="button" class="ta__secundario" @click="elegir('alta')">Dar de alta</button>
            </div>
        </section>

        <form v-if="operacion && !confirmando" class="ta__card" novalidate @submit.prevent="revisar">
            <h2>{{ operacion === 'traslado' ? 'Datos del traslado' : 'Alta hospitalaria' }}</h2>
            <template v-if="operacion === 'traslado'">
                <label for="ta-cama">Cama destino (obligatorio)</label>
                <input id="ta-cama" v-model="cama" autocomplete="off" required :aria-invalid="String(invalido === 'cama_destino_id')" aria-describedby="ta-error-cama">
                <p v-if="invalido === 'cama_destino_id'" id="ta-error-cama" class="ta__error" role="alert">Indique una cama destino válida.</p>
                <label for="ta-motivo">Motivo del traslado (obligatorio)</label>
                <textarea id="ta-motivo" v-model="motivo" maxlength="500" rows="3" required :aria-invalid="String(invalido === 'motivo')" aria-describedby="ta-error-motivo"></textarea>
                <p v-if="invalido === 'motivo'" id="ta-error-motivo" class="ta__error" role="alert">El motivo es obligatorio (máximo 500 caracteres).</p>
            </template>
            <button type="submit">Revisar</button>
            <button type="button" class="ta__secundario" @click="operacion = null">Cancelar</button>
        </form>

        <section v-if="confirmando" class="ta__card" aria-labelledby="ta-confirmar">
            <h2 id="ta-confirmar">Confirmar</h2>
            <p>{{ resumen }}</p>
            <button ref="botonConfirmar" type="button" :disabled="ocupado" @click="confirmar">{{ ocupado ? 'Procesando…' : 'Confirmar' }}</button>
            <button type="button" class="ta__secundario" :disabled="ocupado" @click="confirmando = false">Volver</button>
        </section>

        <p class="ta__msg" :class="`ta__msg--${mensaje.tipo}`" role="status" aria-live="polite">{{ mensaje.texto }}</p>
        <button v-if="mensaje.debeConsultar" type="button" @click="consultar">Consultar estado</button>
    </section>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue';
import api from '@/plugins/axios';
import { can } from '@/router/permissions';
import { useAuthStore } from '@/stores/auth';
import { interpretar, PERMISOS, respuestaDeError, validarTraslado } from '@/modules/traslados/traslados';

const auth = useAuthStore();
// Mostrar u ocultar acciones solo mejora la navegación: el servidor vuelve a exigir cada permiso.
const puedeConsultar = computed(() => can(auth.user, PERMISOS.consultar));
const puedeTrasladar = computed(() => can(auth.user, PERMISOS.trasladar));
const puedeDarAlta = computed(() => can(auth.user, PERMISOS.darAlta));

const estancia = ref('');
const cama = ref('');
const motivo = ref('');
const situacion = ref(null);
const operacion = ref(null);
const confirmando = ref(false);
const ocupado = ref(false);
const invalido = ref(null);
const mensaje = ref({ tipo: '', texto: '' });
const tituloSituacion = ref(null);
const botonConfirmar = ref(null);
const operable = computed(() => situacion.value?.estado === 'open' && situacion.value?.estancia_consultada_abierta);
const resumen = computed(() => (operacion.value === 'traslado'
    ? `Trasladar la estancia ${estancia.value.trim()} a la cama ${cama.value.trim()}. Motivo: ${motivo.value.trim()}`
    : `Dar de alta: se cerrará la estancia ${estancia.value.trim()} y el episodio.`));

async function consultar() {
    invalido.value = /^[0-9a-f-]{36}$/i.test(estancia.value.trim()) ? null : 'estancia';
    if (invalido.value) return (mensaje.value = { tipo: 'invalida', texto: 'Indique una estancia válida.' });
    ocupado.value = true;
    mensaje.value = { tipo: '', texto: 'Consultando…' };
    try {
        const { data } = await api.get(`/estancias/${encodeURIComponent(estancia.value.trim())}/internacion`);
        situacion.value = data;
        mensaje.value = { tipo: '', texto: 'Situación actualizada.' };
        await nextTick();
        tituloSituacion.value?.focus();
    } catch (error) {
        situacion.value = null;
        const { estado, cuerpo } = respuestaDeError(error, 'get');
        mensaje.value = estado === 404 ? { tipo: 'rechazo', texto: 'La estancia no existe.' } : interpretar(estado, cuerpo);
    } finally {
        ocupado.value = false;
    }
}

function elegir(tipo) {
    operacion.value = tipo;
    confirmando.value = false;
    invalido.value = null;
}

async function revisar() {
    invalido.value = operacion.value === 'traslado' ? validarTraslado({ cama_destino_id: cama.value, motivo: motivo.value }) : null;
    if (invalido.value) return;
    confirmando.value = true;
    await nextTick();
    botonConfirmar.value?.focus();
}

async function confirmar() {
    ocupado.value = true;
    mensaje.value = { tipo: '', texto: 'Procesando…' };
    const traslado = operacion.value === 'traslado';
    const cuerpo = { estancia_esperada_id: estancia.value.trim(), ...(traslado ? { cama_destino_id: cama.value.trim(), motivo: motivo.value.trim() } : {}) };
    try {
        const respuesta = await api.post(traslado ? '/traslados' : '/altas', cuerpo);
        mensaje.value = interpretar(respuesta.status, respuesta.data);
        if (traslado) estancia.value = respuesta.data.estancia_destino_id;
        cama.value = motivo.value = '';
        operacion.value = null;
        situacion.value = null;
    } catch (error) {
        const { estado, cuerpo: datos } = respuestaDeError(error, 'post');
        mensaje.value = interpretar(estado, datos);
    } finally {
        ocupado.value = false;
        confirmando.value = false;
    }
}
</script>

<style scoped>
.ta { max-width: 40rem; margin: 0 auto; color: #1f2933; }
.ta h1 { font-size: 1.6rem; font-weight: 700; margin: .5rem 0 1rem; }
.ta h2 { font-size: 1.2rem; font-weight: 600; margin: 0 0 .75rem; }
.ta__card { border: 1px solid #cbd2d9; border-radius: 6px; padding: 1rem; margin-bottom: 1rem; background: #fff; }
.ta label { display: block; font-weight: 600; margin-top: .75rem; }
.ta input, .ta textarea { box-sizing: border-box; width: 100%; min-height: 44px; padding: .5rem; border: 1px solid #52606d; border-radius: 4px; font: inherit; }
.ta button { min-height: 44px; margin-top: .75rem; margin-right: .5rem; padding: .6rem 1rem; border: 1px solid #1d4ed8; border-radius: 4px; background: #1d4ed8; color: #fff; font: inherit; font-weight: 600; }
.ta .ta__secundario { background: #fff; color: #1e3a8a; }
.ta button:disabled { opacity: .6; }
.ta button:focus-visible, .ta input:focus-visible, .ta textarea:focus-visible { outline: 3px solid #f59e0b; outline-offset: 2px; }
.ta__datos { display: grid; grid-template-columns: max-content 1fr; gap: .25rem 1rem; }
.ta__datos dd { margin: 0; overflow-wrap: anywhere; }
.ta__error { color: #b91c1c; margin: .25rem 0; }
.ta__msg:empty { display: none; }
.ta__msg { padding: .6rem .85rem; border-left: 4px solid #52606d; background: #fff; border-radius: 6px; }
.ta__msg--exito { border-color: #15803d; }
.ta__msg--rechazo, .ta__msg--invalida, .ta__msg--error { border-color: #b91c1c; }
.ta__msg--reintentar, .ta__msg--consultar, .ta__msg--warn { border-color: #c2410c; }
@media (max-width: 30rem) {
    .ta button { width: 100%; margin-right: 0; }
    .ta__datos { grid-template-columns: 1fr; }
}
</style>
