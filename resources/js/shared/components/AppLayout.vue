<template>
    <div class="layout">
        <header class="layout__header">
            <div class="layout__brand">
                Hospital HIS
            </div>
            <nav class="layout__nav">
                <router-link class="layout__link" to="/">
                    Inicio
                </router-link>
                <template v-if="auth.token">
                    <span class="layout__user">{{ auth.user?.name }}</span>
                    <button class="layout__link layout__logout" type="button" @click="handleLogout">
                        Cerrar sesión
                    </button>
                </template>
                <router-link v-else class="layout__link" to="/login">
                    Acceso
                </router-link>
            </nav>
        </header>
        <main class="layout__main">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();

async function handleLogout() {
    await auth.logout();
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

.layout__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    background: #ffffff;
}

.layout__brand {
    font-weight: 600;
    letter-spacing: 0.02em;
}

.layout__nav {
    display: flex;
    gap: 1rem;
}

.layout__link {
    color: #0f172a;
    text-decoration: none;
    font-weight: 500;
}

.layout__link.router-link-active {
    color: #2563eb;
}

.layout__main {
    flex: 1;
    padding: 1.5rem;
}
.layout__user {
    color: #475569;
    font-size: 0.9rem;
}

.layout__logout {
    background: none;
    border: none;
    cursor: pointer;
    font: inherit;
}
</style>
