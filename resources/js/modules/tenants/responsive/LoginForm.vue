<template>
    <form class="login-form" novalidate @submit.prevent="enviar">
        <h2 class="login-form__title">
            Iniciar sesión
        </h2>
        <p v-if="! online" class="login-form__offline" role="status">
            Sin conexión: el inicio de sesión requiere red y no se guarda para después.
        </p>
        <p v-if="error" class="login-form__error" role="alert">
            {{ error }}
        </p>
        <FormField
            v-model="form.tenantId"
            name="tenantId"
            label="ID del hospital (X-Tenant-ID)"
            required
            :error="errores.tenantId"
            hint="Lo entrega la administración del hospital"
            @blur="validarCampo"
        />
        <FormField
            v-model="form.email"
            name="email"
            type="email"
            label="Correo"
            required
            autocomplete="username"
            inputmode="email"
            :error="errores.email"
            @blur="validarCampo"
        />
        <FormField
            v-model="form.password"
            name="password"
            type="password"
            label="Contraseña"
            required
            autocomplete="current-password"
            :error="errores.password"
            @blur="validarCampo"
        />
        <BaseButton type="submit" block :loading="loading" :disabled="! online" loading-text="Verificando…">
            Entrar
        </BaseButton>
    </form>
</template>

<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import BaseButton from './BaseButton.vue';
import FormField from './FormField.vue';
import { createSubmitGuard, validateLogin } from './formRules';

const props = defineProps({
    // Debe devolver una promesa; el formulario no llama a la API por sí mismo.
    onSubmit: { type: Function, required: true },
    initialTenantId: { type: String, default: '' },
    error: { type: String, default: '' },
});

const form = reactive({ tenantId: props.initialTenantId, email: '', password: '' });
const errores = reactive({});
const loading = ref(false);
const online = ref(typeof navigator === 'undefined' ? true : navigator.onLine);
const guard = createSubmitGuard();

function validarCampo(campo) {
    errores[campo] = validateLogin(form)[campo] ?? '';
}

function enviar() {
    const encontrados = validateLogin(form);
    Object.keys(form).forEach((campo) => { errores[campo] = encontrados[campo] ?? ''; });
    if (Object.keys(encontrados).length || ! online.value) return;

    // Enter repetido o doble toque mientras se verifica: se ignora (createSubmitGuard).
    return guard.run(async () => {
        loading.value = true;
        try {
            await props.onSubmit({ tenantId: form.tenantId.trim(), email: form.email.trim(), password: form.password });
        } finally {
            loading.value = false;
            form.password = '';
        }
    }).catch(() => {});
}

const actualizarConexion = () => { online.value = navigator.onLine; };
onMounted(() => {
    window.addEventListener('online', actualizarConexion);
    window.addEventListener('offline', actualizarConexion);
});
onBeforeUnmount(() => {
    window.removeEventListener('online', actualizarConexion);
    window.removeEventListener('offline', actualizarConexion);
});
</script>

<style scoped>
.login-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    width: 100%;
    max-width: 420px;
    margin: 0 auto;
    padding: 1.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.login-form__title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
}

.login-form__offline,
.login-form__error {
    margin: 0;
    padding: 0.6rem 0.75rem;
    border-radius: 8px;
    font-size: 0.9rem;
}

.login-form__offline { background: #fef3c7; color: #92400e; }
.login-form__error { background: #fee2e2; color: #b91c1c; }

/* En móvil el formulario usa todo el ancho y se elimina el marco de tarjeta. */
@media (max-width: 480px) {
    .login-form {
        padding: 1rem;
        border: none;
        box-shadow: none;
    }
}
</style>
