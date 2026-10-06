import { createRouter, createWebHistory } from 'vue-router';
import HomePage from '@/pages/HomePage.vue';
import LoginPage from '@/modules/auth/pages/LoginPage.vue';
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

        // Cada área agrega sus rutas SOLO dentro de su bloque, con carga diferida
        // (component: () => import(...)) para no tocar los imports de arriba, y con
        // meta.requiresAuth + meta.permission (lo valida el guard). Convención en
        // el README, sección "Convención de frontend".

        // ── Área 1: Auth, usuarios, RBAC y auditoría ──────────────────────
        // ── Área 2: Pacientes y expediente base ───────────────────────────
        // ── Área 3: Médicos, especialidades y citas ───────────────────────
        {
            path: '/medicos-citas',
            name: 'medical-scheduling',
            component: () => import('@/modules/medical/pages/MedicalSchedulingPage.vue'),
            meta: { requiresAuth: true, permission: 'citas.ver' },
        },
        // ── Área 4: Salas, camas, admisión, traslados y altas ─────────────
        // ── Área 5: Notas SOAP, diagnósticos y signos vitales ─────────────
        // ── Área 6: Alergias, medicamentos y prescripciones ───────────────
        // ── Área 7: Laboratorio ───────────────────────────────────────────
        // ── Área 8: Alertas críticas, dashboard y reportes ────────────────

        {
            path: '/:pathMatch(.*)*',
            redirect: { name: 'home' },
        },
    ],
});

router.beforeEach(authGuard);

export default router;
