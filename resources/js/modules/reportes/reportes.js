/**
 * Utilidades puras de la pantalla de reportes (ASII-23, semana 8).
 *
 * Se separan del componente para poder verificarlas con `node` sin navegador
 * ni dependencias: el módulo solo formatea y decide qué mostrar; la autoridad
 * de los datos y del cálculo sigue en el backend (IND-RA-01/TCI-1.0).
 */

/** Estados del contrato que se publican sin porcentaje y con qué leyenda. */
export const ESTADOS_SIN_VALOR = {
    SIN_DATOS_EN_PERIODO: 'No hubo hospitalizaciones cerradas ni canceladas en el periodo.',
};

/** Ayuda del intervalo [desde, hasta): evita que se espere incluido el día de «Hasta». */
export const AYUDA_INTERVALO = 'Se incluye el día de «Desde». El día de «Hasta» no se incluye: para contar todo enero, indica del 01/01 al 01/02.';

/** Etiqueta legible del indicador publicado por el backend. */
export const INDICADOR = { codigo: 'IND-RA-01', formula: 'TCI-1.0' };

/** Percentage legible; `null` significa estado publicado sin cálculo. */
export function porcentajeLegible(porcentaje) {
    if (porcentaje === null || porcentaje === undefined) {
        return 'N/A';
    }

    return `${Number(porcentaje).toFixed(2)} %`;
}

/** Leyenda del resultado: calculo completado o estado del contrato. */
export function leyendaDelResultado(resultado) {
    const estado = resultado?.state;

    if (estado && ESTADOS_SIN_VALOR[estado]) {
        return ESTADOS_SIN_VALOR[estado];
    }

    if (estado) {
        return 'El resultado no se puede calcular con los datos del periodo.';
    }

    return 'Cancelados sobre hospitalizaciones finalizadas (TCI-1.0).';
}

/** Valida el filtro antes de pedir; devuelve mensaje o cadena vacía. */
export function errorDeFiltros(filtros) {
    const { desde, hasta } = filtros ?? {};

    if (! desde || ! hasta) {
        return 'Indica desde y hasta.';
    }

    const inicio = new Date(desde);
    const fin = new Date(hasta);

    // El navegador convierte 2026-02-31 en 3 de marzo: la fecha debe volver igual.
    if (Number.isNaN(inicio.getTime()) || Number.isNaN(fin.getTime())
        || inicio.toISOString().slice(0, 10) !== desde || fin.toISOString().slice(0, 10) !== hasta) {
        return 'Las fechas deben ser válidas.';
    }

    if (inicio >= fin) {
        return 'Desde debe ser anterior a hasta.';
    }

    return '';
}

/** Trazabilidad mínima para mostrar al usuario sin datos personales. */
export function trazabilidad(resultado) {
    if (! resultado) {
        return '';
    }

    return `${INDICADOR.codigo} (${INDICADOR.formula})${resultado.computed_at ? ` · calculado ${resultado.computed_at}` : ''}`;
}

/** Mensaje seguro por código de error; nunca muestra detalle del servidor. */
export function mensajeDeError(codigo, estado) {
    if (codigo === 'entrada_invalida' || codigo === 'periodo_invalido') {
        return 'Revisa el periodo indicado.';
    }

    if (codigo === 'HOSPITAL_DATABASE_TENANT_MISMATCH') {
        return 'Este hospital no está disponible en esta instalación.';
    }

    if (codigo === 'REPORTES_UNAVAILABLE') {
        return 'La base de episodios no está disponible. Intenta más tarde.';
    }

    if (estado === 401) {
        return 'Inicia sesión para continuar.';
    }

    if (estado === 403) {
        return 'No tienes permiso para consultar reportes.';
    }

    return 'No se pudo calcular el indicador.';
}
