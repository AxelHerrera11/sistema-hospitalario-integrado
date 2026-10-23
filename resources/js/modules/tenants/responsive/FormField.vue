<template>
    <div class="field" :class="{ 'field--error': error }">
        <label :for="id" class="field__label">
            {{ label }}<span v-if="required" class="field__required" aria-hidden="true">*</span>
        </label>
        <div class="field__control">
            <input
                :id="id"
                class="field__input"
                :type="inputType"
                :value="modelValue"
                :name="name"
                :required="required"
                :disabled="disabled"
                :autocomplete="autocomplete"
                :inputmode="inputmode"
                :aria-invalid="error ? 'true' : 'false'"
                :aria-describedby="describedBy"
                @input="emit('update:modelValue', $event.target.value)"
                @blur="emit('blur', name)"
            >
            <button
                v-if="type === 'password'"
                type="button"
                class="field__toggle"
                :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                :aria-pressed="visible ? 'true' : 'false'"
                :disabled="disabled"
                @click="visible = ! visible"
            >
                {{ visible ? 'Ocultar' : 'Ver' }}
            </button>
        </div>
        <p v-if="error" :id="`${id}-error`" class="field__error" role="alert">
            {{ error }}
        </p>
        <p v-else-if="hint" :id="`${id}-hint`" class="field__hint">
            {{ hint }}
        </p>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, required: true },
    name: { type: String, required: true },
    type: { type: String, default: 'text' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    autocomplete: { type: String, default: 'off' },
    inputmode: { type: String, default: undefined },
});

const emit = defineEmits(['update:modelValue', 'blur']);

const visible = ref(false);
const id = computed(() => `campo-${props.name}`);
const inputType = computed(() => (props.type === 'password' && visible.value ? 'text' : props.type));
const describedBy = computed(() => {
    if (props.error) return `${id.value}-error`;
    return props.hint ? `${id.value}-hint` : undefined;
});
</script>

<style scoped>
.field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.field__label {
    font-size: 0.9rem;
    font-weight: 500;
    color: #334155;
}

.field__required {
    margin-left: 0.25rem;
    color: #b91c1c;
}

.field__control {
    position: relative;
    display: flex;
}

/* 16 px evita el zoom automático de iOS al enfocar el campo. */
.field__input {
    width: 100%;
    min-height: 44px;
    padding: 0.65rem 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 16px;
}

.field__input:focus-visible {
    outline: 3px solid #93c5fd;
    border-color: #2563eb;
}

.field--error .field__input { border-color: #b91c1c; }

.field__toggle {
    position: absolute;
    top: 0;
    right: 0;
    min-width: 44px;
    min-height: 44px;
    padding: 0 0.75rem;
    border: none;
    background: none;
    color: #2563eb;
    font-weight: 600;
    cursor: pointer;
}

.field__control:has(.field__toggle) .field__input { padding-right: 4.5rem; }

.field__error,
.field__hint {
    margin: 0;
    font-size: 0.8rem;
}

.field__error { color: #b91c1c; }
.field__hint { color: #64748b; }
</style>
