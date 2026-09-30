import { useAuthStore } from '@/stores/auth';

export function authGuard(to) {
    const auth = useAuthStore();
    const isLoggedIn = Boolean(auth.token);

    if (to.meta.requiresAuth && ! isLoggedIn) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guest && isLoggedIn) {
        return { name: 'home' };
    }

    // Control fino por permiso (la API lo valida de nuevo; esto es solo UX).
    if (to.meta.permission && ! auth.can(to.meta.permission)) {
        return { name: 'home' };
    }

    return true;
}
