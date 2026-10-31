<template>
    <div v-if="visible" class="indicator" :class="`indicator--${tono}`" role="status" aria-live="polite">
        <span>{{ mensaje }}</span>
        <BaseButton
            v-if="online && estado.pendientes.length"
            variant="secondary"
            :loading="estado.sincronizando"
            loading-text="Sincronizando…"
            @click="sincronizar"
        >
            Sincronizar ahora
        </BaseButton>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import BaseButton from '@/modules/tenants/responsive/BaseButton.vue';
import { useOfflineQueue } from './useOfflineQueue';

const { online, estado, sincronizar } = useOfflineQueue();

const visible = computed(() => ! online.value || estado.pendientes.length > 0 || estado.fallidas.length > 0);
const tono = computed(() => {
    if (! online.value) return 'offline';
    return estado.fallidas.length ? 'error' : 'pendiente';
});
const mensaje = computed(() => {
    if (! online.value) {
        return estado.pendientes.length
            ? `Sin conexión · ${estado.pendientes.length} acciones pendientes`
            : 'Sin conexión · iniciar sesión y administrar usuarios requieren red';
    }
    if (estado.fallidas.length) return `${estado.fallidas.length} acciones no se pudieron sincronizar`;

    return `${estado.pendientes.length} acciones pendientes de sincronizar`;
});
</script>

<style scoped>
.indicator {
    position: sticky;
    top: 0;
    z-index: 50;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    font-weight: 500;
    color: #ffffff;
}

.indicator--offline { background: #92400e; }
.indicator--error { background: #b91c1c; }
.indicator--pendiente { background: #1d4ed8; }
</style>
