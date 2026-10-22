<template>
    <section class="patients">
        <p class="patients__notice" role="status">Prototipo local · datos ficticios · sin conexión a API ni autorización real.</p>
        <h1>Pacientes</h1>

        <nav v-if="demoMode" class="patients__nav" aria-label="Prototipo de pacientes">
            <RouterLink :to="target('patients')">Buscar</RouterLink>
            <RouterLink v-if="can(auth.user, 'pacientes.registrar')" :to="target('patients-new')">Nuevo paciente</RouterLink>
            <RouterLink :to="target('patients-restricted')">Acceso restringido</RouterLink>
        </nav>

        <template v-if="!demoMode">
            <h2>Acceso protegido</h2>
            <p v-if="!hasPermissions(auth.user)">No se puede verificar un permiso efectivo para Pacientes en la sesión actual.</p>
            <p v-else>La sesión incluye permisos, pero no hay integración funcional de Pacientes conectada.</p>
            <ul class="patients__permissions">
                <li v-for="permission in permissions" :key="permission">
                    {{ permission }}: {{ can(auth.user, permission) ? 'disponible' : 'no disponible' }}
                </li>
            </ul>
            <p>La integración no está conectada. La demostración siguiente usa solo datos estáticos ficticios.</p>
            <button type="button" @click="openDemo">Explorar prototipo local</button>
        </template>

        <template v-else-if="route.name === 'patients'">
            <form class="patients__card" @submit.prevent="searchPatient">
                <label for="patient-code">Buscar por código de demostración</label>
                <input id="patient-code" v-model.trim="search" autocomplete="off" placeholder="Ej. DEMO-001">
                <button type="submit" :disabled="loading">Buscar paciente</button>
                <p v-if="loading" role="status">Buscando en el conjunto ficticio…</p>
                <p v-if="searchMessage" role="status">{{ searchMessage }}</p>
            </form>
            <p class="patients__hint">Único registro disponible: DEMO-001 (ficticio).</p>
        </template>

        <template v-else-if="route.name === 'patients-detail'">
            <article class="patients__card">
                <h2>Paciente encontrado</h2>
                <dl>
                    <dt>Código de demostración</dt><dd>{{ patient.code }}</dd>
                    <dt>Nombre</dt><dd>{{ patient.givenName }} {{ patient.familyName }}</dd>
                    <dt>Fecha de nacimiento</dt><dd>{{ patient.birthDate }}</dd>
                    <dt>Estado</dt><dd>{{ patient.status }}</dd>
                </dl>
                <div class="patients__actions">
                    <button v-if="can(auth.user, 'pacientes.editar')" type="button" @click="go('patients-edit')">Editar datos (demo)</button>
                    <button class="patients__secondary" type="button" @click="go('patients')">Nueva búsqueda</button>
                </div>
            </article>
        </template>

        <template v-else-if="route.name === 'patients-new' || route.name === 'patients-edit'">
            <form class="patients__card" @submit.prevent="save">
                <h2>{{ route.name === 'patients-new' ? 'Registrar paciente ficticio' : 'Editar paciente ficticio' }}</h2>
                <p v-if="formError" class="patients__error" role="alert" tabindex="-1" ref="errorSummary">{{ formError }}</p>
                <div class="patients__form">
                    <label v-if="route.name === 'patients-new'" for="code">Código de demostración *
                        <input id="code" v-model.trim="form.code" :aria-invalid="Boolean(errors.code)" :aria-describedby="errors.code ? 'code-error' : undefined">
                        <small v-if="errors.code" id="code-error" class="patients__error">{{ errors.code }}</small>
                    </label>
                    <label v-if="route.name === 'patients-new'" for="global-id">Global ID ficticio *
                        <input id="global-id" v-model.trim="form.global_id" :aria-invalid="Boolean(errors.global_id)" :aria-describedby="errors.global_id ? 'global-id-error' : undefined">
                        <small v-if="errors.global_id" id="global-id-error" class="patients__error">{{ errors.global_id }}</small>
                    </label>
                    <label for="given-name">Nombres *
                        <input id="given-name" v-model.trim="form.given_name" :aria-invalid="Boolean(errors.given_name)" :aria-describedby="errors.given_name ? 'given-error' : undefined">
                        <small v-if="errors.given_name" id="given-error" class="patients__error">{{ errors.given_name }}</small>
                    </label>
                    <label for="family-name">Apellidos *
                        <input id="family-name" v-model.trim="form.family_name" :aria-invalid="Boolean(errors.family_name)" :aria-describedby="errors.family_name ? 'family-error' : undefined">
                        <small v-if="errors.family_name" id="family-error" class="patients__error">{{ errors.family_name }}</small>
                    </label>
                    <label for="birth-date">Fecha de nacimiento *
                        <input id="birth-date" v-model="form.birth_date" type="date" :aria-invalid="Boolean(errors.birth_date)" :aria-describedby="errors.birth_date ? 'birth-error' : undefined">
                        <small v-if="errors.birth_date" id="birth-error" class="patients__error">{{ errors.birth_date }}</small>
                    </label>
                    <label for="sex">Sexo *
                        <select id="sex" v-model="form.sex"><option value="">Seleccionar</option><option value="female">Femenino</option><option value="male">Masculino</option><option value="other">Otro</option><option value="unknown">Desconocido</option></select>
                    </label>
                    <label for="contact">Contacto ficticio (opcional)
                        <input id="contact" v-model.trim="form.contact" autocomplete="off">
                    </label>
                    <label for="status">Estado
                        <select id="status" v-model="form.status"><option value="active">Activo</option><option value="inactive">Inactivo</option><option value="deceased">Fallecido</option></select>
                    </label>
                </div>
                <div class="patients__actions">
                    <button type="submit">{{ route.name === 'patients-new' ? 'Guardar demostración' : 'Guardar cambios de demostración' }}</button>
                    <button class="patients__secondary" type="button" @click="go('patients')">Cancelar</button>
                </div>
            </form>
        </template>

        <article v-else-if="route.name === 'patients-saved'" class="patients__card">
            <h2>Demostración completada</h2>
            <p role="status">{{ savedMessage }}</p>
            <button type="button" @click="go('patients')">Volver a búsqueda</button>
        </article>

        <article v-else class="patients__card">
            <h2>Acceso restringido</h2>
            <p>No se verificó un permiso efectivo; no se muestran datos ni acciones de Pacientes.</p>
            <button type="button" @click="go('patients')">Volver</button>
        </article>
    </section>
</template>

<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { can, hasPermissions } from '@/router/permissions';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const permissions = ['pacientes.ver', 'pacientes.registrar', 'pacientes.editar'];
const demoMode = computed(() => route.query.demo === '1');
const patient = reactive({ code: 'DEMO-001', globalId: '00000000-0000-4000-8000-000000000001', givenName: 'Alex', familyName: 'Paciente Demo', birthDate: '1990-01-01', sex: 'unknown', contact: '', status: 'active' });
const form = reactive({});
const errors = reactive({});
const search = ref('');
const loading = ref(false);
const searchMessage = ref('');
const formError = ref('');
const savedMessage = ref('');
const errorSummary = ref(null);

function target(name) {
    return { name, query: demoMode.value ? { demo: '1' } : {} };
}
function go(name) { router.push(target(name)); }
function openDemo() { router.push({ name: 'patients', query: { demo: '1' } }); }
async function searchPatient() {
    if (loading.value) return;
    searchMessage.value = '';
    if (!search.value) {
        searchMessage.value = 'Escribe un código para buscar.';
        return;
    }
    loading.value = true;
    await new Promise((resolve) => setTimeout(resolve, 250));
    loading.value = false;
    if (search.value === patient.code) go('patients-detail');
    else searchMessage.value = 'No encontramos un registro ficticio con ese código.';
}
function resetForm(name) {
    Object.keys(errors).forEach((key) => delete errors[key]);
    formError.value = '';
    Object.assign(form, name === 'patients-edit'
        ? { given_name: patient.givenName, family_name: patient.familyName, birth_date: patient.birthDate, sex: patient.sex, contact: patient.contact, status: patient.status }
        : { code: '', global_id: '', given_name: '', family_name: '', birth_date: '', sex: '', contact: '', status: 'active' });
}
watch(() => route.name, (name) => {
    if (name === 'patients-new' || name === 'patients-edit') resetForm(name);
}, { immediate: true });
async function save() {
    Object.keys(errors).forEach((key) => delete errors[key]);
    formError.value = '';
    if (route.name === 'patients-new' && form.code === patient.code) errors.code = 'Ese código ficticio ya está ocupado.';
    const required = ['given_name', 'family_name', 'birth_date', 'sex'];
    if (route.name === 'patients-new') required.push('code', 'global_id');
    for (const key of required) {
        if (!form[key]) errors[key] = 'Completa este campo.';
    }
    if (form.birth_date && form.birth_date > new Date().toISOString().slice(0, 10)) errors.birth_date = 'La fecha no puede ser futura.';
    if (Object.keys(errors).length) {
        formError.value = 'Revisa los campos indicados; tus datos siguen en el formulario.';
        await nextTick();
        document.querySelector('[aria-invalid="true"]')?.focus();
        return;
    }
    if (route.name === 'patients-edit') Object.assign(patient, { givenName: form.given_name, familyName: form.family_name, birthDate: form.birth_date, sex: form.sex, contact: form.contact, status: form.status });
    savedMessage.value = 'Cambios guardados solo en esta demostración local; no se envió ninguna solicitud.';
    go('patients-saved');
}
</script>

<style scoped>
.patients{max-width:900px;margin:0 auto;color:#0f172a}.patients h1{font-size:1.8rem;margin:.5rem 0 1rem}.patients h2{font-size:1.25rem}.patients__notice{padding:.75rem;background:#fff7ed;border:1px solid #c2410c;border-radius:.4rem;color:#7c2d12}.patients__nav,.patients__actions{display:flex;flex-wrap:wrap;gap:.75rem;margin:1rem 0}.patients__nav a{padding:.5rem;color:#1d4ed8}.patients__card{margin:1rem 0;padding:1.25rem;background:white;border:1px solid #cbd5e1;border-radius:.5rem}.patients__card form,.patients__card input,.patients__card select{width:100%}.patients label{display:grid;gap:.35rem;font-weight:600}.patients input,.patients select{min-height:44px;padding:.55rem;border:1px solid #64748b;border-radius:.3rem;font:inherit}.patients button{min-height:44px;padding:.6rem 1rem;border:1px solid #1d4ed8;border-radius:.35rem;background:#1d4ed8;color:white;font:inherit;font-weight:600;cursor:pointer}.patients button:focus-visible,.patients a:focus-visible,.patients input:focus-visible,.patients select:focus-visible{outline:3px solid #f59e0b;outline-offset:2px}.patients__secondary{background:white!important;color:#1e3a8a!important}.patients__form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.patients dl{display:grid;grid-template-columns:minmax(10rem,1fr) 2fr;gap:.5rem}.patients dt{font-weight:600}.patients dd{margin:0;overflow-wrap:anywhere}.patients__error{color:#991b1b}.patients__hint{color:#334155}.patients__permissions{padding-left:1.25rem}@media(max-width:600px){.patients__form{grid-template-columns:1fr}.patients__actions{flex-direction:column}.patients__actions button,.patients form>button{width:100%}.patients dl{grid-template-columns:1fr;gap:.15rem}.patients dd{margin-bottom:.6rem}.patients__card{padding:1rem}}
</style>
