<template>
    <section class="users">
        <h1>Usuarios del hospital</h1>
        <form class="users__search" @submit.prevent="loadUsers(1)">
            <label for="user-search">Buscar por correo o nombre</label>
            <input id="user-search" v-model="search" maxlength="128">
            <button :disabled="loading" type="submit">Buscar</button>
        </form>
        <p v-if="errorMessage" class="users__error" role="alert">{{ errorMessage }}</p>
        <p v-else-if="loading">Cargando usuarios…</p>
        <p v-else-if="users.length === 0">No hay usuarios para mostrar.</p>
        <table v-else>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Estado</th>
                    <th>Roles del hospital</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users" :key="user.user_id">
                    <td>{{ user.display_name }}</td>
                    <td>{{ user.email }}</td>
                    <td>{{ user.status }}</td>
                    <td>{{ user.roles.join(', ') || 'Sin roles' }}</td>
                </tr>
            </tbody>
        </table>
        <nav v-if="lastPage > 1" class="users__pagination" aria-label="Paginación de usuarios">
            <button :disabled="page <= 1 || loading" @click="loadUsers(page - 1)">Anterior</button>
            <span>Página {{ page }} de {{ lastPage }}</span>
            <button :disabled="page >= lastPage || loading" @click="loadUsers(page + 1)">Siguiente</button>
        </nav>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '@/plugins/axios';

const users = ref([]);
const search = ref('');
const page = ref(1);
const lastPage = ref(1);
const loading = ref(false);
const errorMessage = ref('');

async function loadUsers(nextPage) {
    loading.value = true;
    errorMessage.value = '';

    try {
        const { data } = await api.get('/admin/users', {
            params: { page: nextPage, per_page: 25, search: search.value || undefined },
        });
        users.value = data.data;
        page.value = data.current_page;
        lastPage.value = data.last_page;
    } catch (error) {
        errorMessage.value = error.response?.data?.message
            ?? 'No fue posible cargar los usuarios autorizados.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => loadUsers(1));
</script>

<style scoped>
.users {
    max-width: 960px;
    margin: 0 auto;
}

.users__search,
.users__pagination {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 1rem 0;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

th,
td {
    padding: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
}

.users__error {
    color: #b91c1c;
}
</style>
