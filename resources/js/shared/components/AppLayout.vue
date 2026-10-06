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
                    <!-- Cada área agrega su enlace SOLO en su bloque, con v-if="auth.can('<permiso>')". -->
                    <!-- Área 1: Auth, usuarios, RBAC y auditoría -->
                    <!-- Área 2: Pacientes y expediente base -->
                    <!-- Área 3: Médicos, especialidades y citas -->
                    <router-link
                        v-if="auth.can('citas.ver')"
                        class="layout__link"
                        to="/medicos-citas"
                    >
                        Médicos y citas
                    </router-link>
                    <!-- Área 4: Salas, camas, admisión, traslados y altas -->
                    <!-- Área 5: Notas SOAP, diagnósticos y signos vitales -->
                    <!-- Área 6: Alergias, medicamentos y prescripciones -->
                    <!-- Área 7: Laboratorio -->
                    <!-- Área 8: Alertas críticas, dashboard y reportes -->
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
