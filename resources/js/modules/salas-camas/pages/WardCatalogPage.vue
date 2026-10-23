<template>
    <section class="ward-catalog" aria-labelledby="ward-catalog-title">
        <header class="ward-catalog__header">
            <h1 id="ward-catalog-title">Catálogo de wards y camas</h1>
            <p>Consulta las wards y las camas asociadas en el hospital activo.</p>
        </header>

        <p v-if="!canRead" class="ward-catalog__message ward-catalog__message--error" role="alert">
            Tu sesión no tiene permiso para consultar este catálogo.
        </p>
        <div v-else-if="accessError" class="ward-catalog__message ward-catalog__message--error" role="alert">
            <p>{{ accessError }}</p>
            <button
                class="ward-catalog__button ward-catalog__button--secondary"
                type="button"
                :disabled="wardsLoading"
                @click="loadWards"
            >
                {{ wardsLoading ? 'Verificando…' : 'Reintentar acceso' }}
            </button>
        </div>

        <div v-else class="ward-catalog__layout">
            <section class="ward-catalog__wards" aria-labelledby="wards-title">
                <div class="ward-catalog__section-heading">
                    <h2 id="wards-title">Wards</h2>
                    <button
                        v-if="wardsError"
                        class="ward-catalog__button ward-catalog__button--secondary"
                        type="button"
                        :disabled="wardsLoading"
                        @click="loadWards"
                    >
                        {{ wardsLoading ? 'Actualizando…' : 'Reintentar wards' }}
                    </button>
                    <button
                        v-else
                        class="ward-catalog__button ward-catalog__button--secondary"
                        type="button"
                        :disabled="wardsLoading"
                        @click="loadWards"
                    >
                        {{ wardsLoading ? 'Actualizando…' : 'Actualizar' }}
                    </button>
                </div>

                <p v-if="wardsLoading" class="ward-catalog__message" role="status">
                    Consultando wards…
                </p>
                <p v-if="wardsError" class="ward-catalog__message ward-catalog__message--error" role="alert">
                    {{ wardsError }}
                </p>
                <p v-if="wardsLoaded && !wardsLoading && !wardsError && wards.length === 0" class="ward-catalog__message" role="status">
                    No hay wards para mostrar.
                </p>
                <p v-if="wardsLoaded && !wardsLoading && !wardsError && wards.length > 0" class="ward-catalog__sr-only" role="status">
                    Se cargaron {{ wards.length }} wards.
                </p>

                <ul v-if="wards.length" class="ward-catalog__list" aria-label="Wards del catálogo">
                    <li v-for="ward in wards" :key="ward.ward_id">
                        <button
                            class="ward-catalog__card ward-catalog__ward"
                            :class="{ 'ward-catalog__ward--selected': selectedWard?.ward_id === ward.ward_id }"
                            type="button"
                            :aria-pressed="selectedWard?.ward_id === ward.ward_id"
                            :disabled="wardsLoading"
                            @click="selectWard(ward)"
                        >
                            <span class="ward-catalog__card-title">
                                <strong>{{ ward.code }}</strong>
                                <span>{{ ward.name }}</span>
                            </span>
                            <span class="ward-catalog__detail">
                                <span class="ward-catalog__label">Ubicación</span>
                                <span>{{ ward.location }}</span>
                            </span>
                            <span class="ward-catalog__detail">
                                <span class="ward-catalog__label">Estado</span>
                                <span>{{ ward.status }}</span>
                            </span>
                            <span class="ward-catalog__action">Ver camas</span>
                        </button>
                    </li>
                </ul>
            </section>

            <section class="ward-catalog__beds" aria-labelledby="beds-title">
                <template v-if="selectedWard">
                    <div class="ward-catalog__section-heading">
                        <div>
                            <h2 id="beds-title">Camas</h2>
                            <p class="ward-catalog__context">
                                {{ selectedWard.code }} · {{ selectedWard.name }}
                            </p>
                        </div>
                        <button
                            v-if="bedsError && bedsCanRetry"
                            class="ward-catalog__button ward-catalog__button--secondary"
                            type="button"
                            :disabled="bedsLoading"
                            @click="loadBeds(selectedWard)"
                        >
                            {{ bedsLoading ? 'Consultando…' : 'Reintentar camas' }}
                        </button>
                        <button
                            v-else-if="bedsError && bedsNotFound"
                            class="ward-catalog__button ward-catalog__button--secondary"
                            type="button"
                            :disabled="wardsLoading"
                            @click="loadWards"
                        >
                            Actualizar wards
                        </button>
                    </div>

                    <p v-if="bedsLoading" class="ward-catalog__message" role="status">
                        Consultando camas…
                    </p>
                    <p v-if="bedsError" class="ward-catalog__message ward-catalog__message--error" role="alert">
                        {{ bedsError }}
                    </p>
                    <p v-if="bedsLoaded && !bedsLoading && !bedsError && beds.length === 0" class="ward-catalog__message" role="status">
                        Esta ward no tiene camas registradas.
                    </p>
                    <p v-if="bedsLoaded && !bedsLoading && !bedsError && beds.length > 0" class="ward-catalog__sr-only" role="status">
                        Se cargaron {{ beds.length }} camas para {{ selectedWard.code }}.
                    </p>

                    <ul v-if="beds.length" class="ward-catalog__list" aria-label="Camas de la ward seleccionada">
                        <li v-for="bed in beds" :key="bed.bed_id">
                            <article class="ward-catalog__card ward-catalog__bed">
                                <h3>{{ bed.code }}</h3>
                                <p class="ward-catalog__detail">
                                    <span class="ward-catalog__label">Tipo</span>
                                    <span>{{ bed.type }}</span>
                                </p>
                                <p class="ward-catalog__detail">
                                    <span class="ward-catalog__label">Estado</span>
                                    <span>{{ bed.status }}</span>
                                </p>
                            </article>
                        </li>
                    </ul>
                </template>
                <p v-else class="ward-catalog__message" role="status">
                    Selecciona una ward para consultar sus camas.
                </p>
            </section>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '@/plugins/axios';
import { can } from '@/router/permissions';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const canRead = computed(() => can(auth.user, 'camas.ver'));
const wards = ref([]);
const beds = ref([]);
const selectedWard = ref(null);
const accessError = ref('');
const wardsLoading = ref(false);
const wardsLoaded = ref(false);
const wardsError = ref('');
const bedsLoading = ref(false);
const bedsLoaded = ref(false);
const bedsError = ref('');
const bedsCanRetry = ref(false);
const bedsNotFound = ref(false);
let bedsRequest = 0;

function failureMessage(error, resource) {
    const status = error.response?.status;
    const code = error.response?.data?.error?.code;

    if (status === 401) return 'Tu sesión no es válida. Inicia sesión nuevamente.';
    if (status === 403) return 'No tienes permiso para consultar este catálogo en el hospital activo.';
    if (status === 404 && resource === 'beds') return 'La ward ya no está disponible. Actualiza la lista de wards.';
    if (status === 503 && code === 'WARD_CATALOG_UNAVAILABLE') return 'No se pudo consultar el catálogo de wards. Intenta nuevamente.';
    if (status === 503 && code === 'BED_CATALOG_UNAVAILABLE') return 'No se pudo consultar el catálogo de camas. Intenta nuevamente.';
    if (!status) {
        return error.response
            ? 'El catálogo devolvió una respuesta no válida. Intenta nuevamente.'
            : 'No se pudo conectar con el catálogo. Intenta nuevamente.';
    }
    if (status >= 500) return 'No se pudo conectar con el catálogo. Intenta nuevamente.';

    return 'No se pudo completar la consulta del catálogo.';
}

function restrictAccess(error) {
    accessError.value = failureMessage(error, 'wards');
    wards.value = [];
    wardsLoaded.value = false;
    selectedWard.value = null;
    beds.value = [];
    bedsLoaded.value = false;
    bedsLoading.value = false;
    bedsRequest += 1;
}

function hasCatalogData(response) {
    return Array.isArray(response.data?.data);
}

async function loadWards() {
    if (wardsLoading.value) return;
    wardsLoading.value = true;
    accessError.value = '';
    wardsError.value = '';

    try {
        const response = await api.get('/wards');
        if (!hasCatalogData(response)) throw new Error('Respuesta de wards inválida.');

        wards.value = response.data.data;
        wardsLoaded.value = true;
        if (selectedWard.value) {
            selectedWard.value = wards.value.find((ward) => ward.ward_id === selectedWard.value.ward_id) ?? null;
            if (!selectedWard.value) {
                beds.value = [];
                bedsLoaded.value = false;
                bedsError.value = '';
                bedsLoading.value = false;
                bedsRequest += 1;
            }
        }
    } catch (error) {
        if ([401, 403].includes(error.response?.status)) {
            restrictAccess(error);
        } else {
            wardsError.value = failureMessage(error, 'wards');
        }
    } finally {
        wardsLoading.value = false;
    }
}

async function selectWard(ward) {
    selectedWard.value = ward;
    await loadBeds(ward);
}

async function loadBeds(ward) {
    const request = ++bedsRequest;
    beds.value = [];
    bedsLoaded.value = false;
    bedsError.value = '';
    bedsCanRetry.value = false;
    bedsNotFound.value = false;
    bedsLoading.value = true;

    try {
        const response = await api.get(`/wards/${encodeURIComponent(ward.ward_id)}/beds`);
        if (!hasCatalogData(response)) throw new Error('Respuesta de camas inválida.');
        if (request !== bedsRequest || selectedWard.value?.ward_id !== ward.ward_id) return;

        beds.value = response.data.data;
        bedsLoaded.value = true;
    } catch (error) {
        if (request !== bedsRequest || selectedWard.value?.ward_id !== ward.ward_id) return;

        if ([401, 403].includes(error.response?.status)) {
            restrictAccess(error);
            return;
        }

        bedsError.value = failureMessage(error, 'beds');
        bedsNotFound.value = error.response?.status === 404;
        bedsCanRetry.value = !bedsNotFound.value
            && ![401, 403].includes(error.response?.status)
            && (!error.response?.status || error.response.status >= 500);
    } finally {
        if (request === bedsRequest) bedsLoading.value = false;
    }
}

onMounted(() => {
    if (canRead.value) loadWards();
});
</script>

<style scoped>
.ward-catalog {
    max-width: 1100px;
    margin: 0 auto;
    color: #0f172a;
}

.ward-catalog__header {
    margin-bottom: 1.25rem;
}

.ward-catalog h1 {
    margin: 0 0 0.35rem;
    font-size: 1.8rem;
}

.ward-catalog h2 {
    margin: 0;
    font-size: 1.25rem;
}

.ward-catalog h3 {
    margin: 0;
    font-size: 1.1rem;
}

.ward-catalog__header p,
.ward-catalog__context {
    margin: 0.25rem 0 0;
    color: #475569;
}

.ward-catalog__layout {
    display: grid;
    grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
    align-items: start;
    gap: 1.25rem;
}

.ward-catalog__wards,
.ward-catalog__beds {
    min-width: 0;
}

.ward-catalog__section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.ward-catalog__list {
    display: grid;
    gap: 0.75rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.ward-catalog__card {
    width: 100%;
    min-width: 0;
    padding: 1rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    background: #ffffff;
    color: inherit;
    text-align: left;
    overflow-wrap: anywhere;
}

.ward-catalog__ward {
    display: grid;
    gap: 0.65rem;
    cursor: pointer;
    font: inherit;
}

.ward-catalog__ward:hover,
.ward-catalog__ward--selected {
    border-color: #1d4ed8;
    box-shadow: 0 0 0 1px #1d4ed8;
}

.ward-catalog__ward:disabled {
    cursor: wait;
    opacity: 0.75;
}

.ward-catalog__card-title {
    display: grid;
    gap: 0.15rem;
    font-size: 1.05rem;
}

.ward-catalog__detail {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin: 0;
}

.ward-catalog__label {
    font-weight: 600;
}

.ward-catalog__action {
    color: #1e40af;
    font-weight: 700;
    text-decoration: underline;
    text-underline-offset: 0.15em;
}

.ward-catalog__button {
    min-height: 44px;
    padding: 0.55rem 0.8rem;
    border: 1px solid #1d4ed8;
    border-radius: 0.35rem;
    background: #1d4ed8;
    color: #ffffff;
    font: inherit;
    font-weight: 600;
    cursor: pointer;
}

.ward-catalog__button--secondary {
    background: #ffffff;
    color: #1e3a8a;
}

.ward-catalog button:focus-visible {
    outline: 3px solid #f59e0b;
    outline-offset: 3px;
}

.ward-catalog__message {
    margin: 0.75rem 0;
    padding: 0.75rem;
    border-radius: 0.35rem;
    background: #f1f5f9;
    overflow-wrap: anywhere;
}

.ward-catalog__message--error {
    border: 1px solid #b91c1c;
    background: #fef2f2;
    color: #7f1d1d;
}

.ward-catalog__sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

@media (max-width: 700px) {
    .ward-catalog__layout {
        grid-template-columns: minmax(0, 1fr);
    }

    .ward-catalog__section-heading {
        align-items: flex-start;
        flex-wrap: wrap;
    }
}

@media (max-width: 380px) {
    .ward-catalog__section-heading {
        flex-direction: column;
    }

    .ward-catalog__button {
        width: 100%;
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        scroll-behavior: auto !important;
    }
}
</style>
