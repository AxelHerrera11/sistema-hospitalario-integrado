/**
 * Utilidades puras de la pantalla de médicos y especialidades (ASII-04).
 *
 * Se separan del componente para verificarlas con `node` sin navegador. Validan lo mismo que
 * el servidor para avisar antes de enviar; la autoridad de las reglas y de los permisos
 * sigue en el backend, que vuelve a validar todo.
 */

export const PERMISSIONS = {
    availability: 'medicos.ver_disponibilidad',
    register: 'medicos.registrar',
    associate: 'medicos.asociar_especialidad',
};

/** Fecha ISO `AAAA-MM-DD` que existe en el calendario (rechaza 2026-02-30). */
export function isRealDate(value) {
    if (typeof value !== 'string' || ! /^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return false;
    }

    const date = new Date(`${value}T00:00:00Z`);

    return ! Number.isNaN(date.getTime()) && date.toISOString().startsWith(value);
}

/** @returns {string} mensaje de error o cadena vacía si la consulta es válida */
export function availabilityError({ specialty, date }) {
    if (! String(specialty ?? '').trim()) {
        return 'Indica el código de la especialidad.';
    }

    return isRealDate(date) ? '' : 'Indica una fecha válida (AAAA-MM-DD).';
}

/** @returns {Record<string, string>} errores por campo; vacío si el alta es válida */
export function doctorErrors({ local_user_ref_id, license_number, specialty_code, valid_from }) {
    const errors = {};
    const required = { local_user_ref_id, license_number, specialty_code };

    for (const [field, value] of Object.entries(required)) {
        if (! String(value ?? '').trim()) {
            errors[field] = 'Este campo es obligatorio.';
        }
    }

    if (! isRealDate(valid_from)) {
        errors.valid_from = 'Indica una fecha válida (AAAA-MM-DD).';
    }

    return errors;
}

/** @returns {Record<string, string>} errores por campo; `valid_to` es opcional pero debe ser posterior a `valid_from` */
export function associationErrors({ doctor_id, specialty_code, valid_from, valid_to }) {
    const errors = {};

    if (! String(doctor_id ?? '').trim()) {
        errors.doctor_id = 'Indica el identificador del médico.';
    }

    if (! String(specialty_code ?? '').trim()) {
        errors.specialty_code = 'Este campo es obligatorio.';
    }

    if (! isRealDate(valid_from)) {
        errors.valid_from = 'Indica una fecha válida (AAAA-MM-DD).';
    }

    if (valid_to) {
        if (! isRealDate(valid_to)) {
            errors.valid_to = 'Indica una fecha válida (AAAA-MM-DD).';
        } else if (valid_to <= valid_from) {
            errors.valid_to = 'La vigencia debe terminar después de empezar.';
        }
    }

    return errors;
}

const MESSAGES = {
    LICENSE_ALREADY_REGISTERED: 'Esa licencia ya está registrada.',
    SPECIALTY_INACTIVE: 'La especialidad no existe o está inactiva.',
    SPECIALTY_ALREADY_ASSOCIATED: 'El médico ya tiene esa especialidad en ese periodo.',
    PRIMARY_SPECIALTY_CONFLICT: 'El médico ya tiene otra especialidad principal en ese periodo.',
    PERIOD_OUTSIDE_PROFILE: 'La vigencia queda fuera de la vigencia del perfil del médico.',
    INVALID_PERIOD: 'La vigencia no es válida.',
    INVALID_DATE_RANGE: 'La fecha no es válida.',
    DOCTOR_NOT_FOUND: 'No existe un médico activo con ese identificador.',
    PAYLOAD_INVALID: 'Revisa los datos enviados.',
    ACTOR_NOT_PROVISIONED: 'Tu usuario aún no está registrado en este hospital.',
};

/** Mensaje seguro por código de error; nunca muestra detalle del servidor. */
export function failureMessage(code, status) {
    if (MESSAGES[code]) {
        return MESSAGES[code];
    }

    if (status === 401) {
        return 'Inicia sesión para continuar.';
    }

    if (status === 403) {
        return 'No tienes permiso para esta operación.';
    }

    if (status === 503 || status >= 500 || code === 'DOCTOR_PERSISTENCE_ERROR') {
        return 'El servicio de médicos no está disponible. Intenta de nuevo.';
    }

    return 'No se pudo completar la operación.';
}

/** Solo los fallos del servidor justifican un reintento; un error de datos o de permiso, no. */
export function isRetryable(status) {
    return status === undefined || status >= 500;
}

/** Filas de la tabla de disponibilidad: estado legible y franjas, sin inventar horarios. */
export function availabilityRows(result) {
    return (result?.doctors ?? []).map((doctor) => ({
        id: doctor.doctor_id,
        estado: doctor.status === 'SCHEDULE_NOT_CONFIGURED'
            ? 'Sin horario configurado'
            : doctor.slots.length ? 'Con franjas libres' : 'Sin franjas libres',
        franjas: doctor.slots,
    }));
}
