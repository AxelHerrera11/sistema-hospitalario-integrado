import { allowsRoute } from './permissions.js';

// Revalidar contra el servidor evita conservar permisos de un rol revocado.
export async function authorizeNavigation(auth, to) {
    await auth.initialize();
    if (to.meta.requiresAuth && !auth.token) return { name: 'login' };
    if (to.meta.guest && auth.token) return { name: 'home' };
    if (!to.meta.requiresAuth) return;
    try {
        await auth.synchronizeIdentity();
    } catch (error) {
        if (error.response?.status === 401 || error.response?.status === 403) {
            auth.clearSession();
            return { name: 'login' };
        }
        auth.user = null;
        auth.setNotice('No fue posible verificar los permisos. Intente nuevamente.');
        return { name: 'home' };
    }
    if (!allowsRoute(auth.user, to.meta)) {
        auth.setNotice('No tiene permiso para ver esta sección.');
        return { name: 'home' };
    }
    auth.clearNotice();
}
