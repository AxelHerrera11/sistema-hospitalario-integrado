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
            path: '/patients',
            name: 'patients',
            component: () => import('@/modules/patients/pages/PatientsPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.patients },
        },
        ...['new', 'detail', 'edit', 'restricted', 'saved'].map((view) => ({
            path: `/patients/${view}`,
            name: `patients-${view}`,
            component: () => import('@/modules/patients/pages/PatientsPage.vue'),
            meta: { requiresAuth: true, permission: ROUTE_PERMISSIONS.patients },
        })),
    ],
});

router.beforeEach(authGuard);

export default router;
