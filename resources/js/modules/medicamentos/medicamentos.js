/**
 * Reglas de la pantalla del catálogo de medicamentos (ASII-14), sin dependencias de Vue.
 * Replican las validaciones del dominio para avisar antes de enviar; el servidor sigue
 * siendo la autoridad y repite todas las validaciones y permisos.
 */
export const PERMISSIONS = {
    read: 'medicamentos.ver',
    register: 'medicamentos.registrar',
    changeValidity: 'medicamentos.cambiar_vigencia',
};

const CODE_PATTERN = /^[A-Z0-9][A-Z0-9-]{1,19}$/;
const TEXT_FIELDS = { name: 'Nombre', active_ingredient: 'Principio activo', pharmacological_group: 'Grupo farmacológico' };

/** Errores por campo del formulario de alta; un objeto vacío significa que puede enviarse. */
export function validateDraft(draft) {
    const errors = {};
    const code = String(draft.code ?? '').trim().toUpperCase();
    if (!CODE_PATTERN.test(code)) {
        errors.code = 'Código: letras, dígitos y guiones, de 2 a 20 caracteres.';
    }
    for (const [field, label] of Object.entries(TEXT_FIELDS)) {
        const length = String(draft[field] ?? '').trim().length;
        if (length < 2 || length > 120) {
            errors[field] = `${label}: de 2 a 120 caracteres.`;
        }
    }
    return errors;
}

/** El término se recorta; el servidor admite de 2 a 60 caracteres. */
export function searchTermError(term) {
    const length = String(term ?? '').trim().length;
    return length >= 2 && length <= 60 ? null : 'Escriba de 2 a 60 caracteres para buscar.';
}

/** Mensaje seguro según el estado HTTP; nunca muestra detalles internos del servidor. */
export function failureMessage(status, body = {}) {
    if (status === 422 && body.message) return body.message;
    if (status === 409) return 'Ya existe un medicamento con ese código en este hospital.';
    if (status === 404) return 'El medicamento no existe en este hospital.';
    if (status === 401) return 'Su sesión no es válida. Inicie sesión de nuevo.';
    if (status === 403) return 'No tiene permiso para esta operación en el hospital activo.';
    if (status === 429) return 'Hay demasiadas solicitudes al catálogo. Espere un minuto e intente de nuevo.';
    return 'El catálogo no está disponible. No se confirmó la operación; intente de nuevo.';
}

/** Solo los errores de disponibilidad, de red o de límite de tasa se pueden reintentar con los mismos datos. */
export function isRetryable(status) {
    return status === 0 || status === undefined || status === 429 || status >= 500;
}

export function validityLabel(validity) {
    return validity === 'active' ? 'Vigente' : 'No vigente';
}

export function nextValidity(validity) {
    return validity === 'active' ? 'inactive' : 'active';
}

/** Regla R8 (Semana 8): el diálogo repite el registro elegido y nombra la acción antes de enviar el cambio. */
export function validityConfirmation(medication) {
    const target = nextValidity(medication.validity);
    const verb = target === 'inactive' ? 'Inactivar' : 'Activar';
    return {
        title: `${verb} ${medication.code}`,
        action: `${verb} ${medication.code}`,
        target: validityLabel(target),
    };
}
