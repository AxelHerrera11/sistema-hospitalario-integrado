<script setup>
import { onMounted, ref } from 'vue';
import api from '@/plugins/axios';
const permissions = ref([]);
const loading = ref(false);
const error = ref('');
async function load() {
    permissions.value = [];
    error.value = '';
    loading.value = true;
    try {
        const { data } = await api.get('/permissions');
        permissions.value = data.permissions;
    } catch (e) {
        const messages = { 401: 'Inicia sesión para continuar.', 403: 'No tienes permiso para consultar el catálogo.', 503: 'El servicio de permisos no está disponible.' };
        error.value = messages[e.response?.status] ?? 'No se pudo consultar el catálogo.';
    } finally { loading.value = false; }
}
onMounted(load);
</script>

<template>
    <section aria-labelledby="rbac-title" :aria-busy="loading">
        <h1 id="rbac-title">Catálogo de permisos</h1>
        <p>Permisos publicados para la integración del sistema hospitalario.</p>
        <button type="button" :disabled="loading" @click="load">Actualizar</button>
        <p v-if="loading" role="status">Cargando permisos…</p>
        <p v-else-if="error" role="alert">{{ error }}</p>
        <ul v-else-if="permissions.length"><li v-for="permission in permissions" :key="permission"><code>{{ permission }}</code></li></ul>
        <p v-else>No hay permisos publicados.</p>
    </section>
</template>
