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
        <a
            class="layout__skip"
            href="#contenido"
        >
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
                <router-link
                    v-for="item in visibleItems"
                    :key="item.id"
                    class="layout__link"
                    :to="item.route"
                    @click="closeSidebar"
                >
                    {{ item.label }}
                </router-link>
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
                    <span class="layout__tenant">Tenant: {{ auth.tenantId ?? '—' }}</span>
                    <span class="layout__name">{{ userName }}</span>
                    <button
                        class="layout__logout"
                        type="button"
                        @click="handleLogout"
                    >
                        Cerrar sesión
                    </button>
                </div>
            </header>
            <main
                id="contenido"
                class="layout__main"
            >
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { navigationItems } from '@/shared/navigation';
import { resolvePermissions } from '@/shared/permissions';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const sidebarOpen = ref(false);

const isGuest = computed(() => route.name === 'login');
const userName = computed(() => auth.user?.name ?? 'Usuario');

const visibleItems = computed(() => {
    const permissions = resolvePermissions(auth.user);

    return navigationItems.filter(
        (item) => item.permission === null || permissions.includes(item.permission),
    );
});

watch(() => route.fullPath, () => {
    sidebarOpen.value = false;
});

function closeSidebar() {
    sidebarOpen.value = false;
}

async function handleLogout() {
    await auth.logout();
    sidebarOpen.value = false;
    await router.push({ name: 'login' });
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
    font-weight: 600;
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

.layout__menu:focus-visible,
.layout__link:focus-visible,
.layout__logout:focus-visible {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
}

.layout__session {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    flex-wrap: wrap;
    justify-content: flex-end;
    font-size: 0.9rem;
}

.layout__tenant {
    color: #64748b;
}

.layout__name {
    font-weight: 600;
}

.layout__logout {
    border: 1px solid #cbd5e7;
    border-radius: 8px;
    background: #ffffff;
    padding: 0.4rem 0.8rem;
    cursor: pointer;
}

.layout__nav {
    display: flex;
    flex-direction: column;
    padding: 0.75rem 0.5rem;
    gap: 0.15rem;
}

.layout__link {
    display: block;
    padding: 0.55rem 0.75rem;
    border-radius: 8px;
    color: #0f172a;
    text-decoration: none;
    font-size: 0.95rem;
}

.layout__link:hover {
    background: #f1f5f9;
}

.layout__link.router-link-exact-active {
    background: #eff6ff;
    color: #2563eb;
    font-weight: 600;
}

.layout__main {
    flex: 1;
    padding: 1.5rem;
}

@media (min-width: 768px) {
    .layout {
        flex-direction: row;
    }

    .layout__sidebar {
        position: static;
        transform: none;
        width: 250px;
        flex-shrink: 0;
        transition: none;
    }

    .layout__overlay,
    .layout__menu {
        display: none;
    }

    .layout__brand--sidebar {
        padding-top: 1rem;
    }

    .layout__header {
        padding-top: 0.85rem;
        padding-bottom: 0.85rem;
    }
}
</style>
