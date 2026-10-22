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
                <router-link class="layout__link" to="/login">
                    Acceso
                </router-link>
                <router-link
                    v-for="item in navigation"
                    :key="item.to"
                    class="layout__link"
                    :to="item.to"
                >
                    {{ item.label }}
                </router-link>
                <router-link v-if="auth.token" class="layout__link" to="/traslados">
                    Traslados
                </router-link>
                <button v-if="auth.token" class="layout__link layout__logout" @click="logout">
                    Cerrar sesión
                </button>
            </nav>
        </header>
        <p v-if="auth.hospital" class="layout__tenant">
            Hospital activo: {{ auth.hospital.name }} · {{ auth.hospital.hospital_id }}
        </p>
        <p v-if="auth.notice" class="layout__error" role="alert">
            {{ auth.notice }}
        </p>
        <p v-if="logoutError" class="layout__error" role="alert">
            {{ logoutError }}
        </p>
        <main class="layout__main">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { visibleNavigation } from '@/router/permissions';

const auth = useAuthStore();
const router = useRouter();
const logoutError = ref('');
const navigation = computed(() => visibleNavigation(auth.user));

async function logout() {
    logoutError.value = '';

    try {
        await auth.logout();
    } catch {
        logoutError.value = 'Se cerró la sesión local, pero el servidor no confirmó la revocación.';
    } finally {
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

.layout__logout {
    border: 0;
    padding: 0;
    background: transparent;
    cursor: pointer;
    font: inherit;
}

.layout__tenant {
    margin: 0;
    padding: 0.5rem 1.5rem;
    color: #475569;
    background: #eff6ff;
    font-size: 0.875rem;
}

.layout__error {
    margin: 0;
    padding: 0.5rem 1.5rem;
    color: #b91c1c;
    background: #fef2f2;
}

.layout__main {
    flex: 1;
    padding: 1.5rem;
}

@media (max-width: 600px) {
    .layout__header {
        align-items: flex-start;
        flex-direction: column;
        gap: 0.75rem;
        padding: 1rem;
    }

    .layout__nav {
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .layout__main {
        padding: 1rem;
    }
}
</style>
