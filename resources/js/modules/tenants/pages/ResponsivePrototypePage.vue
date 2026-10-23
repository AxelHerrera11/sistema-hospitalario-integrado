<template>
    <div class="prototipo">
        <OfflineIndicator />
        <header class="prototipo__header">
            <h1>Tenants · prototipo responsive (Semana 10)</h1>
            <p>Formulario de acceso real contra /api/v1/auth, medidor de contraseña y estado de la cola offline.</p>
        </header>
        <div class="prototipo__grid">
            <LoginForm :on-submit="iniciarSesion" :initial-tenant-id="auth.tenantId ?? ''" :error="error" />
            <section class="prototipo__card" aria-labelledby="titulo-clave">
                <h2 id="titulo-clave">
                    Política de contraseñas
                </h2>
                <FormField v-model="clave" name="nuevaClave" type="password" label="Nueva contraseña" autocomplete="new-password" />
                <PasswordStrengthMeter :value="clave" />
            </section>
            <PendingActionsPanel />
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import FormField from '@/modules/tenants/responsive/FormField.vue';
import LoginForm from '@/modules/tenants/responsive/LoginForm.vue';
import PasswordStrengthMeter from '@/modules/tenants/responsive/PasswordStrengthMeter.vue';
import OfflineIndicator from '@/modules/tenants/offline/OfflineIndicator.vue';
import PendingActionsPanel from '@/modules/tenants/offline/PendingActionsPanel.vue';

const router = useRouter();
const auth = useAuthStore();
const error = ref('');
const clave = ref('');

// Mismo flujo que LoginPage: el store llama a /api/v1/auth/login con X-Tenant-ID.
async function iniciarSesion({ tenantId, email, password }) {
    error.value = '';
    try {
        auth.setTenantId(tenantId);
        await auth.login({ email, password });
        await router.push({ name: 'home' });
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'No fue posible iniciar sesión.';
    }
}
</script>

<style scoped>
.prototipo { display: flex; flex-direction: column; gap: 1rem; }
.prototipo__header h1 { margin: 0; font-size: 1.25rem; font-weight: 700; }
.prototipo__header p { margin: 0.25rem 0 0; color: #475569; font-size: 0.9rem; }

.prototipo__grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
    align-items: start;
}

.prototipo__card {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #ffffff;
}

.prototipo__card h2 { margin: 0; font-size: 1rem; font-weight: 700; }

/* Tableta: dos columnas; móvil: una sola columna en orden de uso. */
@media (max-width: 1024px) {
    .prototipo__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 640px) {
    .prototipo__grid { grid-template-columns: minmax(0, 1fr); }
}
</style>
