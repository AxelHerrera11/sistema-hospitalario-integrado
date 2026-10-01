import { createRouter, createWebHistory } from 'vue-router';
import HomePage from '@/pages/HomePage.vue';
import LoginPage from '@/modules/auth/pages/LoginPage.vue';
import MedicalSchedulingPage from '@/modules/medical/pages/MedicalSchedulingPage.vue';
import { authGuard } from '@/router/guards';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'home',
            component: HomePage,
            meta: { requiresAuth: true },
        },
        {
            path: '/login',
            name: 'login',
            component: LoginPage,
            meta: { guest: true },
        },
        {
            path: '/medicos-citas',
            name: 'medical-scheduling',
            component: MedicalSchedulingPage,
            meta: { requiresAuth: true, permission: 'citas.ver' },
        },
        // Cada área registra aquí sus rutas con meta.requiresAuth y, si aplica,
        // meta.permission: 'pacientes.ver' (lo valida el guard).
        {
            path: '/:pathMatch(.*)*',
            redirect: { name: 'home' },
        },
    ],
});

router.beforeEach(authGuard);

export default router;
