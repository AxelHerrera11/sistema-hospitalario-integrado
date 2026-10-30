<script setup>
defineProps({
    ward: { type: Object, required: true },
    selected: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

defineEmits(['select']);
</script>

<template>
    <button
        class="ward-card"
        :class="{ 'ward-card--selected': selected }"
        type="button"
        :aria-pressed="selected"
        :disabled="disabled"
        @click="$emit('select', ward)"
    >
        <span class="ward-card__title">
            <strong>{{ ward.code }}</strong>
            <span>{{ ward.name }}</span>
        </span>
        <span class="ward-card__detail">
            <span class="ward-card__label">Ubicación</span>
            <span>{{ ward.location }}</span>
        </span>
        <span class="ward-card__detail">
            <span class="ward-card__label">Estado</span>
            <span>{{ ward.status }}</span>
        </span>
        <span class="ward-card__action">Ver camas</span>
    </button>
</template>

<style scoped>
.ward-card {
    display: grid;
    gap: 0.65rem;
    width: 100%;
    min-width: 0;
    padding: 1rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    background: #ffffff;
    color: inherit;
    text-align: left;
    font: inherit;
    overflow-wrap: anywhere;
    cursor: pointer;
}

.ward-card:hover,
.ward-card--selected {
    border-color: #1d4ed8;
    box-shadow: 0 0 0 1px #1d4ed8;
}

.ward-card:disabled {
    cursor: wait;
    opacity: 0.75;
}

.ward-card__title {
    display: grid;
    gap: 0.15rem;
    font-size: 1.05rem;
}

.ward-card__detail {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.ward-card__label {
    font-weight: 600;
}

.ward-card__action {
    color: #1e40af;
    font-weight: 700;
    text-decoration: underline;
    text-underline-offset: 0.15em;
}
</style>
