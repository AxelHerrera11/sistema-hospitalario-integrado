<template>
    <section class="login">
        <h2 class="login__title">
            Iniciar sesión
        </h2>
        <form class="login__form" @submit.prevent="handleSubmit">
            <label class="login__label">
                ID de tenant (X-Tenant-ID)
                <input
                    v-model="tenantId"
                    class="login__input"
                    type="text"
                    required
                    autocomplete="off"
                >
            </label>
            <label class="login__label">
                Correo
                <input
                    v-model="email"
                    class="login__input"
                    type="email"
                    required
                    autocomplete="username"
                >
            </label>
            <label class="login__label">
                Contraseña
                <input
                    v-model="password"
                    class="login__input"
                    type="password"
                    required
                    autocomplete="current-password"
                >
            </label>
            <p v-if="errorMessage" class="login__error">
                {{ errorMessage }}
            </p>
            <button class="login__submit" type="submit" :disabled="loading">
                {{ loading ? 'Enviando…' : 'Entrar' }}
            </button>
        </form>
    </section>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();

const tenantId = ref(auth.tenantId ?? '');
const email = ref('');
const password = ref('');
const loading = ref(false);
const errorMessage = ref('');

async function handleSubmit() {
    errorMessage.value = '';
    loading.value = true;

    try {
        auth.setTenantId(tenantId.value);
        await auth.login({
            email: email.value,
            password: password.value,
        });
        await router.push(route.query.redirect ?? { name: 'home' });
    } catch (error) {
        const message = error?.response?.data?.message
            ?? error?.response?.data?.errors?.email?.[0]
            ?? 'No fue posible iniciar sesión.';
        errorMessage.value = message;
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
.login {
    max-width: 420px;
    margin: 0 auto;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.login__title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.login__form {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.login__label {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    font-size: 0.9rem;
    color: #334155;
}

.login__input {
    border: 1px solid #cbd5f5;
    border-radius: 8px;
    padding: 0.65rem 0.75rem;
    font-size: 1rem;
}

.login__submit {
    margin-top: 0.5rem;
    border: none;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    background: #2563eb;
    color: #ffffff;
    font-weight: 600;
    cursor: pointer;
}

.login__submit:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.login__error {
    color: #b91c1c;
    font-size: 0.9rem;
}
</style>
