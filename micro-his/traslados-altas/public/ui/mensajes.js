// PROTOTIPO (Semana 8): traduce una respuesta de la API del micro-HIS a un mensaje y a la siguiente acción permitida.
// Nunca muestra un éxito que la API no confirmó ni ofrece reintentar un resultado incierto.

const MOTIVOS = {
    NO_AUTORIZADO: 'Su usuario no puede realizar esta operación.',
    ESTANCIA_INEXISTENTE: 'La estancia no existe. Revise el identificador.',
    EPISODIO_NO_HOSPITALARIO: 'El episodio no es de hospitalización.',
    EPISODIO_NO_ABIERTO: 'El episodio ya está cerrado.',
    SIN_ESTANCIA_ABIERTA: 'El episodio no tiene una cama asignada.',
    ESTANCIA_NO_VIGENTE: 'Los datos cambiaron: la estancia ya no es la vigente. Vuelva a consultar.',
    MOMENTO_NO_POSTERIOR: 'La hora de la operación no es posterior al ingreso. Vuelva a intentarlo.',
    DESTINO_INEXISTENTE: 'La cama destino no existe.',
    DESTINO_IGUAL_ORIGEN: 'La cama destino es la misma cama actual.',
    DESTINO_OCUPADO: 'La cama destino está ocupada.',
    DESTINO_NO_DISPONIBLE: 'La cama destino no está disponible (mantenimiento o inactiva).',
};

/** @returns {{tipo: string, texto: string, puedeReintentar: boolean, debeConsultar: boolean}} */
export function interpretar(estado, cuerpo = {}) {
    const base = { puedeReintentar: false, debeConsultar: false };
    if (estado === 200 || estado === 201) {
        const texto = cuerpo.estancia_destino_id ? 'Traslado confirmado. La cama de origen quedó liberada.' : 'Alta confirmada. La cama quedó liberada.';
        return { ...base, tipo: 'exito', texto };
    }
    if (estado === 409 || estado === 403) {
        return { ...base, tipo: 'rechazo', texto: MOTIVOS[cuerpo.motivo] ?? 'La operación fue rechazada. No se guardó ningún cambio.' };
    }
    if (estado === 422 || estado === 400) {
        return { ...base, tipo: 'invalida', texto: `Revise el dato: ${cuerpo.campo ?? 'solicitud'}.` };
    }
    if (estado === 401) {
        return { ...base, tipo: 'invalida', texto: 'Indique su referencia de usuario.' };
    }
    if (estado === 503) {
        return { ...base, tipo: 'reintentar', texto: 'El servidor no confirmó la operación. No se guardó ningún cambio; puede reintentar.', puedeReintentar: true };
    }
    if (estado === 500 && cuerpo.resultado === 'incierto') {
        return { ...base, tipo: 'consultar', texto: 'No se sabe si la operación se guardó. Consulte el estado antes de reintentar.', debeConsultar: true };
    }
    return { ...base, tipo: 'error', texto: 'Error inesperado. No se confirmó la operación.' };
}

/** Validación local previa al envío (la API vuelve a validar). Devuelve el campo inválido o null. */
export function validarTraslado({ cama_destino_id: cama = '', motivo = '' }) {
    if (!/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(cama.trim())) return 'cama_destino_id';
    const texto = motivo.trim();
    return texto.length === 0 || texto.length > 500 ? 'motivo' : null;
}
