<template>
    <div class="meter" :aria-label="`Fortaleza de la contraseña: ${fuerza.nivel}`" role="group">
        <div class="meter__bar" aria-hidden="true">
            <span class="meter__fill" :class="`meter__fill--${fuerza.puntaje}`" :style="{ width: `${fuerza.porcentaje}%` }" />
        </div>
        <p class="meter__level" aria-live="polite">
            {{ fuerza.nivel }} · {{ largo }}/12
        </p>
        <ul class="meter__checks">
            <li v-for="check in fuerza.checks" :key="check.clave" :class="{ 'meter__check--ok': check.ok }">
                <span aria-hidden="true">{{ check.ok ? '✓' : '✗' }}</span> {{ check.etiqueta }}
                <span class="sr-only">{{ check.ok ? 'cumplido' : 'pendiente' }}</span>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { passwordStrength } from './formRules';

const props = defineProps({
    value: { type: String, default: '' },
});

const fuerza = computed(() => passwordStrength(props.value));
const largo = computed(() => props.value.length);
</script>

<style scoped>
.meter__bar {
    height: 6px;
    overflow: hidden;
    border-radius: 3px;
    background: #e2e8f0;
}

.meter__fill {
    display: block;
    height: 100%;
    background: #b91c1c;
    transition: width 0.3s ease;
}

.meter__fill--3 { background: #b45309; }
.meter__fill--4,
.meter__fill--5 { background: #15803d; }

.meter__level {
    margin: 0.4rem 0;
    font-size: 0.8rem;
    font-weight: 600;
    color: #334155;
}

.meter__checks {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.4rem;
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: 0.75rem;
    color: #64748b;
}

.meter__checks li {
    padding: 0.35rem;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    text-align: center;
}

.meter__check--ok {
    border-color: #86efac;
    color: #15803d;
}

/* En pantallas angostas los requisitos pasan a dos columnas para no comprimirse. */
@media (max-width: 360px) {
    .meter__checks { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
}

@media (prefers-reduced-motion: reduce) {
    .meter__fill { transition: none; }
}
</style>
