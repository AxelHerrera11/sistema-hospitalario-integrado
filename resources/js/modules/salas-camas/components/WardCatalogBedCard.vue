<script setup>
defineProps({
    bed: { type: Object, required: true },
});
</script>

<template>
    <article class="bed-card">
        <h3>{{ bed.code }}</h3>
        <p class="bed-card__detail">
            <span class="bed-card__label">Tipo</span>
            <span>{{ bed.type }}</span>
        </p>
        <p class="bed-card__detail">
            <span class="bed-card__label">Estado</span>
            <span>{{ bed.status }}</span>
        </p>
    </article>
</template>

<style scoped>
.bed-card {
    min-width: 0;
    padding: 1rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    background: #ffffff;
    color: inherit;
    overflow-wrap: anywhere;
}

.bed-card h3 {
    margin: 0;
    font-size: 1.1rem;
    color: #0f172a;
}

.bed-card__detail {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin: 0.5rem 0 0;
}

.bed-card__label {
    font-weight: 600;
    color: #0f172a;
}
</style>
