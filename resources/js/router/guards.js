import { useAuthStore } from '@/stores/auth';

export async function authGuard(to) {
    const auth = useAuthStore();
    await auth.initialize();

    if (to.meta.requiresAuth && ! auth.token) {
        return { name: 'login' };
    }

    if (to.meta.guest && auth.token) {
        return { name: 'home' };
    }
}
