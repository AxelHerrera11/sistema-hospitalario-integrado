<template>
    <section class="users">
        <h1>Usuarios del hospital</h1>
        <form class="users__search" @submit.prevent="loadUsers(1)">
            <label for="user-search">Buscar por correo o nombre</label>
            <input id="user-search" v-model="search" maxlength="128">
            <button :disabled="loading" type="submit">Buscar</button>
        </form>
        <p class="users__notice" role="status" aria-live="polite">{{ notice }}</p>
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
                    <th v-if="canManage">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users" :key="user.user_id">
                    <td>{{ user.display_name }}</td>
                    <td>{{ user.email }}</td>
                    <td>{{ user.status }}</td>
                    <td>
                        <span v-if="user.roles.length === 0">Sin roles</span>
                        <span v-for="role in user.roles" :key="role" class="users__role">
                            {{ etiquetaRol(role) }}
                            <button
                                v-if="actions(user).retirar"
                                type="button"
                                class="users__remove"
                                :disabled="busy"
                                :aria-label="`Retirar el rol ${etiquetaRol(role)} a ${user.display_name}`"
                                @click="revokeRole(user, role)"
                            >×</button>
                        </span>
                    </td>
                    <td v-if="canManage" class="users__actions">
                        <form v-if="actions(user).asignar && rolesAsignables(user).length" @submit.prevent="assignRole(user)">
                            <label class="sr-only" :for="`role-${user.user_id}`">Rol para {{ user.display_name }}</label>
                            <select :id="`role-${user.user_id}`" v-model="selected[user.user_id]">
                                <option :value="undefined" disabled>Rol…</option>
                                <option v-for="role in rolesAsignables(user)" :key="role.valor" :value="role.valor">{{ role.etiqueta }}</option>
                            </select>
                            <button type="submit" :disabled="busy || !selected[user.user_id]">Asignar</button>
                        </form>
                        <button v-if="actions(user).desactivar" type="button" class="users__danger" :disabled="busy" @click="deactivate(user)">
                            Revocar acceso
                        </button>
                    </td>
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
import { computed, onMounted, reactive, ref } from 'vue';
import api from '@/plugins/axios';
import { can } from '@/router/permissions';
import { useAuthStore } from '@/stores/auth';
import { accionesPara, confirmacion, etiquetaRol, mensajeDeError, rolesAsignables } from '@/modules/tenants/hospitalUsers';

const auth = useAuthStore();
const users = ref([]);
const search = ref('');
const page = ref(1);
const lastPage = ref(1);
const loading = ref(false);
const busy = ref(false);
const errorMessage = ref('');
const notice = ref('');
const selected = reactive({});

const permissions = computed(() => ({
    asignar: can(auth.user, 'rbac.asignar'),
    desactivar: can(auth.user, 'usuarios.desactivar'),
}));
const canManage = computed(() => permissions.value.asignar || permissions.value.desactivar);
const actions = (user) => accionesPara(user, auth.user?.user_id, permissions.value);

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

// Cada cambio recarga la página actual: la lista visible siempre refleja lo que guardó el servidor.
async function run(request, success) {
    busy.value = true;
    notice.value = '';
    errorMessage.value = '';
    try {
        await request();
        notice.value = success;
        await loadUsers(page.value);
    } catch (error) {
        errorMessage.value = mensajeDeError(error.response?.data, error.response?.status);
    } finally {
        busy.value = false;
    }
}

function assignRole(user) {
    const role = selected[user.user_id];
    delete selected[user.user_id];
    run(() => api.post(`/admin/users/${encodeURIComponent(user.user_id)}/roles`, { role_code: role }),
        `Rol «${etiquetaRol(role)}» asignado a ${user.display_name}.`);
}

function revokeRole(user, role) {
    if (! window.confirm(confirmacion('retirar', user, role))) {
        return;
    }
    run(() => api.delete(`/admin/users/${encodeURIComponent(user.user_id)}/roles/${encodeURIComponent(role)}`),
        `Rol «${etiquetaRol(role)}» retirado a ${user.display_name}.`);
}

function deactivate(user) {
    if (! window.confirm(confirmacion('desactivar', user))) {
        return;
    }
    run(() => api.post(`/admin/users/${encodeURIComponent(user.user_id)}/deactivate`),
        `Acceso de ${user.display_name} revocado en este hospital.`);
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

.users__role {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    margin: 0 0.25rem 0.25rem 0;
    padding: 0.125rem 0.5rem;
    border-radius: 999px;
    background: #e0e7ff;
    font-size: 0.875rem;
}

.users__remove {
    padding: 0 0.25rem;
    color: #7f1d1d;
}

.users__actions {
    display: grid;
    gap: 0.5rem;
}

.users__actions form {
    display: flex;
    gap: 0.25rem;
}

.users__actions select {
    padding: 0.25rem;
    border: 1px solid #94a3b8;
    border-radius: 0.375rem;
}

.users__danger {
    justify-self: start;
    color: #b91c1c;
}

.users__error {
    color: #b91c1c;
}

.users__notice {
    color: #15803d;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
}
</style>
