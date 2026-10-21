/**
 * Utilidades puras de la pantalla de ocupación (ASII-09).
 *
 * Se separan del componente para poder verificarlas con `node` sin navegador
 * ni dependencias: el módulo solo formatea y decide qué mostrar; la autoridad
 * de los datos y de los permisos sigue en el backend.
 */

/** Etiquetas de las métricas globales, en el orden en que se muestran. */
export const METRICAS_GLOBAL = [
    { clave: 'total_beds', etiqueta: 'Camas del inventario' },
    { clave: 'occupied', etiqueta: 'Ocupadas en el corte' },
    { clave: 'available', etiqueta: 'Disponibles' },
    { clave: 'maintenance', etiqueta: 'En mantenimiento' },
    { clave: 'inactive', etiqueta: 'Inactivas' },
    { clave: 'unverified_occupied', etiqueta: 'Ocupadas sin estancia' },
    { clave: 'inconsistencies', etiqueta: 'Inconsistencias' },
];

/** @returns {{clave: string, etiqueta: string, valor: string|number}[]} */
export function metricasGlobales(global) {
    return METRICAS_GLOBAL.map(({ clave, etiqueta }) => ({
        clave,
        etiqueta,
        valor: global?.[clave] ?? 0,
    }));
}

/** Tasa de ocupación legible; `null` significa que no hay camas operativas. */
export function tasaLegible(tasa) {
    if (tasa === null || tasa === undefined) {
        return 'N/A';
    }

    return `${Number(tasa).toFixed(2)} %`;
}

/** Una fotografía con corte anterior al instante indicado está desactualizada. */
export function estaDesactualizada(cutoffAt, ahora, minutosMaximos = 15) {
    if (! cutoffAt) {
        return true;
    }

    const corte = new Date(cutoffAt).getTime();

    if (Number.isNaN(corte)) {
        return true;
    }

    return ahora.getTime() - corte > minutosMaximos * 60 * 1000;
}

/** Mensaje seguro por código de error; nunca muestra detalle del servidor. */
export function mensajeDeError(codigo, estado) {
    if (codigo === 'INVALID_CUTOFF') {
        return 'El corte solicitado no es válido.';
    }

    if (codigo === 'OCCUPANCY_UNAVAILABLE') {
        return 'La base de ocupación no está disponible. Se conserva la última fotografía.';
    }

    if (estado === 401) {
        return 'Inicia sesión para continuar.';
    }

    if (estado === 403) {
        return 'No tienes permiso para consultar la ocupación.';
    }

    return 'No se pudo consultar la ocupación.';
}
