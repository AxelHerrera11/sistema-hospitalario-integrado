// Arnés de evidencia (ASII-04): monta la aplicación real (layout, router y pantalla de médicos) con un API simulado.
// El API NO es el real: responde datos ficticios para poder fotografiar cada estado de la pantalla.
import '../../../../../resources/css/app.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from '@/App.vue';
import router from '@/router';
import api from '@/plugins/axios';

const rol = new URLSearchParams(location.search).get('rol') ?? 'admin';
localStorage.setItem('tenant_id', 'hospital-demo');
const PERMISOS = {
    admin: ['medicos.ver_disponibilidad', 'medicos.registrar', 'medicos.asociar_especialidad'],
    recepcion: ['medicos.ver_disponibilidad'],
    sinpermiso: [],
};
const sesion = () => ({
    access_token: 'token-demo',
    user: { id: 'u-demo', name: 'Usuario de demostración', email: 'demo@hospital.test', permissions: PERMISOS[rol] },
    hospital: { id: 'hospital-demo', name: 'Hospital de demostración' },
    roles: [rol === 'admin' ? 'admin_hospital' : rol],
});
const ids = ['5b0e1c7a-3f1d-4c2a-9e55-1a2b3c4d5e01', '8d4f2a90-7c6b-4e13-a8d2-9f0e1d2c3b02', 'c3a91f55-2e8d-4b77-b1c0-6d5e4f3a2c03'];
const responde = (status, data) => Promise.resolve({ data, status, statusText: 'OK', headers: {}, config: {} });
const falla = (status, data, config) => Promise.reject(Object.assign(new Error('simulado'), { response: { status, data }, config }));

api.defaults.adapter = async (config) => {
    const body = config.data ? JSON.parse(config.data) : {};
    const clave = `${config.method.toUpperCase()} ${config.url}`;
    if (clave === 'POST /auth/refresh' || clave === 'GET /auth/me') return responde(200, sesion());
    if (clave === 'GET /availability') {
        const { specialty } = config.params;
        if (specialty === 'FALLA') return falla(503, { code: 'DOCTOR_STORAGE_UNAVAILABLE' }, config);
        if (specialty !== 'NEURO') return responde(200, { code: 'NO_DOCTORS_FOR_SPECIALTY', doctors: [] });
        return responde(200, { code: 'OK', doctors: [
            { doctor_id: ids[0], status: 'OK', slots: ['08:00', '09:00', '09:30'] },
            { doctor_id: ids[1], status: 'OK', slots: [] },
            { doctor_id: ids[2], status: 'SCHEDULE_NOT_CONFIGURED', slots: [] },
        ] });
    }
    if (clave === 'POST /doctors') {
        return body.license_number === 'COL-9'
            ? falla(409, { code: 'LICENSE_ALREADY_REGISTERED' }, config)
            : responde(201, { doctor_profile_id: ids[0] });
    }
    if (clave.startsWith('POST /doctors/')) {
        return body.is_primary ? falla(409, { code: 'PRIMARY_SPECIALTY_CONFLICT' }, config)
            : responde(201, { doctor_profile_id: ids[0], specialty_id: 'sp-neuro', is_primary: false });
    }
    return falla(404, { code: 'ROUTE_NOT_FOUND' }, config);
};

const app = createApp(App);
app.use(createPinia());
app.use(router);
app.mount('#app');
