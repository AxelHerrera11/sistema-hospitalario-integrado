/**
 * Comprueba permisos `modulo.accion` en el frontend.
 *
 * La autoridad final es el backend (motor RBAC); esta comprobación solo mejora
 * la navegación y no sustituye la validación del servidor. Requiere una fuente
 * de permisos efectivos (p. ej. `user.permissions` provisto por la API): hoy
 * `login`/`me` devuelven `user` con `roles` y `tenant`, pero NO `permissions`,
 * por lo que `can` deniega (falla segura). Este helper no se conecta al guard; la decisión
 * queda en el backend. Dependencia pendiente de Cindy/Javier: exponer permisos
 * verificados en el payload de autenticación.
 */

/** Detecta el formato; el consumidor debe obtener los permisos de la API. */
export function hasPermissions(user) {
    return Boolean(user) && Array.isArray(user.permissions);
}

export function can(user, permission) {
    if (! hasPermissions(user)) {
        return false;
    }

    return user.permissions.includes(permission);
}

export function canAny(user, permissions) {
    if (! Array.isArray(permissions)) {
        return false;
    }

    return permissions.some((permission) => can(user, permission));
}
