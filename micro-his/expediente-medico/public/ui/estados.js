// Lógica pura de la interfaz del expediente (sin DOM): valida la entrada y traduce cada respuesta del API
// (openapi.yaml del módulo) a un estado de pantalla. La prueba node tests/ui-estados.test.mjs la ejecuta.

/** Mismo patrón que PatientSummary::CODE en el backend: 1 a 40 caracteres, letras, números, «-» y «_». */
export const PATRON_CODIGO = /^[A-Za-z0-9][A-Za-z0-9_-]{0,39}$/;

/** Reintentos manuales permitidos ante un 503 o un fallo de red; después se indica avisar a soporte. */
export const MAX_REINTENTOS = 3;

export function validarCodigo(valor) {
    const codigo = String(valor ?? '').trim();
    if (codigo === '') return { valido: false, codigo, mensaje: 'Escriba el código del paciente (por ejemplo, P-0001).' };
    if (!PATRON_CODIGO.test(codigo)) {
        return { valido: false, codigo, mensaje: 'El código solo admite letras, números, guion y guion bajo, con un máximo de 40 caracteres.' };
    }
    return { valido: true, codigo, mensaje: '' };
}

const DENEGADO = 'denegado';
const ERROR = 'error';

/** Mensajes por código estable del API. Ninguno repite datos del paciente. */
const MENSAJES = {
    IDENTIDAD_REQUERIDA: [ERROR, 'Sesión no válida', 'No se identificó al usuario. Vuelva a iniciar sesión.', false],
    TENANT_REQUERIDO: [ERROR, 'Falta el hospital', 'Seleccione el hospital en el que trabaja.', false],
    CODIGO_PACIENTE_INVALIDO: [ERROR, 'Código no válido', 'Revise el código del paciente: letras, números y guion, hasta 40 caracteres.', false],
    ACCESO_DENEGADO: [DENEGADO, 'Acceso denegado', 'Su rol no tiene permiso para esta acción en este hospital. Si cree que es un error, comunique el código de seguimiento a su jefatura.', false],
    SIN_VINCULO_ASISTENCIAL: [DENEGADO, 'Sin vínculo asistencial', 'Solo el personal asignado a un episodio activo del paciente puede ver su expediente. El intento quedó registrado.', false],
    EXPEDIENTE_NO_ABIERTO: ['vacio', 'Sin expediente abierto', 'El paciente todavía no tiene expediente en este hospital. Si corresponde, solicite su apertura.', false],
    PACIENTE_NO_ENCONTRADO: [ERROR, 'Paciente no encontrado', 'No existe un paciente con ese código en este hospital. Verifique el código en el módulo de Pacientes.', false],
    PACIENTE_NO_ACTIVO: [ERROR, 'No se puede abrir', 'El paciente no está activo; no se abre un expediente nuevo.', false],
    HOSPITAL_NO_DISPONIBLE: [ERROR, 'Hospital no disponible', 'La base de datos de este hospital no responde. Reintente en unos minutos.', true],
    EXPEDIENTE_NO_DISPONIBLE: [ERROR, 'Servicio no disponible', 'El expediente no está disponible en este momento. Reintente en unos minutos.', true],
    AUDITORIA_NO_DISPONIBLE: [ERROR, 'Datos protegidos', 'Por seguridad no se muestra el expediente: la bitácora de auditoría no respondió. Reintente; si continúa, avise a soporte.', true],
};

/**
 * Traduce (operación, estado HTTP, cuerpo JSON) a un estado de pantalla:
 * { tipo: 'exito'|'vacio'|'denegado'|'error', titulo, mensaje, puedeReintentar, traza?, datos? }.
 * estado 0 = sin respuesta (red). Abrir es idempotente en el backend, así que reintentar una apertura es seguro.
 */
export function interpretar(operacion, estado, cuerpo = {}) {
    if (operacion === 'consultar' && estado === 200) {
        const episodios = Array.isArray(cuerpo.episodes) ? cuerpo.episodes : [];
        return episodios.length === 0
            ? { tipo: 'vacio', titulo: 'Expediente sin episodios', mensaje: 'El expediente está abierto pero todavía no tiene episodios clínicos.', puedeReintentar: false, datos: cuerpo }
            : { tipo: 'exito', titulo: 'Expediente longitudinal', mensaje: `${episodios.length} episodio(s) registrados.`, puedeReintentar: false, datos: cuerpo };
    }
    if (operacion === 'abrir' && (estado === 201 || estado === 200)) {
        const numero = cuerpo.record_number ?? '';
        return estado === 201
            ? { tipo: 'exito', titulo: 'Expediente abierto', mensaje: `Se abrió el expediente ${numero}.`, puedeReintentar: false, datos: cuerpo }
            : { tipo: 'exito', titulo: 'El expediente ya existía', mensaje: `El paciente ya tenía el expediente ${numero}; no se creó otro.`, puedeReintentar: false, datos: cuerpo };
    }
    if (estado === 0) {
        const mensaje = operacion === 'abrir'
            ? 'No hubo respuesta del servidor. Reintentar es seguro: si la apertura ya se hizo, se mostrará el mismo expediente.'
            : 'No hubo respuesta del servidor. Revise su conexión y reintente.';
        return { tipo: ERROR, titulo: 'Sin conexión', mensaje, puedeReintentar: true };
    }
    const definido = MENSAJES[cuerpo?.code];
    if (definido) {
        const [tipo, titulo, mensaje, puedeReintentar] = definido;
        return { tipo, titulo, mensaje, puedeReintentar, traza: cuerpo.trace_id ?? null };
    }
    return { tipo: ERROR, titulo: 'Error inesperado', mensaje: `No se pudo completar la operación (código ${estado}).`, puedeReintentar: false };
}

/** ¿Se ofrece otro reintento? Cada error reintentable tiene salida: al llegar al máximo, se pide avisar a soporte. */
export function siguienteReintento(intentos) {
    return intentos < MAX_REINTENTOS
        ? { permitido: true, aviso: `Intento ${intentos + 1} de ${MAX_REINTENTOS}.` }
        : { permitido: false, aviso: 'Se agotaron los reintentos. Avise a soporte e indique la hora del error.' };
}

const TIPOS = { outpatient: 'Consulta externa', emergency: 'Emergencia', inpatient: 'Hospitalización' };
const ESTADOS = { open: 'Abierto', closed: 'Cerrado', cancelled: 'Cancelado' };

const SEXOS = { female: 'Femenino', male: 'Masculino', other: 'Otro', unknown: 'No registrado' };
const PACIENTE = { active: 'Activo', inactive: 'Inactivo', deceased: 'Fallecido' };

// Los valores del API son los códigos de las tablas (en inglés); la pantalla siempre muestra su etiqueta (DEF-EXP-02).
export const etiquetaTipo = (tipo) => TIPOS[tipo] ?? 'Otro';
export const etiquetaEstado = (estado) => ESTADOS[estado] ?? 'Desconocido';
export const etiquetaSexo = (sexo) => SEXOS[sexo] ?? 'No registrado';
export const etiquetaPaciente = (estado) => PACIENTE[estado] ?? 'Desconocido';

/**
 * Fecha y hora legibles en es-GT CON la zona horaria (H-02): en una línea de tiempo clínica la hora sin zona es
 * ambigua. zona = undefined usa la del navegador (las pruebas fijan una). Vacío si no hay fecha válida.
 */
export function formatearFecha(iso, zona = undefined) {
    if (!iso) return '';
    const fecha = new Date(iso);
    return Number.isNaN(fecha.getTime()) ? ''
        : fecha.toLocaleString('es-GT', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZoneName: 'short', timeZone: zona });
}

/** Fecha de nacimiento AAAA-MM-DD → DD/MM/AAAA sin convertir zona (es una fecha, no un instante) (H-03). */
export function formatearNacimiento(fecha) {
    const partes = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(fecha ?? ''));
    return partes ? `${partes[3]}/${partes[2]}/${partes[1]}` : '';
}

/** ¿Hay que quitar el expediente de la pantalla? Sí, si el código escrito ya no es el del expediente mostrado (H-01). */
export function debeOcultar(codigoMostrado, valorCampo) {
    return codigoMostrado !== null && String(valorCampo ?? '').trim() !== codigoMostrado;
}
