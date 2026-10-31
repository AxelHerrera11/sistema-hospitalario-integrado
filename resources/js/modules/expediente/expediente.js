/**
 * Lógica pura de la pantalla del expediente médico (ASII-25) dentro de la aplicación.
 *
 * Reutiliza los estados del prototipo del micro-HIS (validación del código, mensajes por código, reintentos y
 * etiquetas) y solo agrega los códigos que pone la capa Laravel delante del micro-HIS: RBAC, guard de la base
 * hospitalaria y referencia local del usuario. La autoridad sigue en el backend.
 */
import { interpretar } from '../../../../micro-his/expediente-medico/public/ui/estados.js';

export {
    debeOcultar, etiquetaEstado, etiquetaPaciente, etiquetaSexo, etiquetaTipo, formatearFecha, formatearNacimiento,
    siguienteReintento, validarCodigo,
} from '../../../../micro-his/expediente-medico/public/ui/estados.js';

/** Códigos de la capa Laravel que significan lo mismo que uno del micro-HIS. */
const EQUIVALENTES = {
    RBAC_DENIED: 'ACCESO_DENEGADO',
    HOSPITAL_DATABASE_TENANT_MISMATCH: 'HOSPITAL_NO_DISPONIBLE',
    HOSPITAL_DATABASE_TENANT_UNCONFIGURED: 'HOSPITAL_NO_DISPONIBLE',
    RBAC_UNAVAILABLE: 'EXPEDIENTE_NO_DISPONIBLE',
};

/** Códigos propios de la aplicación: [tipo, título, mensaje, puedeReintentar]. Ninguno repite datos del paciente. */
const PROPIOS = {
    ACTOR_SIN_REFERENCIA_LOCAL: ['denegado', 'Usuario no habilitado en este hospital', 'Su usuario no tiene una referencia activa en este hospital. Solicite a administración que lo habilite.', false],
    TENANT_MISMATCH: ['denegado', 'Hospital distinto', 'La consulta no corresponde al hospital de su sesión. Vuelva a seleccionar el hospital.', false],
};

/** Código estable de la respuesta: `code` (micro-HIS, RBAC) o `error` (guard y middlewares). */
export function codigoDeRespuesta(cuerpo) {
    return cuerpo?.code ?? (typeof cuerpo?.error === 'string' ? cuerpo.error : null);
}

/**
 * Traduce (operación, estado HTTP, cuerpo) al estado de pantalla del prototipo:
 * { tipo: 'exito'|'vacio'|'denegado'|'error', titulo, mensaje, puedeReintentar, traza?, datos? }.
 */
export function estadoDesdeApi(operacion, estado, cuerpo = {}) {
    if (estado === 401) {
        return { tipo: 'error', titulo: 'Sesión vencida', mensaje: 'Vuelva a iniciar sesión para continuar.', puedeReintentar: false };
    }
    const codigo = codigoDeRespuesta(cuerpo);
    if (PROPIOS[codigo]) {
        const [tipo, titulo, mensaje, puedeReintentar] = PROPIOS[codigo];

        return { tipo, titulo, mensaje, puedeReintentar, traza: cuerpo.trace_id ?? null };
    }

    return interpretar(operacion, estado, EQUIVALENTES[codigo] ? { ...cuerpo, code: EQUIVALENTES[codigo] } : (cuerpo ?? {}));
}
