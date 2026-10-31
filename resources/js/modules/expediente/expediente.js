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

/** «2 episodios (1 abierto)»: concordancia de número en el resumen. */
export function resumenEpisodios(total, abiertos) {
    const t = Number(total) || 0;
    const a = Number(abiertos) || 0;

    return `${t} ${t === 1 ? 'episodio' : 'episodios'} (${a} ${a === 1 ? 'abierto' : 'abiertos'})`;
}

/** Opciones «Médico · Especialidad»; el valor junta perfil y especialidad porque se asignan juntos. */
export function opcionesDeMedico(medicos) {
    return (medicos ?? []).map((m) => ({
        valor: `${m.doctor_profile_id}|${m.specialty_id}`,
        etiqueta: `${m.display_name} · ${m.specialty}`,
    }));
}

/** Validación previa del formulario de asignación; un objeto vacío significa que se puede enviar. */
export function erroresDeAsignacion(form) {
    const errores = {};
    if (! form?.care_episode_id) {
        errores.care_episode_id = 'Elija el episodio.';
    }
    if (! form?.medico) {
        errores.medico = 'Elija el médico y su especialidad.';
    }
    if ((form?.reason ?? '').trim().length < 3) {
        errores.reason = 'Escriba el motivo de la asignación.';
    }

    return errores;
}

const MENSAJES_ASIGNACION = {
    PACIENTE_NO_ENCONTRADO: 'No existe un paciente con ese código en este hospital.',
    CODIGO_PACIENTE_INVALIDO: 'El código del paciente no es válido.',
    EPISODIO_NO_DISPONIBLE: 'El episodio ya no está abierto. Vuelva a cargar los episodios.',
    MEDICO_NO_ELEGIBLE: 'El médico no tiene esa especialidad vigente.',
    ASIGNACION_DUPLICADA: 'Ese médico ya atiende el episodio.',
    ASIGNACION_NO_ENCONTRADA: 'La asignación ya no está activa.',
    ACTOR_SIN_REFERENCIA_LOCAL: 'Su usuario no tiene una referencia activa en este hospital.',
    AUDITORIA_NO_DISPONIBLE: 'No se pudo registrar en la bitácora; no se guardó ningún cambio. Intente de nuevo.',
    HOSPITAL_NO_DISPONIBLE: 'La base del hospital no está disponible. Intente de nuevo.',
    RBAC_DENIED: 'No tiene permiso para asignar médicos tratantes.',
};

/** Mensaje seguro para el panel de médicos tratantes; nunca muestra el detalle interno. */
export function mensajeDeAsignacion(codigo, estado) {
    if (estado === 401) {
        return 'Vuelva a iniciar sesión para continuar.';
    }

    return MENSAJES_ASIGNACION[codigo] ?? (estado === 422 ? 'Revise los datos de la asignación.' : 'No se pudo completar la operación.');
}
