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
    traslados: 'traslados.ver',
    wards: 'camas.ver',
    reportes: 'reportes.ver',
};

/** Entradas de navegación sujetas a permiso. Las entradas sin `to` se
 * muestran como «próximamente» hasta que su módulo registre la ruta. */
export const NAVIGATION = [
    { label: 'Inicio', to: '/', permission: null },
    { label: 'Ocupación', to: '/ocupacion', permission: ROUTE_PERMISSIONS.ocupacion },
    { label: 'Usuarios', to: '/admin/users', permission: ROUTE_PERMISSIONS['admin-users'] },
    { label: 'Pacientes', to: '/patients', permission: ROUTE_PERMISSIONS.patients },
    { label: 'Salas y camas', to: '/wards', permission: ROUTE_PERMISSIONS.wards },
    { label: 'Medicamentos', to: '/medicamentos', permission: ROUTE_PERMISSIONS.medicamentos },
    { label: 'Traslados y altas', to: '/traslados', permission: ROUTE_PERMISSIONS.traslados },
    { label: 'Catálogo RBAC', to: '/rbac', permission: ROUTE_PERMISSIONS.rbac },
    { label: 'Citas', to: null, permission: 'citas.ver', soon: true },
    { label: 'Admisión y camas', to: null, permission: 'admision.ver', soon: true },
    { label: 'Expediente', to: null, permission: 'expediente.ver', soon: true },
    { label: 'Signos vitales', to: null, permission: 'signos_vitales.ver', soon: true },
    { label: 'Notas SOAP', to: null, permission: 'notas_soap.ver', soon: true },
    { label: 'Alergias', to: null, permission: 'alergias.ver', soon: true },
    { label: 'Prescripciones', to: null, permission: 'prescripciones.ver', soon: true },
    { label: 'Laboratorio', to: null, permission: 'laboratorio.ver', soon: true },
    { label: 'Reportes', to: '/reportes', permission: ROUTE_PERMISSIONS.reportes },
    { label: 'Auditoría', to: null, permission: 'auditoria.ver', soon: true },
    { label: 'Documentación', to: null, permission: 'documentacion.ver', soon: true },
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

/** Entradas de navegación que el usuario puede ver.
 * - Con `permission` declarado: solo si el usuario tiene el permiso.
 * - Sin `permission` y con ruta: siempre (Inicio).
 * - Sin `permission` y sin ruta (`soon` solo lectura): solo un usuario con
 *   sesión; el item «próximamente» no enlaza a nada. */
export function visibleNavigation(user, entries = NAVIGATION) {
    if (! Array.isArray(entries)) {
        return [];
    }

    return entries.filter((entry) => {
        if (typeof entry.permission === 'string') {
            return can(user, entry.permission);
        }

        /* Rutas publicas sin permiso declarado se muestran solo cuando la
         * sesion ya publico permisos; evita navs vacias o misleading del
         * payload antiguo y no revela rutas protegidas por error. */
        return Boolean(entry.to) && hasPermissions(user);
    });
}
