/**
 * Utilidades puras de la pantalla de admisión (ASII-07).
 *
 * Se separan del componente para verificarlas con `node`, sin navegador. Las reglas (una cama por paciente, un paciente por
 * cama) y los permisos siguen en el backend; aquí solo se adelanta la validación y se decide qué mostrar.
 */

const texto = (valor) => (typeof valor === 'string' ? valor.trim() : '');

/** Tipos distintos de las camas disponibles, en orden alfabético, para el filtro. */
export function tiposDeCama(camas) {
    return [...new Set((Array.isArray(camas) ? camas : []).map((cama) => cama.type).filter(Boolean))].sort();
}

/** Mismo orden de reglas que AdmitPatient y Admission::admit; devuelve el campo inválido o null. */
export function campoInvalido(campos) {
    if (! campos.patient_id) {
        return 'patient_id';
    }
    if (! campos.bed_id) {
        return 'bed_id';
    }
    if (texto(campos.reason) === '') {
        return 'reason';
    }

    return null;
}

/** Cuerpo del POST: nunca envía actor, hospital ni fecha; el servidor los decide. */
export function cuerpoAdmision(campos) {
    return { patient_id: campos.patient_id, bed_id: campos.bed_id, reason: texto(campos.reason) };
}

const MENSAJES = {
    PATIENT_NOT_FOUND: 'El paciente no existe en este hospital.',
    BED_NOT_FOUND: 'La cama no existe en este hospital.',
    PATIENT_ALREADY_ADMITTED: 'El paciente ya tiene una hospitalización abierta.',
    BED_NOT_AVAILABLE: 'La cama ya no está disponible. Elija otra.',
    REASON_REQUIRED: 'Indique el motivo de ingreso.',
    BED_TYPE_MISMATCH: 'La cama no es del tipo requerido.',
    ACTOR_WITHOUT_LOCAL_REFERENCE: 'Su usuario no está habilitado en este hospital para admitir pacientes.',
    HOSPITAL_DATABASE_TENANT_MISMATCH: 'Este hospital no está disponible en esta instancia.',
};

const REGLA_POR_CAMPO = { patient_id: 'PATIENT_NOT_FOUND', bed_id: 'BED_NOT_FOUND', reason: 'REASON_REQUIRED' };

/** Mensaje de la validación local para el campo que devolvió campoInvalido. */
export function mensajeDeCampo(campo) {
    if (campo === 'patient_id') {
        return 'Busque primero al paciente.';
    }
    if (campo === 'bed_id') {
        return 'Seleccione una cama disponible.';
    }

    return MENSAJES[REGLA_POR_CAMPO[campo]] ?? 'Revise el formulario.';
}

/** Mensaje seguro por código de error; nunca muestra el detalle del servidor. */
export function mensajeDeError(codigo, estado) {
    if (MENSAJES[codigo]) {
        return MENSAJES[codigo];
    }
    if (estado === 401) {
        return 'Inicie sesión para continuar.';
    }
    if (estado === 403) {
        return 'No tiene permiso para esta acción.';
    }
    if (estado === 503) {
        return 'La base de admisiones no está disponible. Intente de nuevo en unos segundos.';
    }

    return 'No se pudo completar la operación.';
}

/** El código viene en `error.code` (API de admisión) o en `error`/`code` (guard y middlewares). */
export function codigoDeError(datos) {
    return datos?.error?.code ?? (typeof datos?.error === 'string' ? datos.error : null) ?? datos?.code ?? null;
}

/** Tras un 409 por cama tomada hay que recargar la lista: la cama elegida ya no sirve. */
export function debeRecargarCamas(codigo) {
    return codigo === 'BED_NOT_AVAILABLE' || codigo === 'BED_NOT_FOUND';
}
