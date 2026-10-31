/**
 * Utilidades puras de la bandeja de alertas críticas (ASII-21).
 *
 * Se separan del componente para verificarlas con `node`, sin navegador. El acuse exige una lectura de verificación
 * (read-back): quien acusa vuelve a escribir el valor y confirma paciente y prueba; el dominio compara y decide.
 */

/** «Bajo el umbral crítico» o «sobre el umbral crítico». */
export function direccionLegible(direccion) {
    return direccion === 'low' ? 'bajo el umbral crítico' : 'sobre el umbral crítico';
}

/** Minutos hasta el vencimiento (negativo si ya venció). */
export function minutosRestantes(dueAt, ahora) {
    const vence = new Date(dueAt).getTime();

    return Number.isNaN(vence) ? 0 : Math.round((vence - ahora.getTime()) / 60000);
}

/** Texto del vencimiento: las vencidas se anuncian primero y sin ambigüedad. */
export function vencimientoLegible(dueAt, ahora) {
    const minutos = minutosRestantes(dueAt, ahora);

    return minutos < 0 ? `Vencida hace ${-minutos} min` : `Vence en ${minutos} min`;
}

/** Primeros 8 caracteres de un UUID, para mostrar sin exponer el identificador completo. */
export function idCorto(uuid) {
    return typeof uuid === 'string' ? uuid.slice(0, 8) : '';
}

/** La lectura es válida si se confirmó paciente y prueba y el valor escrito es un número. */
export function lecturaCompleta(lectura) {
    return lectura?.confirmado === true && lectura.valor !== '' && lectura.valor !== null && ! Number.isNaN(Number(lectura.valor));
}

/** Cuerpo del acuse: paciente y prueba de la alerta confirmada y el valor tal como lo escribió quien acusa. */
export function cuerpoAcuse(alerta, lectura) {
    return { read_back: { patient_id: alerta.local_patient_id, lab_test_id: alerta.lab_test_id, value: Number(lectura.valor) } };
}

const MENSAJES = {
    READ_BACK_MISMATCH: 'El valor leído no coincide con la alerta. Vuelva a leer el resultado.',
    NO_CARE_RELATION: 'No tiene una relación de cuidado vigente con este paciente.',
    ALERT_ALREADY_ACKNOWLEDGED: 'Otra persona ya acusó esta alerta.',
    ALERT_NOT_FOUND: 'La alerta ya no existe en este hospital.',
    ACTOR_WITHOUT_LOCAL_REFERENCE: 'Su usuario no está habilitado en este hospital.',
    HOSPITAL_DATABASE_TENANT_MISMATCH: 'Este hospital no está disponible en esta instancia.',
};

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
        return 'Las alertas no están disponibles. Intente de nuevo en unos segundos y verifique por teléfono los críticos.';
    }

    return 'No se pudo completar la operación.';
}

/** El código viene en `error.code` (API de alertas) o en `error`/`code` (guard y middlewares). */
export function codigoDeError(datos) {
    return datos?.error?.code ?? (typeof datos?.error === 'string' ? datos.error : null) ?? datos?.code ?? null;
}

/** Tras un 409 la alerta ya no está pendiente: hay que recargar la bandeja. */
export function debeRecargar(codigo) {
    return codigo === 'ALERT_ALREADY_ACKNOWLEDGED' || codigo === 'ALERT_NOT_FOUND';
}
