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
    EMAIL_TAKEN: 'Ya existe una cuenta con ese correo.',
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
    if (estado === 422 && datos?.errors?.password) {
        return 'La contraseña no cumple la política: 12 caracteres con mayúscula, minúscula, número y símbolo.';
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

/** Iniciales para el avatar: primera letra de las dos primeras palabras del nombre, o del correo. */
export function iniciales(usuario) {
    const palabras = (usuario?.display_name || usuario?.email || '?').trim().split(/[\s@._-]+/).filter(Boolean);

    return palabras.slice(0, 2).map((p) => p[0].toUpperCase()).join('') || '?';
}

const ESTADOS = {
    active: { etiqueta: 'Activo', tono: 'ok' },
    inactive: { etiqueta: 'Inactivo', tono: 'off' },
};

/** Estado legible y su tono visual; un estado desconocido se muestra tal cual, en gris. */
export function estadoDe(status) {
    return ESTADOS[status] ?? { etiqueta: status || 'Sin estado', tono: 'off' };
}

/** Resumen de la página visible; el total viene del servidor porque la lista está paginada. */
export function resumen(usuarios, total) {
    const lista = usuarios ?? [];

    return {
        total: total ?? lista.length,
        activos: lista.filter((u) => u.status === 'active').length,
        sinRoles: lista.filter((u) => (u.roles ?? []).length === 0).length,
    };
}

/** Roles para el alta: el rol superadmin solo lo ofrece a quien ya es superadmin (SUPERADMIN_REQUIRES_SUPERADMIN). */
export function rolesParaAlta(rolesDelActor) {
    const esSuperadmin = (rolesDelActor ?? []).includes('superadmin');

    return ROLES.filter((rol) => esSuperadmin || rol.valor !== 'superadmin');
}

/**
 * Validación previa del formulario de alta, con la misma política que el backend (CreateHospitalUser):
 * devuelve un mensaje por campo; un objeto vacío significa que se puede enviar.
 */
export function erroresDeAlta(datos) {
    const errores = {};
    const clave = datos?.password ?? '';

    if ((datos?.display_name ?? '').trim().length < 2) {
        errores.display_name = 'Escriba el nombre completo.';
    }
    if (! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test((datos?.email ?? '').trim())) {
        errores.email = 'Escriba un correo válido.';
    }
    if (! datos?.role_code) {
        errores.role_code = 'Elija el rol inicial.';
    }
    if (clave.length < 12 || ! /[A-Z]/.test(clave) || ! /[a-z]/.test(clave) || ! /\d/.test(clave) || ! /[^A-Za-z0-9]/.test(clave)) {
        errores.password = 'Mínimo 12 caracteres, con mayúscula, minúscula, número y símbolo.';
    }

    return errores;
}
