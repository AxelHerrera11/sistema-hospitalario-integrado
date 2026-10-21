import { createRouter, createWebHistory } from 'vue-router';
import HomePage from '@/pages/HomePage.vue';
import LoginPage from '@/modules/auth/pages/LoginPage.vue';
import { authGuard } from '@/router/guards';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/ocupacion',
            name: 'ocupacion',
            component: () => import('@/modules/ocupacion/pages/OccupancyPage.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/rbac',
            name: 'rbac',
            component: () => import('@/modules/rbac/CatalogPage.vue'),
            meta: { requiresAuth: true },
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
            meta: { requiresAuth: true },
        },
        {
            path: '/patients',
            name: 'patients',
            component: () => import('@/modules/patients/pages/PatientsPage.vue'),
            meta: { requiresAuth: true },
        },
        ...['new', 'detail', 'edit', 'restricted', 'saved'].map((view) => ({
            path: `/patients/${view}`,
            name: `patients-${view}`,
            component: () => import('@/modules/patients/pages/PatientsPage.vue'),
            meta: { requiresAuth: true },
        })),
    ],
});

router.beforeEach(authGuard);

export default router;
