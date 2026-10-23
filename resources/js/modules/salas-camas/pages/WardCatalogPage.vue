<template>
    <section class="ward-catalog" aria-labelledby="ward-catalog-title">
        <header class="ward-catalog__header">
            <h1 id="ward-catalog-title">CatÃ¡logo de wards y camas</h1>
            <p>Consulta las wards y las camas asociadas en el hospital activo.</p>
        </header>

        <p v-if="!canRead" class="ward-catalog__message ward-catalog__message--error" role="alert">
            Tu sesiÃ³n no tiene permiso para consultar este catÃ¡logo.
        </p>
        <div v-else-if="accessError" class="ward-catalog__message ward-catalog__message--error" role="alert">
            <p>{{ accessError }}</p>
            <button
                class="ward-catalog__button ward-catalog__button--secondary"
                type="button"
                :disabled="wardsLoading"
                @click="loadWards"
            >
                {{ wardsLoading ? 'Verificandoâ€¦' : 'Reintentar acceso' }}
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
                        {{ wardsLoading ? 'Actualizandoâ€¦' : 'Reintentar wards' }}
                    </button>
                    <button
                        v-else
                        class="ward-catalog__button ward-catalog__button--secondary"
                        type="button"
                        :disabled="wardsLoading"
                        @click="loadWards"
                    >
                        {{ wardsLoading ? 'Actualizandoâ€¦' : 'Actualizar' }}
                    </button>
                </div>

                <p v-if="wardsLoading" class="ward-catalog__message" role="status">
                    Consultando wardsâ€¦
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

                <ul v-if="wards.length" class="ward-catalog__list" aria-label="Wards del catÃ¡logo">
                    <li v-for="ward in wards" :key="ward.ward_id">
                        <WardCatalogWardCard
                            :ward="ward"
                            :selected="selectedWard?.ward_id === ward.ward_id"
                            :disabled="wardsLoading"
                            @select="selectWard"
                        />
                    </li>
                </ul>
            </section>

            <section class="ward-catalog__beds" aria-labelledby="beds-title">
                <template v-if="selectedWard">
                    <div class="ward-catalog__section-heading">
                        <div>
                            <h2 id="beds-title">Camas</h2>
                            <p class="ward-catalog__context">
                                {{ selectedWard.code }} Â· {{ selectedWard.name }}
                            </p>
                        </div>
                        <button
                            v-if="bedsError && bedsCanRetry"
                            class="ward-catalog__button ward-catalog__button--secondary"
                            type="button"
                            :disabled="bedsLoading"
                            @click="loadBeds(selectedWard)"
                        >
                            {{ bedsLoading ? 'Consultandoâ€¦' : 'Reintentar camas' }}
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
                        Consultando camasâ€¦
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
                            <WardCatalogBedCard :bed="bed" />
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
import WardCatalogWardCard from '../components/WardCatalogWardCard.vue';
import WardCatalogBedCard from '../components/WardCatalogBedCard.vue';
import './ward-catalog.css';

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

    if (status === 401) return 'Tu sesiÃ³n no es vÃ¡lida. Inicia sesiÃ³n nuevamente.';
    if (status === 403) return 'No tienes permiso para consultar este catÃ¡logo en el hospital activo.';
    if (status === 404 && resource === 'beds') return 'La ward ya no estÃ¡ disponible. Actualiza la lista de wards.';
    if (status === 503 && code === 'WARD_CATALOG_UNAVAILABLE') return 'No se pudo consultar el catÃ¡logo de wards. Intenta nuevamente.';
    if (status === 503 && code === 'BED_CATALOG_UNAVAILABLE') return 'No se pudo consultar el catÃ¡logo de camas. Intenta nuevamente.';
    if (!status) {
        return error.response
            ? 'El catÃ¡logo devolviÃ³ una respuesta no vÃ¡lida. Intenta nuevamente.'
            : 'No se pudo conectar con el catÃ¡logo. Intenta nuevamente.';
    }
    if (status >= 500) return 'No se pudo conectar con el catÃ¡logo. Intenta nuevamente.';

    return 'No se pudo completar la consulta del catÃ¡logo.';
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
        if (!hasCatalogData(response)) throw new Error('Respuesta de wards invÃ¡lida.');

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
        if (!hasCatalogData(response)) throw new Error('Respuesta de camas invÃ¡lida.');
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
