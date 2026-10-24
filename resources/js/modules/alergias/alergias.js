/**
 * Utilidades puras de la pantalla de alergias (ASII-12).
 *
 * Se separan del componente para verificarlas con `node`, sin navegador. La autoridad de las reglas clínicas y de
 * los permisos sigue en el backend; aquí solo se adelanta la validación y se decide qué mostrar.
 */

export const TIPOS = [
    { valor: 'allergy', etiqueta: 'Alergia' },
    { valor: 'intolerance', etiqueta: 'Intolerancia' },
    { valor: 'unknown', etiqueta: 'No determinado' },
];

export const SEVERIDADES = [
    { valor: 'mild', etiqueta: 'Leve' },
    { valor: 'moderate', etiqueta: 'Moderada' },
    { valor: 'severe', etiqueta: 'Grave' },
    { valor: 'life_threatening', etiqueta: 'Amenaza la vida' },
    { valor: 'unknown', etiqueta: 'No determinada' },
];

/** Banner por nivel. «unavailable» nunca se presenta como «sin alergias». */
const BANNERS = {
    none: { clase: 'none', rol: 'status' },
    warning: { clase: 'warning', rol: 'status' },
    critical: { clase: 'critical', rol: 'alert' },
    unavailable: { clase: 'unavailable', rol: 'alert' },
};

export function banner(nivel) {
    return BANNERS[nivel] ?? BANNERS.unavailable;
}

export function etiqueta(lista, valor) {
    return lista.find((opcion) => opcion.valor === valor)?.etiqueta ?? valor;
}

const texto = (valor) => (typeof valor === 'string' ? valor.trim() : '');

/** Cuerpo del POST: recorta y omite los textos vacíos, igual que el dominio. */
export function cuerpoRegistro(campos) {
    const cuerpo = { allergy_type: campos.allergy_type, severity: campos.severity };
    for (const clave of ['allergen_code', 'allergen_name', 'reaction']) {
        if (texto(campos[clave]) !== '') {
            cuerpo[clave] = texto(campos[clave]);
        }
    }

    return cuerpo;
}

/** Mismo orden de reglas que Allergy::register; devuelve el campo inválido o null. */
export function campoInvalido(campos) {
    if (texto(campos.allergen_code) === '' && texto(campos.allergen_name) === '') {
        return 'allergen_name';
    }
    if (! TIPOS.some((t) => t.valor === campos.allergy_type)) {
        return 'allergy_type';
    }
    if (! SEVERIDADES.some((s) => s.valor === campos.severity)) {
        return 'severity';
    }

    return null;
}

const MENSAJES = {
    PATIENT_NOT_FOUND: 'El paciente no existe en este hospital.',
    ALLERGY_ALREADY_ACTIVE: 'El paciente ya tiene esa alergia activa.',
    ALLERGEN_REQUIRED: 'Indique el código o el nombre del alérgeno.',
    ALLERGY_TYPE_INVALID: 'Seleccione el tipo de reacción.',
    SEVERITY_INVALID: 'Seleccione la severidad.',
    ACTOR_WITHOUT_LOCAL_REFERENCE: 'Su usuario no está habilitado en este hospital para registrar alergias.',
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
        return 'Alergias no disponibles: verificar con el paciente antes de prescribir.';
    }

    return 'No se pudo completar la operación.';
}

/** El código viene en `error.code` (API de alergias) o en `error`/`code` (guard y middlewares). */
export function codigoDeError(datos) {
    return datos?.error?.code ?? (typeof datos?.error === 'string' ? datos.error : null) ?? datos?.code ?? null;
}
