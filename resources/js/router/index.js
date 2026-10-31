import { createRouter, createWebHistory } from 'vue-router';
import HomePage from '@/pages/HomePage.vue';
import LoginPage from '@/modules/auth/pages/LoginPage.vue';
import { authGuard } from '@/router/guards';
import { ROUTE_PERMISSIONS } from '@/router/permissions';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/recepcion-muestras', name: 'recepcion-muestras',
            component: () => import('@/modules/recepcion-muestras/ReceptionPage.vue'),
            meta: { requiresAuth: true, permission: 'muestras.ver' },
        },
        {
            path: '/ocupacion',
            name: 'ocupacion',
            component: () => import('@/modules/ocupacion/pages/OccupancyPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.ocupacion },
        },
        {
            path: '/laboratorio/catalogo',
            name: 'catalogo-lab',
            component: () => import('@/modules/catalogo-lab/pages/LabCatalogPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS['catalogo-lab'] },
        },
        {
            path: '/medicamentos',
            name: 'medicamentos',
            component: () => import('@/modules/medicamentos/pages/MedicationsPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.medicamentos },
        },
        {
            path: '/medicos',
            name: 'medicos',
            component: () => import('@/modules/medicos/pages/DoctorsPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.medicos },
        },
        {
            path: '/alertas',
            name: 'alertas',
            component: () => import('@/modules/alertas/pages/CriticalAlertsPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.alertas },
        },
        {
            path: '/prescripciones',
            name: 'prescripciones',
            component: () => import('@/modules/prescripciones/pages/PrescriptionsPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.prescripciones },
        },
        {
            path: '/traslados',
            name: 'traslados',
            component: () => import('@/modules/traslados/pages/TrasladosPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.traslados },
        },
        {
            path: '/alergias',
            name: 'alergias',
            component: () => import('@/modules/alergias/pages/AllergiesPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.alergias },
        },
        {
            path: '/auditoria',
            name: 'auditoria',
            component: () => import('@/modules/auditoria/pages/AuditPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.auditoria },
        },
        {
            path: '/rbac',
            name: 'rbac',
            component: () => import('@/modules/rbac/CatalogPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.rbac },
        },
        {
            path: '/',
            name: 'home',
            component: HomePage,
        },
        {
            path: '/login',
            name: 'login',
            component: LoginPage,
            meta: { guest: true },
        },
        {
            path: '/admin/users',
            name: 'admin-users',
            component: () => import('@/modules/tenants/pages/HospitalUsersPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS['admin-users'] },
        },
        {
            path: '/calidad-versiones',
            name: 'qa-calidad',
            component: () => import('@/modules/qa-calidad/pages/QualityRunsPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS['qa-calidad'] },
        },
        {
            path: '/prototipos/tenants-responsive',
            name: 'tenants-responsive-prototype',
            component: () => import('@/modules/tenants/pages/ResponsivePrototypePage.vue'),
        },
        {
            path: '/patients',
            name: 'patients',
            component: () => import('@/modules/patients/pages/PatientsPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.patients },
        },
        {
            path: '/expediente',
            name: 'expediente',
            component: () => import('@/modules/expediente/pages/MedicalRecordPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.expediente },
        },
        {
            path: '/wards',
            name: 'wards',
            component: () => import('@/modules/salas-camas/pages/WardCatalogPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.wards },
        },
        {
            path: '/nativephp',
            name: 'nativephp',
            component: () => import('@/modules/nativephp/pages/NativePhpPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.nativephp },
        },
        {
            path: '/lab-validation',
            name: 'lab-validation',
            component: () => import('@/modules/laboratorio/pages/ValidationPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS['lab-validation'] },
        },
        {
            path: '/lab-results',
            name: 'lab-results',
            component: () => import('@/modules/laboratorio/pages/LabResultsPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS['lab-results'] },
        },
        {
            path: '/lab-orders',
            name: 'lab-orders',
            component: () => import('@/modules/ordenes-laboratorio/pages/LabOrdersPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS['lab-orders'] },
        },
        {
            path: '/reportes',
            name: 'reportes',
            component: () => import('@/modules/reportes/pages/ReportsPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.reportes },
        },
        {
            path: '/admision',
            name: 'admision',
            component: () => import('@/modules/admision/pages/AdmissionPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.admision },
        },
        ...Object.entries({
            new: ROUTE_PERMISSIONS['patients-new'],
            detail: ROUTE_PERMISSIONS.patients,
            edit: ROUTE_PERMISSIONS['patients-edit'],
            restricted: ROUTE_PERMISSIONS.patients,
            saved: ROUTE_PERMISSIONS.patients,
        }).map(([view, permission]) => ({
            path: `/patients/${view}`,
            name: `patients-${view}`,
            component: () => import('@/modules/patients/pages/PatientsPage.vue'),
            meta: { requiresAuth: true, permission },
        })),
    ],
});

router.beforeEach(authGuard);

export default router;
