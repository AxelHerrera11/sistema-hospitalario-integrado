/**
 * Utilidades puras de la pantalla de inicio (ASII-26). Se separan del componente para probarlas con
 * `node` sin navegador; la autoridad de datos y permisos sigue en el backend.
 */

const ETIQUETAS_ESTADO = { scheduled: 'Programadas', confirmed: 'Confirmadas', completed: 'Atendidas', cancelled: 'Canceladas' };

/** Estado de la página a partir del resumen recibido: cargando, vacío (sin tarjetas) o listo. */
export function estadoDePagina(resumen, cargando) {
    if (cargando) return 'cargando';
    return resumen?.tarjetas?.length ? 'listo' : 'vacio';
}

/** Filas legibles del conteo por estado, en orden fijo. */
export function filasPorEstado(porEstado = {}) {
    return Object.entries(ETIQUETAS_ESTADO).map(([clave, etiqueta]) => ({ clave, etiqueta, valor: porEstado[clave] ?? 0 }));
}

/** Texto del indicador: nunca depende solo del color (WCAG 1.4.1). */
export function textoIndicador(indicador) {
    const sentido = indicador.sentido === 'menor_mejor' ? 'menor es mejor' : 'mayor es mejor';
    const estado = indicador.cumple_meta ? '✔ Cumple' : '✖ No cumple';
    return `${estado}: ${indicador.valor} (meta ${indicador.meta}, ${sentido})`;
}

/** Mensaje accionable por código de error del sobre común. */
export function mensajeDeError(status) {
    if (status === 401) return 'Su sesión expiró. Inicie sesión de nuevo.';
    if (status === 503) return 'No se pudo cargar el tablero. Sus datos no se perdieron; intente de nuevo.';
    return 'Ocurrió un error inesperado al cargar el tablero.';
}

/** Fecha AAAA-MM-DD en la zona del navegador; `toISOString()` daría el día siguiente por la noche (UTC-6). */
export function fechaLocal(fecha = new Date()) {
    const dos = (n) => String(n).padStart(2, '0');
    return `${fecha.getFullYear()}-${dos(fecha.getMonth() + 1)}-${dos(fecha.getDate())}`;
}

/** Solo se enlaza el detalle si la ruta existe en el router; evita un enlace roto mientras el módulo no publique su pantalla. */
export function enlaceDisponible(resolver, ruta) {
    return Boolean(ruta) && resolver(ruta).matched.length > 0;
}
