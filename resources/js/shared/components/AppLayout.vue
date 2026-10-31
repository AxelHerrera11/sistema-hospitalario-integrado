<template>
    <div v-if="isGuest" class="layout layout--guest">
        <header class="layout__topbar">
            <span class="layout__brand">Hospital HIS</span>
        </header>
        <main class="layout__main">
            <slot />
        </main>
    </div>
    <div v-else class="layout">
        <a class="layout__skip" href="#contenido">
            Saltar al contenido
        </a>
        <div
            v-if="sidebarOpen"
            class="layout__overlay"
            @click="sidebarOpen = false"
        />
        <aside class="layout__sidebar" :class="{ 'layout__sidebar--open': sidebarOpen }">
            <span class="layout__brand layout__brand--sidebar">Hospital HIS</span>
            <nav
                id="menu-principal"
                class="layout__nav"
                aria-label="Navegación principal"
            >
                <template v-for="item in navigation" :key="item.label">
                    <details v-if="item.children" class="layout__group" open>
                        <summary class="layout__group-title">{{ item.label }}</summary>
                        <div class="layout__group-links">
                            <router-link
                                v-for="child in item.children"
                                :key="child.label"
                                class="layout__link layout__link--child"
                                :to="child.to"
                                @click="closeSidebar"
                            >
                                {{ child.label }}
                            </router-link>
                        </div>
                    </details>
                    <router-link
                        v-else-if="item.to"
                        class="layout__link"
                        :to="item.to"
                        @click="closeSidebar"
                    >
                        {{ item.label }}
                    </router-link>
                    <span
                        v-else
                        class="layout__link layout__link--soon"
                        aria-disabled="true"
                    >
                        {{ item.label }} <small>(próximamente)</small>
                    </span>
                </template>
            </nav>
        </aside>
        <div class="layout__body">
            <header class="layout__header">
                <button
                    class="layout__menu"
                    type="button"
                    :aria-expanded="String(sidebarOpen)"
                    aria-controls="menu-principal"
                    aria-label="Abrir o cerrar el menú de navegación"
                    @click="sidebarOpen = !sidebarOpen"
                >
                    Menú
                </button>
                <div class="layout__session">
                    <span v-if="auth.hospital" class="layout__tenant">
                        Hospital activo: {{ auth.hospital.name }} · {{ auth.hospital.hospital_id }}
                    </span>
                    <span class="layout__name">{{ userName }}</span>
                    <button class="layout__logout" type="button" @click="logout">
                        Cerrar sesión
                    </button>
                </div>
            </header>
            <p v-if="auth.notice" class="layout__error" role="alert">
                {{ auth.notice }}
            </p>
            <p v-if="logoutError" class="layout__error" role="alert">
                {{ logoutError }}
            </p>
            <main id="contenido" class="layout__main">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { visibleNavigation } from '@/router/permissions';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const sidebarOpen = ref(false);
const logoutError = ref('');

watch(() => auth.token, (token) => {
    if (token) {
        logoutError.value = '';
    }
});

const isGuest = computed(() => route.name === 'login');
const userName = computed(() => auth.user?.display_name ?? 'Usuario');

/* Gating por permisos efectivos: los permisos llegan de la API y el backend
 * sigue siendo la autoridad final. */
const navigation = computed(() => visibleNavigation(auth.user));

watch(() => route.fullPath, () => {
    sidebarOpen.value = false;
});

function closeSidebar() {
    sidebarOpen.value = false;
}

async function logout() {
    logoutError.value = '';

    try {
        await auth.logout();
    } catch {
        logoutError.value = 'Se cerró la sesión local, pero el servidor no confirmó la revocación.';
    } finally {
        sidebarOpen.value = false;
        await router.push({ name: 'login' });
    }
}
</script>

<style scoped>
.layout {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: #f8fafc;
    color: #0f172a;
}

.layout--guest {
    background: #ffffff;
}

.layout__topbar {
    display: flex;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.layout__skip {
    position: absolute;
    left: -9999px;
    padding: 0.5rem 1rem;
    background: #2563eb;
    color: #ffffff;
    z-index: 30;
}

.layout__skip:focus {
    left: 0.5rem;
    top: 0.5rem;
}

.layout__brand {
    font-weight: 700;
    letter-spacing: 0.02em;
}

.layout__brand--sidebar {
    display: block;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
}

.layout__sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    width: 250px;
    background: #ffffff;
    border-right: 1px solid #e2e8f0;
    transform: translateX(-100%);
    transition: transform 0.2s ease;
    z-index: 20;
    overflow-y: auto;
}

.layout__sidebar--open {
    transform: translateX(0);
}

.layout__overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 15;
}

.layout__body {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.layout__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.75rem 1.25rem;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
}

.layout__menu {
    border: 1px solid #cbd5e7;
    border-radius: 8px;
    background: #ffffff;
    padding: 0.45rem 0.85rem;
    font-weight: 600;
    cursor: pointer;
}

.layout__session {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
}

.layout__name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.layout__tenant {
    font-size: 0.85rem;
    color: #475569;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.layout__nav {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 0.5rem 0.75rem;
}

.layout__link {
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    color: #1e293b;
    text-decoration: none;
}

.layout__link:hover {
    background: #eef2ff;
}

.layout__group {
    margin: 0.15rem 0;
}

.layout__group-title {
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    color: #1e293b;
    font-weight: 700;
    cursor: pointer;
    list-style: none;
}

.layout__group-title::-webkit-details-marker {
    display: none;
}

.layout__group-title::before {
    content: '▸';
    display: inline-block;
    width: 1rem;
}

.layout__group[open] .layout__group-title::before {
    content: '▾';
}

.layout__group-title:hover {
    background: #eef2ff;
}

.layout__group-links {
    display: grid;
    gap: 2px;
    padding-left: 0.75rem;
}

.layout__link--child {
    padding-left: 1.5rem;
}

.layout__link--soon {
    color: #94a3b8;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.layout__link--soon small {
    font-size: 0.7rem;
}
.layout__logout {
    border: 1px solid #cbd5e7;
    border-radius: 8px;
    background: #ffffff;
    padding: 0.4rem 0.8rem;
    font-weight: 600;
    cursor: pointer;
    color: #b91c1c;
}

.layout__main {
    flex: 1;
    margin: 0;
    padding: 1.5rem;
}

.layout__error {
    margin: 0 1.5rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #b91c1c;
}

@media (min-width: 1024px) {
    .layout__menu {
        display: none;
    }

    .layout__sidebar {
        transform: translateX(0);
    }

    .layout__body {
        margin-left: 250px;
    }
}
</style>
