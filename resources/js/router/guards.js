import { useAuthStore } from '@/stores/auth';
import { allowsRoute } from '@/router/permissions';

export async function authGuard(to) {
    const auth = useAuthStore();
    await auth.initialize();

    if (to.meta.requiresAuth && ! auth.token) {
        return { name: 'login' };
    }

    if (to.meta.guest && auth.token) {
        return { name: 'home' };
    }

    if (to.meta.requiresAuth) {
        if (! allowsRoute(auth.user, to.meta)) {
            auth.setNotice('No tiene permiso para ver esta sección.');
            return { name: 'home' };
        }

        auth.clearNotice();
    }
}
