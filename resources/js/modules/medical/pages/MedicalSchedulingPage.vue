<template>
    <section class="medical-module">
        <header class="medical-module__hero">
            <div>
                <p class="medical-module__breadcrumb">
                    Operativo clinico / Medicos, especialidades y citas
                </p>
                <h1>Gestion de medicos, especialidades y citas</h1>
                <p>
                    Agenda ambulatoria, catalogo medico y especialidades clinicas del hospital activo.
                </p>
            </div>
            <div class="medical-module__hero-actions">
                <button class="medical-module__button medical-module__button--quiet" type="button" :disabled="loading" @click="loadAll">
                    {{ loading ? 'Actualizando' : 'Actualizar' }}
                </button>
                <button
                    v-if="activeTab === 'appointments' && auth.can('citas.crear')"
                    class="medical-module__button"
                    type="button"
                    @click="openAppointmentDrawer()"
                >
                    Nueva cita
                </button>
                <button
                    v-if="activeTab === 'doctors' && auth.can('medicos.gestionar')"
                    class="medical-module__button"
                    type="button"
                    @click="openDoctorDrawer()"
                >
                    Nuevo medico
                </button>
                <button
                    v-if="activeTab === 'specialties' && auth.can('medicos.gestionar')"
                    class="medical-module__button"
                    type="button"
                    @click="openSpecialtyDrawer()"
                >
                    Nueva especialidad
                </button>
            </div>
        </header>

        <div v-if="message" class="medical-module__notice medical-module__notice--success">
            {{ message }}
        </div>
        <div v-if="errorMessage" class="medical-module__notice medical-module__notice--error">
            {{ errorMessage }}
        </div>

        <nav class="medical-module__tabs" aria-label="Areas del modulo">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                class="medical-module__tab"
                :class="{ 'medical-module__tab--active': activeTab === tab.key }"
                type="button"
                @click="activeTab = tab.key"
            >
                <span>{{ tab.label }}</span>
                <strong>{{ tab.count }}</strong>
            </button>
        </nav>

        <section v-if="activeTab === 'appointments'" class="medical-module__workspace">
            <div class="medical-module__kpis" aria-label="Resumen de citas">
                <article v-for="item in appointmentKpis" :key="item.key" class="medical-module__kpi">
                    <span>{{ item.label }}</span>
                    <strong>{{ item.value }}</strong>
                    <small>{{ item.hint }}</small>
                </article>
            </div>

            <div class="medical-module__panel medical-module__panel--filters">
                <div class="medical-module__filter-group">
                    <label>
                        Fecha
                        <input v-model="appointmentFilters.date" type="date">
                    </label>
                    <label>
                        Paciente o motivo
                        <input v-model="appointmentFilters.search" type="search" placeholder="Buscar en agenda">
                    </label>
                    <label>
                        Medico
                        <select v-model="appointmentFilters.doctor_id">
                            <option value="">Todos los medicos</option>
                            <option v-for="doctor in doctors" :key="doctor.id" :value="String(doctor.id)">
                                {{ doctorName(doctor) }}
                            </option>
                        </select>
                    </label>
                    <label>
                        Especialidad
                        <select v-model="appointmentFilters.specialty_id">
                            <option value="">Todas</option>
                            <option v-for="specialty in specialties" :key="specialty.id" :value="String(specialty.id)">
                                {{ specialty.name }}
                            </option>
                        </select>
                    </label>
                    <label>
                        Estado
                        <select v-model="appointmentFilters.status">
                            <option value="">Todos</option>
                            <option v-for="status in statuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="medical-module__grid medical-module__grid--agenda">
                <article class="medical-module__panel medical-module__panel--table">
                    <div class="medical-module__panel-heading">
                        <div>
                            <h2>Agenda de citas</h2>
                            <p>{{ filteredAppointments.length }} citas visibles, ordenadas por fecha y hora.</p>
                        </div>
                    </div>

                    <div class="medical-module__table-wrap">
                        <p v-if="loading" class="medical-module__empty">Cargando agenda de citas.</p>
                        <table v-else-if="filteredAppointments.length" class="medical-module__table">
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Paciente</th>
                                    <th>Medico</th>
                                    <th>Especialidad</th>
                                    <th>Motivo</th>
                                    <th>Duracion</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="appointment in filteredAppointments" :key="appointment.id">
                                    <td>
                                        <strong>{{ formatTime(appointment.scheduled_at) }}</strong>
                                        <span>{{ formatDate(appointment.scheduled_at) }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ patientName(appointment) }}</strong>
                                        <span>{{ appointment.patient?.code || `Paciente #${appointment.patient_id}` }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ doctorName(appointment.doctor) }}</strong>
                                        <span>{{ appointment.doctor?.license_number || 'Sin colegiado' }}</span>
                                    </td>
                                    <td>{{ appointment.specialty?.name || 'Sin especialidad' }}</td>
                                    <td>{{ appointment.reason || 'Sin motivo registrado' }}</td>
                                    <td>{{ appointment.duration_min }} min</td>
                                    <td>
                                        <span class="medical-module__badge" :class="statusClass(appointment.status)">
                                            {{ statusLabel(appointment.status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="medical-module__row-actions">
                                            <button
                                                v-if="auth.can('citas.editar')"
                                                class="medical-module__link-button"
                                                type="button"
                                                @click="openAppointmentDrawer(appointment)"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                v-if="auth.can('citas.editar') && appointment.status !== 'confirmada'"
                                                class="medical-module__link-button"
                                                type="button"
                                                @click="changeAppointmentStatus(appointment, 'confirmada')"
                                            >
                                                Confirmar
                                            </button>
                                            <button
                                                v-if="auth.can('citas.editar') && appointment.status !== 'completada'"
                                                class="medical-module__link-button"
                                                type="button"
                                                @click="changeAppointmentStatus(appointment, 'completada')"
                                            >
                                                Completar
                                            </button>
                                            <button
                                                v-if="auth.can('citas.cancelar') && appointment.status !== 'cancelada'"
                                                class="medical-module__link-button medical-module__link-button--danger"
                                                type="button"
                                                @click="cancelAppointment(appointment)"
                                            >
                                                Cancelar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="medical-module__empty">No hay citas que coincidan con los filtros.</p>
                    </div>
                </article>

                <aside class="medical-module__panel medical-module__side-panel">
                    <div class="medical-module__panel-heading">
                        <div>
                            <h2>Disponibilidad medica</h2>
                            <p>Referencia rapida segun catalogos actuales.</p>
                        </div>
                    </div>
                    <ul class="medical-module__availability">
                        <li v-for="doctor in doctors.slice(0, 5)" :key="doctor.id">
                            <span>
                                <strong>{{ doctorName(doctor) }}</strong>
                                <small>{{ doctor.specialty?.name || 'Sin especialidad' }}</small>
                            </span>
                            <em>{{ doctorAppointmentCount(doctor.id) }} citas</em>
                        </li>
                    </ul>
                    <button class="medical-module__button medical-module__button--quiet medical-module__button--block" type="button" @click="activeTab = 'doctors'">
                        Ver catalogo medico
                    </button>
                </aside>
            </div>
        </section>

        <section v-if="activeTab === 'doctors'" class="medical-module__workspace">
            <div class="medical-module__panel medical-module__panel--filters">
                <div class="medical-module__filter-group medical-module__filter-group--compact">
                    <label>
                        Buscar medico
                        <input v-model="doctorFilters.search" type="search" placeholder="Nombre, email o colegiado">
                    </label>
                    <label>
                        Especialidad
                        <select v-model="doctorFilters.specialty_id">
                            <option value="">Todas</option>
                            <option v-for="specialty in specialties" :key="specialty.id" :value="String(specialty.id)">
                                {{ specialty.name }}
                            </option>
                        </select>
                    </label>
                </div>
            </div>

            <article class="medical-module__panel medical-module__panel--table">
                <div class="medical-module__panel-heading">
                    <div>
                        <h2>Catalogo de medicos</h2>
                        <p>{{ filteredDoctors.length }} perfiles asociados a usuarios del hospital.</p>
                    </div>
                </div>
                <div class="medical-module__table-wrap">
                    <p v-if="loading" class="medical-module__empty">Cargando medicos.</p>
                    <table v-else-if="filteredDoctors.length" class="medical-module__table">
                        <thead>
                            <tr>
                                <th>Medico</th>
                                <th>Email</th>
                                <th>Especialidad</th>
                                <th>Colegiado</th>
                                <th>Telefono</th>
                                <th>Citas</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="doctor in filteredDoctors" :key="doctor.id">
                                <td><strong>{{ doctorName(doctor) }}</strong></td>
                                <td>{{ doctor.user?.email || 'Sin email' }}</td>
                                <td>{{ doctor.specialty?.name || 'Sin especialidad' }}</td>
                                <td>{{ doctor.license_number }}</td>
                                <td>{{ doctor.phone || 'Sin telefono' }}</td>
                                <td>{{ doctorAppointmentCount(doctor.id) }}</td>
                                <td><span class="medical-module__badge medical-module__badge--success">Activo</span></td>
                                <td>
                                    <button
                                        v-if="auth.can('medicos.gestionar')"
                                        class="medical-module__link-button"
                                        type="button"
                                        @click="openDoctorDrawer(doctor)"
                                    >
                                        Editar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="medical-module__empty">No hay medicos que coincidan con los filtros.</p>
                </div>
            </article>
        </section>

        <section v-if="activeTab === 'specialties'" class="medical-module__workspace">
            <div class="medical-module__panel medical-module__panel--filters">
                <div class="medical-module__filter-group medical-module__filter-group--compact">
                    <label>
                        Buscar especialidad
                        <input v-model="specialtyFilters.search" type="search" placeholder="Nombre o descripcion">
                    </label>
                </div>
            </div>

            <div class="medical-module__specialty-grid">
                <article v-for="specialty in filteredSpecialties" :key="specialty.id" class="medical-module__specialty-card">
                    <div>
                        <h2>{{ specialty.name }}</h2>
                        <p>{{ specialty.description || 'Sin descripcion registrada.' }}</p>
                    </div>
                    <dl>
                        <div>
                            <dt>Medicos</dt>
                            <dd>{{ specialtyDoctorCount(specialty.id) }}</dd>
                        </div>
                        <div>
                            <dt>Citas activas</dt>
                            <dd>{{ specialtyAppointmentCount(specialty.id) }}</dd>
                        </div>
                    </dl>
                    <div class="medical-module__card-actions">
                        <button type="button" class="medical-module__link-button" @click="filterAgendaBySpecialty(specialty)">
                            Ver agenda
                        </button>
                        <button
                            v-if="auth.can('medicos.gestionar')"
                            type="button"
                            class="medical-module__link-button"
                            @click="openSpecialtyDrawer(specialty)"
                        >
                            Editar
                        </button>
                    </div>
                </article>
                <p v-if="! loading && ! filteredSpecialties.length" class="medical-module__empty">
                    No hay especialidades que coincidan con la busqueda.
                </p>
            </div>
        </section>

        <aside v-if="drawer.open" class="medical-module__drawer-backdrop" @click.self="closeDrawer">
            <section class="medical-module__drawer" aria-label="Formulario del modulo">
                <header>
                    <div>
                        <span>{{ drawerLabel }}</span>
                        <h2>{{ drawerTitle }}</h2>
                    </div>
                    <button class="medical-module__icon-button" type="button" @click="closeDrawer">
                        X
                    </button>
                </header>

                <form v-if="drawer.type === 'appointment'" class="medical-module__drawer-form" @submit.prevent="saveAppointment">
                    <label>
                        ID paciente
                        <input v-model.number="appointmentForm.patient_id" type="number" min="1" required>
                    </label>
                    <label>
                        Especialidad
                        <select v-model.number="appointmentForm.specialty_id" required>
                            <option disabled value="">Selecciona especialidad</option>
                            <option v-for="specialty in specialties" :key="specialty.id" :value="specialty.id">
                                {{ specialty.name }}
                            </option>
                        </select>
                    </label>
                    <label>
                        Medico
                        <select v-model.number="appointmentForm.doctor_id" required @change="syncDoctorSpecialty">
                            <option disabled value="">Selecciona medico</option>
                            <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                                {{ doctorName(doctor) }} - {{ doctor.specialty?.name }}
                            </option>
                        </select>
                    </label>
                    <p v-if="doctorSpecialtyMismatch" class="medical-module__validation">
                        La especialidad seleccionada no coincide con la especialidad del medico.
                    </p>
                    <label>
                        Fecha y hora
                        <input v-model="appointmentForm.scheduled_at" type="datetime-local" required>
                    </label>
                    <label>
                        Duracion
                        <select v-model.number="appointmentForm.duration_min" required>
                            <option :value="15">15 minutos</option>
                            <option :value="20">20 minutos</option>
                            <option :value="30">30 minutos</option>
                            <option :value="45">45 minutos</option>
                            <option :value="60">60 minutos</option>
                        </select>
                    </label>
                    <label>
                        Motivo
                        <input v-model="appointmentForm.reason" type="text" placeholder="Motivo de consulta">
                    </label>
                    <label>
                        Notas
                        <textarea v-model="appointmentForm.notes" rows="3" placeholder="Notas administrativas o clinicas breves" />
                    </label>
                    <button class="medical-module__button" type="submit" :disabled="saving || doctorSpecialtyMismatch">
                        {{ appointmentForm.id ? 'Guardar cambios' : 'Confirmar y agendar cita' }}
                    </button>
                </form>

                <form v-if="drawer.type === 'doctor'" class="medical-module__drawer-form" @submit.prevent="saveDoctor">
                    <label v-if="! doctorForm.id">
                        ID usuario asociado
                        <input v-model.number="doctorForm.user_id" type="number" min="1" required>
                    </label>
                    <label>
                        Especialidad
                        <select v-model.number="doctorForm.specialty_id" required>
                            <option disabled value="">Selecciona especialidad</option>
                            <option v-for="specialty in specialties" :key="specialty.id" :value="specialty.id">
                                {{ specialty.name }}
                            </option>
                        </select>
                    </label>
                    <label>
                        No. colegiado
                        <input v-model="doctorForm.license_number" type="text" required>
                    </label>
                    <label>
                        Telefono
                        <input v-model="doctorForm.phone" type="text">
                    </label>
                    <button class="medical-module__button" type="submit" :disabled="saving">
                        {{ doctorForm.id ? 'Guardar cambios' : 'Guardar medico' }}
                    </button>
                </form>

                <form v-if="drawer.type === 'specialty'" class="medical-module__drawer-form" @submit.prevent="saveSpecialty">
                    <label>
                        Nombre
                        <input v-model="specialtyForm.name" type="text" required>
                    </label>
                    <label>
                        Descripcion
                        <textarea v-model="specialtyForm.description" rows="4" />
                    </label>
                    <button class="medical-module__button" type="submit" :disabled="saving">
                        {{ specialtyForm.id ? 'Guardar cambios' : 'Guardar especialidad' }}
                    </button>
                </form>
            </section>
        </aside>
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import api from '@/plugins/axios';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();

const specialties = ref([]);
const doctors = ref([]);
const appointments = ref([]);
const loading = ref(false);
const saving = ref(false);
const message = ref('');
const errorMessage = ref('');
const activeTab = ref('appointments');

const statuses = [
    { value: 'pendiente', label: 'Pendiente' },
    { value: 'confirmada', label: 'Confirmada' },
    { value: 'completada', label: 'Completada' },
    { value: 'cancelada', label: 'Cancelada' },
    { value: 'no_asistio', label: 'No asistio' },
];

const appointmentFilters = reactive({
    date: '',
    search: '',
    doctor_id: '',
    specialty_id: '',
    status: '',
});

const doctorFilters = reactive({
    search: '',
    specialty_id: '',
});

const specialtyFilters = reactive({
    search: '',
});

const drawer = reactive({
    open: false,
    type: '',
});

const appointmentForm = reactive({
    id: null,
    patient_id: '',
    doctor_id: '',
    specialty_id: '',
    scheduled_at: '',
    duration_min: 30,
    reason: '',
    notes: '',
    status: 'pendiente',
});

const doctorForm = reactive({
    id: null,
    user_id: '',
    specialty_id: '',
    license_number: '',
    phone: '',
});

const specialtyForm = reactive({
    id: null,
    name: '',
    description: '',
});

const tabs = computed(() => [
    { key: 'appointments', label: 'Agenda de citas', count: appointments.value.length },
    { key: 'doctors', label: 'Medicos', count: doctors.value.length },
    { key: 'specialties', label: 'Especialidades', count: specialties.value.length },
]);

const filteredAppointments = computed(() => appointments.value.filter((appointment) => {
    const term = normalize(appointmentFilters.search);
    const scheduledDate = appointment.scheduled_at ? appointment.scheduled_at.slice(0, 10) : '';

    return (! appointmentFilters.date || scheduledDate === appointmentFilters.date)
        && (! appointmentFilters.doctor_id || String(appointment.doctor_id) === appointmentFilters.doctor_id)
        && (! appointmentFilters.specialty_id || String(appointment.specialty_id) === appointmentFilters.specialty_id)
        && (! appointmentFilters.status || appointment.status === appointmentFilters.status)
        && (! term || normalize([
            appointment.reason,
            appointment.patient?.code,
            appointment.patient?.first_name,
            appointment.patient?.last_name,
            appointment.doctor?.user?.name,
            appointment.specialty?.name,
        ].join(' ')).includes(term));
}));

const filteredDoctors = computed(() => doctors.value.filter((doctor) => {
    const term = normalize(doctorFilters.search);

    return (! doctorFilters.specialty_id || String(doctor.specialty_id) === doctorFilters.specialty_id)
        && (! term || normalize([
            doctor.user?.name,
            doctor.user?.email,
            doctor.license_number,
            doctor.phone,
            doctor.specialty?.name,
        ].join(' ')).includes(term));
}));

const filteredSpecialties = computed(() => specialties.value.filter((specialty) => {
    const term = normalize(specialtyFilters.search);

    return ! term || normalize(`${specialty.name} ${specialty.description || ''}`).includes(term);
}));

const appointmentKpis = computed(() => {
    const total = appointments.value.length;

    return [
        { key: 'total', label: 'Citas', value: total, hint: 'Total cargado' },
        ...statuses.map((status) => ({
            key: status.value,
            label: status.label,
            value: countAppointmentsByStatus(status.value),
            hint: percentage(countAppointmentsByStatus(status.value), total),
        })),
    ];
});

const drawerTitle = computed(() => {
    if (drawer.type === 'appointment') {
        return appointmentForm.id ? 'Editar cita medica' : 'Nueva cita medica';
    }

    if (drawer.type === 'doctor') {
        return doctorForm.id ? 'Editar medico' : 'Nuevo medico';
    }

    return specialtyForm.id ? 'Editar especialidad' : 'Nueva especialidad';
});

const drawerLabel = computed(() => {
    if (drawer.type === 'appointment') {
        return 'Agenda de citas';
    }

    if (drawer.type === 'doctor') {
        return 'Catalogo medico';
    }

    return 'Catalogo de especialidades';
});

const doctorSpecialtyMismatch = computed(() => {
    if (! appointmentForm.doctor_id || ! appointmentForm.specialty_id) {
        return false;
    }

    const doctor = doctors.value.find((item) => item.id === appointmentForm.doctor_id);

    return doctor && Number(doctor.specialty_id ?? doctor.specialty?.id) !== Number(appointmentForm.specialty_id);
});

function normalizePaginated(data) {
    return data?.data ?? [];
}

function normalize(value) {
    return String(value ?? '').toLowerCase().trim();
}

function clearFeedback() {
    message.value = '';
    errorMessage.value = '';
}

function handleError(error) {
    const errors = error?.response?.data?.errors;
    const firstField = errors ? Object.keys(errors)[0] : null;
    errorMessage.value = firstField ? errors[firstField][0] : (error?.response?.data?.message ?? 'No fue posible completar la accion.');
}

async function loadAll() {
    clearFeedback();
    loading.value = true;

    try {
        const specialtiesResponse = await api.get('/specialties', { params: { per_page: 50 }, timeout: 8000 });
        const doctorsResponse = await api.get('/doctors', { params: { per_page: 50 }, timeout: 8000 });
        const appointmentsResponse = await api.get('/appointments', { params: { per_page: 50 }, timeout: 8000 });

        specialties.value = normalizePaginated(specialtiesResponse.data);
        doctors.value = normalizePaginated(doctorsResponse.data);
        appointments.value = normalizePaginated(appointmentsResponse.data);
    } catch (error) {
        handleError(error);
    } finally {
        loading.value = false;
    }
}

function openAppointmentDrawer(appointment = null) {
    resetAppointmentForm();

    if (appointment) {
        appointmentForm.id = appointment.id;
        appointmentForm.patient_id = appointment.patient_id;
        appointmentForm.doctor_id = appointment.doctor_id;
        appointmentForm.specialty_id = appointment.specialty_id;
        appointmentForm.scheduled_at = toDateTimeLocal(appointment.scheduled_at);
        appointmentForm.duration_min = appointment.duration_min;
        appointmentForm.reason = appointment.reason ?? '';
        appointmentForm.notes = appointment.notes ?? '';
        appointmentForm.status = appointment.status ?? 'pendiente';
    }

    drawer.type = 'appointment';
    drawer.open = true;
}

function openDoctorDrawer(doctor = null) {
    resetDoctorForm();

    if (doctor) {
        doctorForm.id = doctor.id;
        doctorForm.user_id = doctor.user_id;
        doctorForm.specialty_id = doctor.specialty_id ?? doctor.specialty?.id ?? '';
        doctorForm.license_number = doctor.license_number ?? '';
        doctorForm.phone = doctor.phone ?? '';
    }

    drawer.type = 'doctor';
    drawer.open = true;
}

function openSpecialtyDrawer(specialty = null) {
    resetSpecialtyForm();

    if (specialty) {
        specialtyForm.id = specialty.id;
        specialtyForm.name = specialty.name ?? '';
        specialtyForm.description = specialty.description ?? '';
    }

    drawer.type = 'specialty';
    drawer.open = true;
}

function closeDrawer() {
    drawer.open = false;
    drawer.type = '';
}

function resetAppointmentForm() {
    Object.assign(appointmentForm, {
        id: null,
        patient_id: '',
        doctor_id: '',
        specialty_id: '',
        scheduled_at: '',
        duration_min: 30,
        reason: '',
        notes: '',
        status: 'pendiente',
    });
}

function resetDoctorForm() {
    Object.assign(doctorForm, {
        id: null,
        user_id: '',
        specialty_id: '',
        license_number: '',
        phone: '',
    });
}

function resetSpecialtyForm() {
    Object.assign(specialtyForm, {
        id: null,
        name: '',
        description: '',
    });
}

async function saveAppointment() {
    await save(async () => {
        const payload = {
            patient_id: appointmentForm.patient_id,
            doctor_id: appointmentForm.doctor_id,
            specialty_id: appointmentForm.specialty_id,
            scheduled_at: appointmentForm.scheduled_at.replace('T', ' '),
            duration_min: appointmentForm.duration_min,
            status: appointmentForm.status,
            reason: appointmentForm.reason,
            notes: appointmentForm.notes,
        };

        if (appointmentForm.id) {
            await api.put(`/appointments/${appointmentForm.id}`, payload);
            message.value = 'Cita actualizada.';
        } else {
            await api.post('/appointments', payload);
            message.value = 'Cita agendada.';
        }

        closeDrawer();
    });
}

async function saveDoctor() {
    await save(async () => {
        const payload = {
            specialty_id: doctorForm.specialty_id,
            license_number: doctorForm.license_number,
            phone: doctorForm.phone,
        };

        if (doctorForm.id) {
            await api.put(`/doctors/${doctorForm.id}`, payload);
            message.value = 'Medico actualizado.';
        } else {
            await api.post('/doctors', { ...payload, user_id: doctorForm.user_id });
            message.value = 'Medico creado.';
        }

        closeDrawer();
    });
}

async function saveSpecialty() {
    await save(async () => {
        const payload = {
            name: specialtyForm.name,
            description: specialtyForm.description,
        };

        if (specialtyForm.id) {
            await api.put(`/specialties/${specialtyForm.id}`, payload);
            message.value = 'Especialidad actualizada.';
        } else {
            await api.post('/specialties', payload);
            message.value = 'Especialidad creada.';
        }

        closeDrawer();
    });
}

async function changeAppointmentStatus(appointment, status) {
    await save(async () => {
        await api.post(`/appointments/${appointment.id}/status`, { status, notes: appointment.notes });
        message.value = 'Estado de cita actualizado.';
    });
}

async function cancelAppointment(appointment) {
    await save(async () => {
        await api.post(`/appointments/${appointment.id}/cancel`, { notes: appointment.notes });
        message.value = 'Cita cancelada.';
    });
}

async function save(callback) {
    clearFeedback();
    saving.value = true;

    try {
        await callback();
        await loadAll();
    } catch (error) {
        handleError(error);
    } finally {
        saving.value = false;
    }
}

function syncDoctorSpecialty() {
    const doctor = doctors.value.find((item) => item.id === appointmentForm.doctor_id);
    appointmentForm.specialty_id = doctor?.specialty_id ?? doctor?.specialty?.id ?? appointmentForm.specialty_id;
}

function filterAgendaBySpecialty(specialty) {
    appointmentFilters.specialty_id = String(specialty.id);
    activeTab.value = 'appointments';
}

function doctorName(doctor) {
    return doctor?.user?.name || (doctor?.id ? `Medico #${doctor.id}` : 'Sin medico');
}

function patientName(appointment) {
    const patient = appointment.patient;

    if (! patient) {
        return appointment.patient_id ? `Paciente #${appointment.patient_id}` : 'Sin paciente';
    }

    return `${patient.first_name ?? ''} ${patient.last_name ?? ''}`.trim() || patient.code;
}

function formatDate(value) {
    if (! value) {
        return '';
    }

    return new Intl.DateTimeFormat('es-GT', { dateStyle: 'medium' }).format(new Date(value));
}

function formatTime(value) {
    if (! value) {
        return '';
    }

    return new Intl.DateTimeFormat('es-GT', { hour: '2-digit', minute: '2-digit' }).format(new Date(value));
}

function toDateTimeLocal(value) {
    if (! value) {
        return '';
    }

    const date = new Date(value);
    const offsetDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000);

    return offsetDate.toISOString().slice(0, 16);
}

function statusLabel(status) {
    return statuses.find((item) => item.value === status)?.label ?? status;
}

function statusClass(status) {
    return `medical-module__badge--${status}`;
}

function countAppointmentsByStatus(status) {
    return appointments.value.filter((appointment) => appointment.status === status).length;
}

function percentage(value, total) {
    if (! total) {
        return '0%';
    }

    return `${Math.round((value / total) * 100)}%`;
}

function doctorAppointmentCount(doctorId) {
    return appointments.value.filter((appointment) => Number(appointment.doctor_id) === Number(doctorId)).length;
}

function specialtyDoctorCount(specialtyId) {
    return doctors.value.filter((doctor) => Number(doctor.specialty_id) === Number(specialtyId)).length;
}

function specialtyAppointmentCount(specialtyId) {
    return appointments.value.filter((appointment) => Number(appointment.specialty_id) === Number(specialtyId)).length;
}

onMounted(loadAll);
</script>

<style scoped>
.medical-module {
    --clinical-primary: #0d9488;
    --clinical-primary-dark: #0f766e;
    --clinical-info: #2563eb;
    --clinical-text: #1e293b;
    --clinical-muted: #64748b;
    --clinical-border: #e2e8f0;
    --clinical-bg: #f8fafc;
    --clinical-surface: #ffffff;
    --clinical-success: #16a34a;
    --clinical-success-bg: #dcfce7;
    --clinical-warning: #d97706;
    --clinical-warning-bg: #fef3c7;
    --clinical-danger: #dc2626;
    --clinical-danger-bg: #fee2e2;
    color: var(--clinical-text);
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.medical-module__hero,
.medical-module__panel,
.medical-module__specialty-card {
    border: 1px solid var(--clinical-border);
    border-radius: 8px;
    background: var(--clinical-surface);
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
}

.medical-module__hero {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem;
}

.medical-module__breadcrumb {
    margin: 0 0 0.4rem;
    color: var(--clinical-primary-dark);
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.medical-module__hero h1 {
    margin: 0;
    font-size: 1.55rem;
    line-height: 1.2;
}

.medical-module__hero p:not(.medical-module__breadcrumb) {
    margin: 0.35rem 0 0;
    color: var(--clinical-muted);
    line-height: 1.5;
}

.medical-module__hero-actions,
.medical-module__row-actions,
.medical-module__card-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.medical-module__button,
.medical-module__tab,
.medical-module__link-button,
.medical-module__icon-button {
    font: inherit;
    cursor: pointer;
}

.medical-module__button {
    border: 1px solid var(--clinical-primary-dark);
    border-radius: 7px;
    background: var(--clinical-primary-dark);
    color: #ffffff;
    padding: 0.65rem 0.9rem;
    font-weight: 800;
}

.medical-module__button:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.medical-module__button--quiet {
    background: #ffffff;
    color: var(--clinical-primary-dark);
}

.medical-module__button--block {
    width: 100%;
    justify-content: center;
}

.medical-module__notice {
    border-radius: 8px;
    padding: 0.8rem 1rem;
    font-weight: 700;
}

.medical-module__notice--success {
    background: var(--clinical-success-bg);
    color: #166534;
}

.medical-module__notice--error,
.medical-module__validation {
    background: var(--clinical-danger-bg);
    color: #991b1b;
}

.medical-module__tabs {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
}

.medical-module__tab {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: 1px solid var(--clinical-border);
    border-radius: 8px;
    background: #ffffff;
    color: var(--clinical-text);
    padding: 0.9rem 1rem;
    font-weight: 800;
}

.medical-module__tab strong {
    border-radius: 999px;
    background: #f1f5f9;
    color: var(--clinical-muted);
    padding: 0.2rem 0.55rem;
    font-size: 0.78rem;
}

.medical-module__tab--active {
    border-color: var(--clinical-primary);
    background: #f0fdfa;
    color: var(--clinical-primary-dark);
}

.medical-module__workspace {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.medical-module__kpis {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0.75rem;
}

.medical-module__kpi {
    border: 1px solid var(--clinical-border);
    border-radius: 8px;
    background: #ffffff;
    padding: 0.85rem;
}

.medical-module__kpi span,
.medical-module__panel-heading p,
.medical-module__table td span,
.medical-module__availability small,
.medical-module__specialty-card p,
.medical-module__specialty-card dt {
    color: var(--clinical-muted);
}

.medical-module__kpi span {
    display: block;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
}

.medical-module__kpi strong {
    display: block;
    margin-top: 0.25rem;
    font-size: 1.45rem;
}

.medical-module__kpi small {
    color: var(--clinical-primary-dark);
    font-weight: 700;
}

.medical-module__panel {
    padding: 1rem;
}

.medical-module__panel-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.9rem;
}

.medical-module__panel-heading h2,
.medical-module__specialty-card h2 {
    margin: 0;
    font-size: 1rem;
}

.medical-module__panel-heading p,
.medical-module__specialty-card p {
    margin: 0.25rem 0 0;
    line-height: 1.5;
}

.medical-module__filter-group {
    display: grid;
    grid-template-columns: 0.9fr 1.35fr repeat(3, 1fr);
    gap: 0.75rem;
}

.medical-module__filter-group--compact {
    grid-template-columns: minmax(260px, 1.3fr) minmax(220px, 0.7fr);
}

.medical-module label {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    color: #334155;
    font-size: 0.82rem;
    font-weight: 800;
}

.medical-module input,
.medical-module select,
.medical-module textarea {
    width: 100%;
    min-width: 0;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    background: #ffffff;
    color: var(--clinical-text);
    font: inherit;
    font-weight: 500;
    padding: 0.6rem 0.7rem;
}

.medical-module textarea {
    resize: vertical;
}

.medical-module__grid--agenda {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    gap: 1rem;
}

.medical-module__table-wrap {
    overflow-x: auto;
}

.medical-module__table {
    width: 100%;
    min-width: 840px;
    border-collapse: collapse;
}

.medical-module__table th,
.medical-module__table td {
    border-top: 1px solid var(--clinical-border);
    padding: 0.65rem;
    text-align: left;
    vertical-align: top;
}

.medical-module__table th {
    color: #475569;
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.medical-module__table td strong,
.medical-module__table td span {
    display: block;
}

.medical-module__badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    background: #eff6ff;
    color: var(--clinical-info);
    padding: 0.25rem 0.55rem;
    font-size: 0.78rem;
    font-weight: 900;
    white-space: nowrap;
}

.medical-module__badge--confirmada {
    background: #ccfbf1;
    color: var(--clinical-primary-dark);
}

.medical-module__badge--completada,
.medical-module__badge--success {
    background: var(--clinical-success-bg);
    color: #166534;
}

.medical-module__badge--pendiente {
    background: var(--clinical-warning-bg);
    color: #92400e;
}

.medical-module__badge--cancelada {
    background: #f1f5f9;
    color: #475569;
}

.medical-module__badge--no_asistio {
    background: var(--clinical-danger-bg);
    color: #991b1b;
}

.medical-module__link-button {
    border: none;
    background: none;
    color: var(--clinical-primary-dark);
    padding: 0;
    font-weight: 900;
}

.medical-module__link-button--danger {
    color: var(--clinical-danger);
}

.medical-module__empty {
    margin: 0;
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    padding: 1rem;
    color: var(--clinical-muted);
}

.medical-module__availability {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin: 0 0 1rem;
    padding: 0;
    list-style: none;
}

.medical-module__availability li {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    border-bottom: 1px solid var(--clinical-border);
    padding-bottom: 0.75rem;
}

.medical-module__availability strong,
.medical-module__availability small {
    display: block;
}

.medical-module__availability em {
    color: var(--clinical-primary-dark);
    font-style: normal;
    font-weight: 800;
    white-space: nowrap;
}

.medical-module__specialty-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}

.medical-module__specialty-card {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;
}

.medical-module__specialty-card dl {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
    margin: 0;
}

.medical-module__specialty-card dt,
.medical-module__specialty-card dd {
    margin: 0;
}

.medical-module__specialty-card dt {
    font-size: 0.75rem;
    font-weight: 900;
    text-transform: uppercase;
}

.medical-module__specialty-card dd {
    color: var(--clinical-text);
    font-size: 1.2rem;
    font-weight: 900;
}

.medical-module__drawer-backdrop {
    position: fixed;
    inset: 0;
    z-index: 40;
    display: flex;
    justify-content: flex-end;
    background: rgba(15, 23, 42, 0.34);
}

.medical-module__drawer {
    width: min(460px, 100%);
    height: 100%;
    overflow-y: auto;
    background: #ffffff;
    box-shadow: -24px 0 50px rgba(15, 23, 42, 0.18);
}

.medical-module__drawer header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    border-bottom: 1px solid var(--clinical-border);
    padding: 1rem;
}

.medical-module__drawer header span {
    color: var(--clinical-primary-dark);
    font-size: 0.75rem;
    font-weight: 900;
    text-transform: uppercase;
}

.medical-module__drawer header h2 {
    margin: 0.2rem 0 0;
    font-size: 1.1rem;
}

.medical-module__icon-button {
    width: 2rem;
    height: 2rem;
    border: 1px solid var(--clinical-border);
    border-radius: 999px;
    background: #ffffff;
    color: var(--clinical-muted);
    font-weight: 900;
}

.medical-module__drawer-form {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    padding: 1rem;
}

.medical-module__validation {
    margin: 0;
    border-radius: 7px;
    padding: 0.65rem 0.75rem;
    font-size: 0.86rem;
    font-weight: 800;
}

@media (max-width: 1180px) {
    .medical-module__kpis {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .medical-module__filter-group,
    .medical-module__grid--agenda,
    .medical-module__specialty-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    .medical-module__hero {
        align-items: stretch;
        flex-direction: column;
    }

    .medical-module__hero-actions,
    .medical-module__tabs,
    .medical-module__kpis,
    .medical-module__filter-group--compact {
        grid-template-columns: 1fr;
    }

    .medical-module__tabs,
    .medical-module__kpis {
        display: grid;
    }
}
</style>
