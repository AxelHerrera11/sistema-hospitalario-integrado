<template>
    <section class="panel" aria-labelledby="panel-cola-titulo">
        <h3 id="panel-cola-titulo" class="panel__title">
            Cola de sincronización
        </h3>
        <p class="panel__resumen">
            {{ online ? 'En línea' : 'Sin conexión' }} · {{ estado.pendientes.length }} pendientes · {{ estado.fallidas.length }} fallidas
        </p>
        <p v-if="! estado.pendientes.length && ! estado.fallidas.length" class="panel__vacio">
            Cola vacía. Las acciones de autenticación nunca se encolan.
        </p>
        <!-- Solo metadatos (describe()): el contenido de cada acción nunca se muestra. -->
        <ul class="panel__lista">
            <li v-for="accion in estado.pendientes" :key="accion.id" class="panel__item">
                <strong>{{ accion.etiqueta }}</strong>
                <span>{{ fecha(accion.creada) }} · intento {{ accion.intentos }}</span>
            </li>
            <li v-for="accion in estado.fallidas" :key="accion.id" class="panel__item panel__item--fallida">
                <strong>{{ accion.etiqueta }}</strong>
                <span>{{ accion.error ?? 'Error' }} · {{ accion.intentos }} intentos</span>
                <BaseButton variant="secondary" @click="reintentar(accion.id)">
                    Reintentar
                </BaseButton>
            </li>
        </ul>
        <div class="panel__acciones">
            <BaseButton
                :disabled="! online || ! estado.pendientes.length"
                :loading="estado.sincronizando"
                loading-text="Sincronizando…"
                @click="sincronizar"
            >
                Sincronizar ahora
            </BaseButton>
            <BaseButton variant="danger" :disabled="! estado.fallidas.length" @click="limpiarFallidas">
                Limpiar fallidas
            </BaseButton>
        </div>
    </section>
</template>

<script setup>
import BaseButton from '@/modules/tenants/responsive/BaseButton.vue';
import { useOfflineQueue } from './useOfflineQueue';

const { online, estado, sincronizar, reintentar, limpiarFallidas } = useOfflineQueue();

const fecha = (ms) => new Date(ms).toLocaleString('es-GT');
</script>

<style scoped>
.panel {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #ffffff;
}

.panel__title { margin: 0; font-size: 1rem; font-weight: 700; }
.panel__resumen, .panel__vacio { margin: 0; font-size: 0.85rem; color: #475569; }

.panel__lista {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.panel__item {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.6rem 0.75rem;
    border-radius: 8px;
    background: #f8fafc;
    font-size: 0.85rem;
}

.panel__item--fallida { background: #fef2f2; }

.panel__acciones {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
</style>
