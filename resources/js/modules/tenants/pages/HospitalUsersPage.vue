<template>
    <section class="users">
        <header>
            <p class="users__eyebrow">Administración</p>
            <h1>Usuarios del hospital</h1>
            <p class="users__subtitle">
                {{ auth.hospital?.name || 'Hospital de la sesión' }} · personal con acceso y sus roles en este hospital
            </p>
        </header>

        <ul class="users__stats" aria-label="Resumen">
            <li><strong>{{ stats.total }}</strong><span>Usuarios con acceso</span></li>
            <li><strong>{{ stats.activos }}</strong><span>Activos en esta página</span></li>
            <li><strong>{{ stats.sinRoles }}</strong><span>Sin roles en esta página</span></li>
        </ul>

        <form class="users__search" role="search" @submit.prevent="loadUsers(1)">
            <label class="sr-only" for="user-search">Buscar por correo o nombre</label>
            <input id="user-search" v-model="search" maxlength="128" placeholder="Buscar por nombre o correo…">
            <button :disabled="loading" type="submit">Buscar</button>
            <button v-if="search" type="button" class="users__secondary" :disabled="loading" @click="clearSearch">Limpiar</button>
        </form>

        <p v-if="notice" class="users__msg users__msg--ok" role="status" aria-live="polite">{{ notice }}</p>
        <p v-if="errorMessage" class="users__msg users__msg--error" role="alert">{{ errorMessage }}</p>

        <div class="users__card">
            <p v-if="loading && users.length === 0" class="users__empty">Cargando usuarios…</p>
            <div v-else-if="users.length === 0" class="users__empty">
                <p class="users__empty-title">No hay usuarios para mostrar</p>
                <p>{{ search ? 'Ninguno coincide con la búsqueda.' : 'Este hospital todavía no tiene personal con acceso.' }}</p>
            </div>
            <table v-else :aria-busy="loading">
                <thead>
                    <tr>
                        <th scope="col">Usuario</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Roles del hospital</th>
                        <th v-if="canManage" scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.user_id">
                        <td data-label="Usuario">
                            <div class="users__who">
                                <span class="users__avatar" aria-hidden="true">{{ iniciales(user) }}</span>
                                <div>
                                    <p class="users__name">
                                        {{ user.display_name }}
                                        <span v-if="isSelf(user)" class="users__you">Usted</span>
                                    </p>
                                    <p class="users__email">{{ user.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td data-label="Estado">
                            <span class="users__badge" :class="`users__badge--${estadoDe(user.status).tono}`">{{ estadoDe(user.status).etiqueta }}</span>
                        </td>
                        <td data-label="Roles">
                            <span v-if="user.roles.length === 0" class="users__muted">Sin roles</span>
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
                        <td v-if="canManage" data-label="Acciones" class="users__actions">
                            <form v-if="actions(user).asignar && rolesAsignables(user).length" @submit.prevent="assignRole(user)">
                                <label class="sr-only" :for="`role-${user.user_id}`">Rol para {{ user.display_name }}</label>
                                <select :id="`role-${user.user_id}`" v-model="selected[user.user_id]">
                                    <option :value="undefined" disabled>Agregar rol…</option>
                                    <option v-for="role in rolesAsignables(user)" :key="role.valor" :value="role.valor">{{ role.etiqueta }}</option>
                                </select>
                                <button type="submit" :disabled="busy || !selected[user.user_id]">Asignar</button>
                            </form>
                            <button v-if="actions(user).desactivar" type="button" class="users__danger" :disabled="busy" @click="deactivate(user)">
                                Revocar acceso
                            </button>
                            <span v-if="isSelf(user)" class="users__muted">Su propia cuenta</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav v-if="lastPage > 1" class="users__pagination" aria-label="Paginación de usuarios">
            <button class="users__secondary" :disabled="page <= 1 || loading" @click="loadUsers(page - 1)">Anterior</button>
            <span>Página {{ page }} de {{ lastPage }}</span>
            <button class="users__secondary" :disabled="page >= lastPage || loading" @click="loadUsers(page + 1)">Siguiente</button>
        </nav>
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import api from '@/plugins/axios';
import { can } from '@/router/permissions';
import { useAuthStore } from '@/stores/auth';
import {
    accionesPara, confirmacion, estadoDe, etiquetaRol, iniciales, mensajeDeError, resumen, rolesAsignables,
} from '@/modules/tenants/hospitalUsers';

const auth = useAuthStore();
const users = ref([]);
const total = ref(0);
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
const stats = computed(() => resumen(users.value, total.value));
const actions = (user) => accionesPara(user, auth.user?.user_id, permissions.value);
const isSelf = (user) => Boolean(auth.user?.user_id) && user.user_id === auth.user.user_id;

async function loadUsers(nextPage) {
    loading.value = true;
    errorMessage.value = '';

    try {
        const { data } = await api.get('/admin/users', {
            params: { page: nextPage, per_page: 25, search: search.value || undefined },
        });
        users.value = data.data;
        total.value = data.total;
        page.value = data.current_page;
        lastPage.value = data.last_page;
    } catch (error) {
        errorMessage.value = error.response?.data?.message
            ?? 'No fue posible cargar los usuarios autorizados.';
    } finally {
        loading.value = false;
    }
}

function clearSearch() {
    search.value = '';
    loadUsers(1);
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
/* El preflight de Tailwind quita bordes, fondos y tamaños a campos, botones y títulos; se restauran con los mismos tokens que Médicos y Medicamentos. */
.users { max-width: 1040px; margin: 0 auto; color: #0f172a; }
.users p { margin: 0; }
.users__eyebrow { font-size: 0.8rem; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; color: #1d4ed8; }
.users h1 { font-size: 1.6rem; font-weight: 700; margin: 0.15rem 0 0.25rem; }
.users__subtitle { color: #475569; }
.users__stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.75rem; margin: 1.25rem 0; padding: 0; list-style: none; }
.users__stats li { display: flex; flex-direction: column; padding: 0.85rem 1rem; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; }
.users__stats strong { font-size: 1.5rem; font-weight: 700; }
.users__stats span { color: #475569; font-size: 0.9rem; }
.users__search { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.users input, .users select { min-height: 44px; padding: 0.5rem 0.65rem; border: 1px solid #64748b; border-radius: 6px; background: #fff; font: inherit; }
.users__search input { flex: 1; }
.users button { min-height: 44px; padding: 0.5rem 1rem; border: 1px solid #1d4ed8; border-radius: 6px; background: #1d4ed8; color: #fff; font: inherit; font-weight: 600; cursor: pointer; }
.users button:disabled { opacity: 0.6; cursor: progress; }
.users .users__secondary { background: #fff; color: #1e3a8a; }
.users .users__danger { border-color: #b91c1c; background: #fff; color: #b91c1c; }
.users button:focus-visible, .users input:focus-visible, .users select:focus-visible { outline: 3px solid #2563eb; outline-offset: 2px; }
.users__msg { margin-bottom: 1rem; padding: 0.5rem 0.75rem; border-left: 4px solid #64748b; border-radius: 6px; font-size: 0.9rem; }
.users__msg--ok { background: #dcfce7; color: #14532d; border-left-color: #16a34a; }
.users__msg--error { background: #fee2e2; color: #7f1d1d; border-left-color: #dc2626; }
.users__card { overflow: hidden; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; }
.users table { width: 100%; border-collapse: collapse; }
.users th { padding: 0.65rem 1rem; background: #f1f5f9; color: #334155; font-size: 0.85rem; font-weight: 600; text-align: left; }
.users td { padding: 0.75rem 1rem; border-top: 1px solid #e2e8f0; vertical-align: middle; }
.users__who { display: flex; align-items: center; gap: 0.75rem; }
.users__avatar { display: grid; place-items: center; flex: none; width: 2.5rem; height: 2.5rem; border-radius: 999px; background: #dbeafe; color: #1e3a8a; font-weight: 700; }
.users__name { font-weight: 600; }
.users__email, .users__muted { color: #64748b; font-size: 0.875rem; }
.users__you { margin-left: 0.35rem; padding: 0.05rem 0.45rem; border-radius: 999px; background: #e0e7ff; color: #3730a3; font-size: 0.75rem; }
.users__badge { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
.users__badge--ok { background: #dcfce7; color: #166534; }
.users__badge--off { background: #e2e8f0; color: #475569; }
.users__role { display: inline-flex; align-items: center; gap: 0.25rem; margin: 0 0.35rem 0.35rem 0; padding: 0.15rem 0.3rem 0.15rem 0.6rem; border: 1px solid #bfdbfe; border-radius: 999px; background: #eff6ff; color: #1e3a8a; font-size: 0.85rem; }
/* 24 px: tamaño mínimo del objetivo (WCAG 2.5.8) */
.users .users__remove { min-height: 24px; width: 24px; padding: 0; border: 0; border-radius: 999px; background: transparent; color: #7f1d1d; line-height: 1; }
.users .users__remove:hover { background: #fee2e2; }
.users__actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.users__actions form { display: flex; gap: 0.35rem; }
.users__empty { padding: 2.5rem 1rem; text-align: center; color: #475569; }
.users__empty-title { font-weight: 600; color: #0f172a; }
.users__pagination { display: flex; align-items: center; justify-content: center; gap: 0.75rem; margin-top: 1rem; }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
@media (max-width: 720px) {
    .users__stats { grid-template-columns: 1fr; }
    .users__search { flex-wrap: wrap; }
    .users__search input { flex-basis: 100%; }
    .users thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
    .users tr { display: block; padding: 0.5rem 0; border-top: 1px solid #e2e8f0; }
    .users td { display: block; padding: 0.4rem 1rem; border: 0; }
    .users td[data-label="Estado"]::before, .users td[data-label="Roles"]::before { content: attr(data-label) ': '; color: #64748b; font-size: 0.85rem; }
}
</style>
