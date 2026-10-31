// Integración de traslados y altas en la SPA (Semana 16). La interpretación de cada respuesta es la del micro-HIS
// (public/ui/mensajes.js): se reutiliza para que la SPA y el prototipo nunca discrepen sobre éxito, rechazo o incierto.
export { interpretar, validarConsulta, validarTraslado } from '../../../../micro-his/traslados-altas/public/ui/mensajes.js';

/** Permisos propuestos (rbac.pending): hasta que RBAC los active, `can` los deniega y el backend responde 403. */
export const PERMISOS = { trasladar: 'traslados.registrar', darAlta: 'altas.registrar', consultar: 'traslados.ver' };

/**
 * Traduce un error de axios al par { estado, cuerpo } que entiende interpretar(). Sin respuesta del servidor, una
 * lectura simplemente falló (503), pero un POST pudo haberse confirmado: se informa como tiempo agotado (incierto).
 */
export function respuestaDeError(error, metodo) {
    if (error?.response) return { estado: error.response.status, cuerpo: error.response.data ?? {} };
    return metodo === 'post' ? { estado: 0, cuerpo: { motivo: 'tiempo_agotado' } } : { estado: 503, cuerpo: {} };
}
