/**
 * Utilidades puras de la pantalla del catálogo de pruebas de laboratorio (ASII-17).
 *
 * Se separan del componente para verificarlas con `node`, sin navegador. Las reglas de rangos y de vigencia siguen en el
 * dominio (LabTest::register) y en `lab_tests`; aquí solo se adelanta la validación y se decide qué mostrar.
 */

export const TIPOS_RESULTADO = [
    { valor: 'numeric', etiqueta: 'Numérico' },
    { valor: 'qualitative', etiqueta: 'Cualitativo' },
    { valor: 'text', etiqueta: 'Texto' },
];

const RANGOS = ['critical_low', 'reference_low', 'reference_high', 'critical_high'];
const texto = (valor) => (typeof valor === 'string' ? valor.trim() : '');
const numero = (valor) => (valor === '' || valor === null || valor === undefined || Number.isNaN(Number(valor)) ? null : Number(valor));

/** Rango de referencia legible («70–100 mg/dL») para numéricos; «—» para los demás. */
export function rangoLegible(prueba) {
    if (prueba?.result_type !== 'numeric') {
        return '—';
    }

    return `${prueba.reference_low}–${prueba.reference_high} ${prueba.unit ?? ''}`.trim();
}

/** Mismo orden de reglas que LabTest::register; devuelve el campo inválido o null. */
export function campoInvalido(campos, hoy) {
    for (const campo of ['code', 'name', 'specimen_type', 'result_type', 'valid_from']) {
        if (texto(campos[campo]) === '') {
            return campo;
        }
    }
    if (! /^[A-Z0-9][A-Z0-9._-]{1,31}$/.test(texto(campos.code).toUpperCase())) {
        return 'code';
    }
    if (! (numero(campos.min_volume_ml) > 0)) {
        return 'min_volume_ml';
    }
    if (campos.result_type === 'numeric') {
        const [cl, rl, rh, ch] = RANGOS.map((r) => numero(campos[r]));
        if (texto(campos.unit) === '' || [cl, rl, rh, ch].includes(null) || ! (cl < rl && rl <= rh && rh < ch)) {
            return texto(campos.unit) === '' ? 'unit' : 'reference_low';
        }
    }
    if (campos.valid_from < hoy) {
        return 'valid_from';
    }

    return null;
}

/** Cuerpo del POST: números como números y sin rangos si el resultado no es numérico; nunca envía el autor. */
export function cuerpoRegistro(campos) {
    const cuerpo = {
        code: texto(campos.code).toUpperCase(), name: texto(campos.name), specimen_type: texto(campos.specimen_type),
        min_volume_ml: numero(campos.min_volume_ml), result_type: campos.result_type, valid_from: campos.valid_from,
    };
    if (campos.result_type === 'numeric') {
        cuerpo.unit = texto(campos.unit);
        RANGOS.forEach((r) => { cuerpo[r] = numero(campos[r]); });
    }

    return cuerpo;
}

const MENSAJES = {
    FIELD_REQUIRED: 'Complete los campos obligatorios.',
    CODE_INVALID: 'El código usa letras, números, punto, guion o guion bajo (2 a 32).',
    SPECIMEN_REQUIREMENT_INVALID: 'El volumen mínimo debe ser mayor que cero.',
    RANGES_INCOHERENT: 'Los rangos deben cumplir crítico bajo < referencia baja ≤ referencia alta < crítico alto, con unidad.',
    RESULT_DEFINITION_INVALID: 'Solo los resultados numéricos llevan rangos.',
    VALIDITY_INVALID: 'La vigencia no puede empezar antes de hoy.',
    LAB_TEST_VALIDITY_OVERLAP: 'Ya hay una versión vigente de esa prueba.',
    ACTOR_WITHOUT_LOCAL_REFERENCE: 'Su usuario no está habilitado en este hospital para administrar el catálogo.',
    HOSPITAL_DATABASE_TENANT_MISMATCH: 'Este hospital no está disponible en esta instancia.',
    DATE_INVALID: 'La fecha de consulta no es válida.',
    TEXT_TOO_LONG: 'El texto supera la longitud permitida (nombre 160, muestra 40, unidad 32).',
};

const REGLA_POR_CAMPO = { code: 'CODE_INVALID', min_volume_ml: 'SPECIMEN_REQUIREMENT_INVALID', unit: 'RANGES_INCOHERENT',
    reference_low: 'RANGES_INCOHERENT', valid_from: 'VALIDITY_INVALID' };

/** Mensaje de la validación local para el campo que devolvió campoInvalido. */
export function mensajeDeCampo(campo, campos = {}) {
    const vacio = texto(campos[campo]) === '' && ['code', 'name', 'specimen_type', 'result_type', 'valid_from'].includes(campo);

    return MENSAJES[vacio ? 'FIELD_REQUIRED' : REGLA_POR_CAMPO[campo]] ?? MENSAJES.FIELD_REQUIRED;
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
        return 'El catálogo de laboratorio no está disponible. Intente de nuevo en unos segundos.';
    }

    return 'No se pudo completar la operación.';
}

/** El código viene en `error.code` (API del catálogo) o en `error`/`code` (guard y middlewares). */
export function codigoDeError(datos) {
    return datos?.error?.code ?? (typeof datos?.error === 'string' ? datos.error : null) ?? datos?.code ?? null;
}
