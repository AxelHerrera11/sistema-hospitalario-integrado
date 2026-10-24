import { createRouter, createWebHistory } from 'vue-router';
import HomePage from '@/pages/HomePage.vue';
import LoginPage from '@/modules/auth/pages/LoginPage.vue';
import { authGuard } from '@/router/guards';
import { ROUTE_PERMISSIONS } from '@/router/permissions';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/ocupacion',
            name: 'ocupacion',
            component: () => import('@/modules/ocupacion/pages/OccupancyPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.ocupacion },
        },
        {
            path: '/medicamentos',
            name: 'medicamentos',
            component: () => import('@/modules/medicamentos/pages/MedicationsPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.medicamentos },
        },
        {
            path: '/alertas',
            name: 'alertas',
            component: () => import('@/modules/alertas/pages/CriticalAlertsPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.alertas },
        },
        {
            path: '/traslados',
            name: 'traslados',
            component: () => import('@/modules/traslados/pages/TrasladosPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.traslados },
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
            path: '/wards',
            name: 'wards',
            component: () => import('@/modules/salas-camas/pages/WardCatalogPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.wards },
        },
        {
            path: '/reportes',
            name: 'reportes',
            component: () => import('@/modules/reportes/pages/ReportsPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.reportes },
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
