<template>
    <button
        :type="type"
        class="btn"
        :class="[`btn--${variant}`, { 'btn--block': block }]"
        :disabled="disabled || loading"
        :aria-busy="loading ? 'true' : 'false'"
        @click="handleClick"
    >
        <span v-if="loading" class="btn__spinner" aria-hidden="true" />
        <span>{{ loading ? loadingText : '' }}<slot v-if="! loading" /></span>
    </button>
</template>

<script setup>
// Botón táctil (mínimo 44 px). Con loading queda deshabilitado y además ignora clics:
// el doble envío también se bloquea si el padre olvida pasar disabled.
const props = defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary', validator: (v) => ['primary', 'secondary', 'danger'].includes(v) },
    loading: { type: Boolean, default: false },
    loadingText: { type: String, default: 'Enviando…' },
    disabled: { type: Boolean, default: false },
    block: { type: Boolean, default: false },
});

const emit = defineEmits(['click']);

function handleClick(event) {
    if (props.loading || props.disabled) {
        event.preventDefault();
        return;
    }
    emit('click', event);
}
</script>

<style scoped>
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    min-height: 44px;
    padding: 0.65rem 1.25rem;
    border: 1px solid transparent;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
}

.btn--primary { background: #2563eb; color: #ffffff; }
.btn--secondary { background: #ffffff; color: #334155; border-color: #cbd5e1; }
.btn--danger { background: #b91c1c; color: #ffffff; }

.btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn:focus-visible {
    outline: 3px solid #93c5fd;
    outline-offset: 2px;
}

.btn--block { width: 100%; }

.btn__spinner {
    width: 1rem;
    height: 1rem;
    border: 2px solid currentColor;
    border-top-color: transparent;
    border-radius: 50%;
    animation: btn-spin 0.8s linear infinite;
}

@keyframes btn-spin {
    to { transform: rotate(360deg); }
}

/* En móvil los botones ocupan todo el ancho para facilitar el toque. */
@media (max-width: 480px) {
    .btn { width: 100%; }
}

@media (prefers-reduced-motion: reduce) {
    .btn__spinner { animation: none; }
}
</style>
