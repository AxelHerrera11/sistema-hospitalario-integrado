/**
 * Comprueba permisos `modulo.accion` en el frontend.
 *
 * La autoridad final es el backend (motor RBAC); esta comprobación solo mejora
 * la navegación y no sustituye la validación del servidor. Los permisos
 * efectivos del usuario llegan en `user.permissions` (login, `/auth/me` y
 * renovación), según `docs/rbac/catalogo-modulos.md`; si ese conjunto no está
 * disponible, la comprobación deniega (falla segura).
 */

/** Permiso que exige cada ruta protegida; el backend aplica el mismo permiso. */
export const ROUTE_PERMISSIONS = {
    ocupacion: 'ocupacion.ver',
    medicamentos: 'medicamentos.ver',
    rbac: 'rbac.permisos.ver',
    'admin-users': 'usuarios.ver',
    patients: 'pacientes.ver',
    'patients-new': 'pacientes.registrar',
    'patients-edit': 'pacientes.editar',
};

/** Entradas de navegación sujetas a permiso. */
export const NAVIGATION = [
    { label: 'Ocupación', to: '/ocupacion', permission: ROUTE_PERMISSIONS.ocupacion },
    { label: 'Usuarios', to: '/admin/users', permission: ROUTE_PERMISSIONS['admin-users'] },
    { label: 'Pacientes', to: '/patients', permission: ROUTE_PERMISSIONS.patients },
    { label: 'Medicamentos', to: '/medicamentos', permission: ROUTE_PERMISSIONS.medicamentos },
];

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

/** Permisos exigidos por una ruta: `meta.permission` o `meta.permissions`. */
export function requiredPermissions(meta) {
    if (! meta || typeof meta !== 'object') {
        return [];
    }

    const single = typeof meta.permission === 'string' ? meta.permission : null;
    const many = Array.isArray(meta.permissions) ? meta.permissions : [];

    return [...new Set([single, ...many].filter((permission) => typeof permission === 'string' && permission.length > 0))];
}

/** ¿El usuario puede entrar a la ruta? Sin permisos publicados se deniega. */
export function allowsRoute(user, meta) {
    const required = requiredPermissions(meta);

    return required.length === 0 || canAny(user, required);
}

/** Entradas de navegación que el usuario puede ver. */
export function visibleNavigation(user, entries = NAVIGATION) {
    if (! Array.isArray(entries)) {
        return [];
    }

    return entries.filter((entry) => can(user, entry.permission));
}
