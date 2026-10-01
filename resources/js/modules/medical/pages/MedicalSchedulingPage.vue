<template>
    <section class="medical-page">
        <header class="medical-page__header">
            <div>
                <p class="medical-page__eyebrow">ASII-03</p>
                <h1 class="medical-page__title">Médicos, especialidades y citas</h1>
            </div>
            <button class="medical-page__button medical-page__button--ghost" type="button" :disabled="loading" @click="loadAll">
                {{ loading ? 'Actualizando' : 'Actualizar' }}
            </button>
        </header>

        <p v-if="message" class="medical-page__message">
            {{ message }}
        </p>
        <p v-if="errorMessage" class="medical-page__error">
            {{ errorMessage }}
        </p>

        <div class="medical-page__grid">
            <section class="medical-page__panel">
                <div class="medical-page__panel-header">
                    <h2>Especialidades</h2>
                    <span>{{ specialties.length }}</span>
                </div>
                <form v-if="auth.can('medicos.gestionar')" class="medical-page__form" @submit.prevent="createSpecialty">
                    <input v-model="specialtyForm.name" type="text" placeholder="Nombre de especialidad" required>
                    <input v-model="specialtyForm.description" type="text" placeholder="Descripción clínica">
                    <button class="medical-page__button" type="submit" :disabled="saving">
                        Guardar especialidad
                    </button>
                </form>
                <p v-if="loading" class="medical-page__empty">Cargando especialidades.</p>
                <ul v-else-if="specialties.length" class="medical-page__list">
                    <li v-for="specialty in specialties" :key="specialty.id">
                        <strong>{{ specialty.name }}</strong>
                        <span>{{ specialty.description || 'Sin descripción' }}</span>
                    </li>
                </ul>
                <p v-else class="medical-page__empty">No hay especialidades registradas.</p>
            </section>

            <section class="medical-page__panel">
                <div class="medical-page__panel-header">
                    <h2>Médicos</h2>
                    <span>{{ doctors.length }}</span>
                </div>
                <form v-if="auth.can('medicos.gestionar')" class="medical-page__form" @submit.prevent="createDoctor">
                    <div class="medical-page__inline">
                        <input v-model.number="doctorForm.user_id" type="number" min="1" placeholder="ID usuario" required>
                        <select v-model.number="doctorForm.specialty_id" required>
                            <option disabled value="">Especialidad</option>
                            <option v-for="specialty in specialties" :key="specialty.id" :value="specialty.id">
                                {{ specialty.name }}
                            </option>
                        </select>
                    </div>
                    <div class="medical-page__inline">
                        <input v-model="doctorForm.license_number" type="text" placeholder="No. colegiado" required>
                        <input v-model="doctorForm.phone" type="text" placeholder="Teléfono">
                    </div>
                    <button class="medical-page__button" type="submit" :disabled="saving">
                        Guardar médico
                    </button>
                </form>
                <p v-if="loading" class="medical-page__empty">Cargando médicos.</p>
                <ul v-else-if="doctors.length" class="medical-page__list">
                    <li v-for="doctor in doctors" :key="doctor.id">
                        <strong>{{ doctor.user?.name || `Médico #${doctor.id}` }}</strong>
                        <span>{{ doctor.specialty?.name }} · {{ doctor.license_number }}</span>
                    </li>
                </ul>
                <p v-else class="medical-page__empty">No hay médicos registrados.</p>
            </section>
        </div>

        <section class="medical-page__panel medical-page__panel--wide">
            <div class="medical-page__panel-header">
                <h2>Citas</h2>
                <span>{{ appointments.length }}</span>
            </div>
            <form v-if="auth.can('citas.crear')" class="medical-page__form medical-page__form--appointments" @submit.prevent="createAppointment">
                <input v-model.number="appointmentForm.patient_id" type="number" min="1" placeholder="ID paciente" required>
                <select v-model.number="appointmentForm.doctor_id" required @change="syncDoctorSpecialty">
                    <option disabled value="">Médico</option>
                    <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                        {{ doctor.user?.name || `Médico #${doctor.id}` }} - {{ doctor.specialty?.name }}
                    </option>
                </select>
                <select v-model.number="appointmentForm.specialty_id" required>
                    <option disabled value="">Especialidad</option>
                    <option v-for="specialty in specialties" :key="specialty.id" :value="specialty.id">
                        {{ specialty.name }}
                    </option>
                </select>
                <input v-model="appointmentForm.scheduled_at" type="datetime-local" required>
                <input v-model.number="appointmentForm.duration_min" type="number" min="10" max="240" step="5" placeholder="Minutos" required>
                <input v-model="appointmentForm.reason" type="text" placeholder="Motivo">
                <button class="medical-page__button" type="submit" :disabled="saving">
                    Agendar cita
                </button>
            </form>

            <div class="medical-page__table-wrap">
                <p v-if="loading" class="medical-page__empty">Cargando agenda de citas.</p>
                <table v-else-if="appointments.length" class="medical-page__table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Paciente</th>
                            <th>Médico</th>
                            <th>Especialidad</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="appointment in appointments" :key="appointment.id">
                            <td>{{ formatDate(appointment.scheduled_at) }}</td>
                            <td>{{ appointment.patient?.code }} · {{ appointment.patient?.first_name }} {{ appointment.patient?.last_name }}</td>
                            <td>{{ appointment.doctor?.user?.name }}</td>
                            <td>{{ appointment.specialty?.name }}</td>
                            <td>
                                <span class="medical-page__status" :class="statusClass(appointment.status)">
                                    {{ statusLabel(appointment.status) }}
                                </span>
                            </td>
                            <td>
                                <button
                                    v-if="auth.can('citas.cancelar') && appointment.status !== 'cancelada'"
                                    class="medical-page__button medical-page__button--danger"
                                    type="button"
                                    @click="cancelAppointment(appointment)"
                                >
                                    Cancelar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="medical-page__empty">No hay citas registradas.</p>
            </div>
        </section>
    </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
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

const specialtyForm = reactive({
    name: '',
    description: '',
});

const doctorForm = reactive({
    user_id: '',
    specialty_id: '',
    license_number: '',
    phone: '',
});

const appointmentForm = reactive({
    patient_id: '',
    doctor_id: '',
    specialty_id: '',
    scheduled_at: '',
    duration_min: 30,
    reason: '',
});

function normalizePaginated(data) {
    return data?.data ?? [];
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

async function createSpecialty() {
    await save(async () => {
        await api.post('/specialties', specialtyForm);
        specialtyForm.name = '';
        specialtyForm.description = '';
        message.value = 'Especialidad creada.';
    });
}

async function createDoctor() {
    await save(async () => {
        await api.post('/doctors', doctorForm);
        doctorForm.user_id = '';
        doctorForm.specialty_id = '';
        doctorForm.license_number = '';
        doctorForm.phone = '';
        message.value = 'Médico creado.';
    });
}

async function createAppointment() {
    await save(async () => {
        await api.post('/appointments', {
            ...appointmentForm,
            scheduled_at: appointmentForm.scheduled_at.replace('T', ' '),
        });
        appointmentForm.patient_id = '';
        appointmentForm.doctor_id = '';
        appointmentForm.specialty_id = '';
        appointmentForm.scheduled_at = '';
        appointmentForm.duration_min = 30;
        appointmentForm.reason = '';
        message.value = 'Cita agendada.';
    });
}

async function cancelAppointment(appointment) {
    await save(async () => {
        await api.post(`/appointments/${appointment.id}/cancel`, {
            notes: appointment.notes,
        });
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
    appointmentForm.specialty_id = doctor?.specialty_id ?? doctor?.specialty?.id ?? '';
}

function formatDate(value) {
    if (! value) {
        return '';
    }

    return new Intl.DateTimeFormat('es-GT', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function statusLabel(status) {
    const labels = {
        pendiente: 'Pendiente',
        confirmada: 'Confirmada',
        completada: 'Completada',
        cancelada: 'Cancelada',
        no_asistio: 'No asistió',
    };

    return labels[status] ?? status;
}

function statusClass(status) {
    return `medical-page__status--${status}`;
}

onMounted(loadAll);
</script>

<style scoped>
.medical-page {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.medical-page__header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
}

.medical-page__eyebrow {
    margin: 0 0 0.25rem;
    color: #64748b;
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.medical-page__title {
    margin: 0;
    font-size: 1.65rem;
}

.medical-page__message,
.medical-page__error {
    margin: 0;
    border-radius: 8px;
    padding: 0.75rem 1rem;
}

.medical-page__message {
    background: #ecfdf5;
    color: #065f46;
}

.medical-page__error {
    background: #fff1f2;
    color: #9f1239;
}

.medical-page__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.medical-page__panel {
    border: 1px solid #d8e1e8;
    border-radius: 8px;
    background: #ffffff;
    padding: 1rem;
}

.medical-page__panel--wide {
    overflow: hidden;
}

.medical-page__panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}

.medical-page__panel-header h2 {
    margin: 0;
    font-size: 1rem;
}

.medical-page__panel-header span {
    color: #64748b;
    font-weight: 700;
}

.medical-page__form {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    margin-bottom: 1rem;
}

.medical-page__form--appointments {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
}

.medical-page__inline {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.65rem;
}

.medical-page input,
.medical-page select {
    width: 100%;
    min-width: 0;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 0.55rem 0.65rem;
    font: inherit;
}

.medical-page__button {
    border: none;
    border-radius: 6px;
    padding: 0.6rem 0.8rem;
    background: #0f766e;
    color: #ffffff;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
}

.medical-page__button:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.medical-page__button--ghost {
    border: 1px solid #94a3b8;
    background: #ffffff;
    color: #164e63;
}

.medical-page__button--danger {
    background: #be123c;
}

.medical-page__list {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.medical-page__list li {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    border-top: 1px solid #edf2f7;
    padding-top: 0.6rem;
}

.medical-page__list strong {
    color: #102a43;
}

.medical-page__list span {
    color: #64748b;
    font-size: 0.9rem;
}

.medical-page__empty {
    margin: 0;
    border-top: 1px solid #edf2f7;
    padding-top: 0.75rem;
    color: #64748b;
    font-size: 0.92rem;
}

.medical-page__table-wrap {
    overflow-x: auto;
}

.medical-page__table {
    width: 100%;
    min-width: 900px;
    border-collapse: collapse;
}

.medical-page__table th,
.medical-page__table td {
    border-top: 1px solid #e2e8f0;
    padding: 0.7rem;
    text-align: left;
    vertical-align: top;
}

.medical-page__table th {
    color: #475569;
    font-size: 0.8rem;
    text-transform: uppercase;
}

.medical-page__status {
    display: inline-flex;
    border-radius: 999px;
    background: #e0f2fe;
    color: #075985;
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
    font-weight: 700;
}

.medical-page__status--confirmada {
    background: #dcfce7;
    color: #166534;
}

.medical-page__status--completada {
    background: #e0e7ff;
    color: #3730a3;
}

.medical-page__status--cancelada {
    background: #ffe4e6;
    color: #9f1239;
}

.medical-page__status--no_asistio {
    background: #fef3c7;
    color: #92400e;
}

@media (max-width: 1000px) {
    .medical-page__grid,
    .medical-page__form--appointments {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .medical-page__header,
    .medical-page__inline {
        grid-template-columns: 1fr;
    }

    .medical-page__header {
        align-items: stretch;
        flex-direction: column;
    }
}
</style>
