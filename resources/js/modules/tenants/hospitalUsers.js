/**
 * Utilidades puras de la administración de usuarios del hospital (ASII-25, Tenants).
 *
 * Se separan del componente para verificarlas con `node`, sin navegador. La autoridad es el backend
 * (ManageHospitalMembership): aquí solo se decide qué acciones mostrar y qué mensaje dar.
 */

/** Roles canónicos (CanonicalRole::ALL), en el mismo orden que el backend. */
export const ROLES = [
    { valor: 'superadmin', etiqueta: 'Superadministración' },
    { valor: 'admin_hospital', etiqueta: 'Administración del hospital' },
    { valor: 'medico', etiqueta: 'Medicina' },
    { valor: 'enfermeria', etiqueta: 'Enfermería' },
    { valor: 'recepcion', etiqueta: 'Recepción' },
    { valor: 'tecnico_laboratorio', etiqueta: 'Técnico de laboratorio' },
    { valor: 'bioquimico', etiqueta: 'Bioquímica' },
    { valor: 'jefatura_medica', etiqueta: 'Jefatura médica' },
];

export function etiquetaRol(valor) {
    return ROLES.find((rol) => rol.valor === valor)?.etiqueta ?? valor;
}

/** Roles que aún se pueden asignar al usuario (los que no tiene). */
export function rolesAsignables(usuario) {
    const actuales = new Set(usuario?.roles ?? []);

    return ROLES.filter((rol) => ! actuales.has(rol.valor));
}

/**
 * Acciones visibles para una fila. Nadie modifica sus propios roles ni su propio acceso (SELF_ROLE_CHANGE),
 * y un usuario inactivo no recibe roles nuevos (USER_INACTIVE).
 */
export function accionesPara(usuario, actorId, permisos) {
    const propio = Boolean(actorId) && usuario?.user_id === actorId;
    const activo = usuario?.status === 'active';

    return {
        asignar: permisos.asignar && ! propio && activo,
        retirar: permisos.asignar && ! propio,
        desactivar: permisos.desactivar && ! propio,
    };
}

const MENSAJES = {
    SELF_ROLE_CHANGE: 'No puede modificar sus propios roles ni su acceso.',
    SUPERADMIN_REQUIRES_SUPERADMIN: 'Solo un superadministrador puede asignar o retirar el rol superadmin.',
    ROLE_INVALID: 'El rol seleccionado no es válido.',
    USER_NOT_FOUND: 'El usuario ya no existe.',
    USER_INACTIVE: 'El usuario está inactivo.',
    ROLE_ALREADY_ASSIGNED: 'El usuario ya tiene ese rol en este hospital.',
    MEMBERSHIP_NOT_FOUND: 'El usuario ya no tiene ese rol o acceso en este hospital.',
    ACTOR_NO_ACTIVE_MEMBERSHIP: 'Su usuario no tiene una membresía activa en este hospital.',
};

/** Mensaje seguro: primero el código conocido; si no, uno genérico por estado. Nunca el detalle interno. */
export function mensajeDeError(datos, estado) {
    if (MENSAJES[datos?.error]) {
        return MENSAJES[datos.error];
    }
    if (estado === 401) {
        return 'Inicie sesión para continuar.';
    }
    if (estado === 403) {
        return 'No tiene permiso para esta acción.';
    }
    if (estado === 422) {
        return 'Revise los datos enviados.';
    }

    return 'No se pudo completar la operación.';
}

/** Texto de confirmación antes de una acción irreversible desde esta pantalla. */
export function confirmacion(accion, usuario, rol) {
    const nombre = usuario?.display_name || usuario?.email || 'el usuario';
    if (accion === 'retirar') {
        return `¿Retirar el rol «${etiquetaRol(rol)}» a ${nombre}?`;
    }

    return `¿Revocar el acceso de ${nombre} a este hospital? Perderá todos sus roles aquí.`;
}
