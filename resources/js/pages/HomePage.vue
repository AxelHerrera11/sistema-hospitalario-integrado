<template>
    <section class="home">
        <h1 class="home__title">
            Inicio
        </h1>
        <p class="home__subtitle">
            Resumen del {{ fecha }} según los permisos de su rol.
        </p>

        <div v-if="error" class="home__alerta" role="alert">
            {{ error }} <button type="button" @click="cargar">Reintentar</button>
        </div>
        <div v-else-if="estado === 'cargando'" aria-busy="true" aria-live="polite">Cargando tablero...</div>
        <p v-else-if="estado === 'vacio'" class="home__subtitle">No hay tarjetas disponibles para su rol hoy.</p>

        <div v-else class="home__tarjetas">
            <article v-for="tarjeta in resumen.tarjetas" :key="tarjeta.clave" class="home__tarjeta">
                <h2>{{ tarjeta.titulo }}: {{ tarjeta.total }}</h2>
                <ul>
                    <li v-for="fila in filasPorEstado(tarjeta.por_estado)" :key="fila.clave">{{ fila.etiqueta }}: {{ fila.valor }}</li>
                </ul>
                <p role="status">{{ textoIndicador(tarjeta.indicador) }}</p>
                <RouterLink :to="tarjeta.ruta">Ver detalle</RouterLink>
            </article>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '@/plugins/axios';
import { estadoDePagina, filasPorEstado, mensajeDeError, textoIndicador } from '@/modules/dashboard/resumen';

const resumen = ref(null);
const cargando = ref(false);
const error = ref('');
const fecha = new Date().toISOString().slice(0, 10);
const estado = computed(() => estadoDePagina(resumen.value, cargando.value));

async function cargar() {
    cargando.value = true;
    error.value = '';
    try {
        const { data } = await api.get('/dashboard/resumen', { params: { fecha } });
        resumen.value = data.data;
    } catch (e) {
        error.value = mensajeDeError(e.response?.status);
    } finally {
        cargando.value = false;
    }
}

onMounted(cargar);
</script>

<style scoped>
.home {
    max-width: 960px;
}

.home__title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.home__subtitle {
    color: #475569;
    line-height: 1.6;
}

.home__tarjetas {
    display: grid;
    gap: 16px;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    margin-top: 16px;
}

.home__tarjeta {
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 16px;
}

.home__alerta {
    border-left: 6px solid #b91c1c;
    background: #fff;
    padding: 12px 16px;
    margin-top: 16px;
}

.home__alerta button {
    min-height: 44px;
    margin-left: 8px;
}
</style>
