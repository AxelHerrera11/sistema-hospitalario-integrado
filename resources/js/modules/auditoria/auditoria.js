/**
 * Utilidades puras de la pantalla de auditoría (ASII-25, Gobernanza).
 *
 * Se separan del componente para verificarlas con `node`, sin navegador. La autoridad sigue en el backend
 * (GET /api/v1/audit-events con auditoria.ver): aquí solo se arma la consulta con los nombres públicos del
 * contrato (AuditFilterParser) y se decide qué mostrar. El tenant nunca viaja en la consulta: lo fija el servidor.
 */

export const FILTROS = ['usuario', 'modulo', 'accion', 'desde', 'hasta', 'resultado', 'correlacion'];

export const RESULTADOS = [
    { valor: '', etiqueta: 'Todos' },
    { valor: 'success', etiqueta: 'Éxito' },
    { valor: 'failure', etiqueta: 'Fallo' },
    { valor: 'denied', etiqueta: 'Denegado' },
];

export const POR_PAGINA = 25;

const PATRONES = {
    usuario: /^[A-Za-z0-9][A-Za-z0-9._:@-]{0,127}$/,
    modulo: /^[a-z][a-z0-9_]{0,39}$/,
    accion: /^[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)*$/,
    correlacion: /^[A-Za-z0-9][A-Za-z0-9._:-]{0,63}$/,
    desde: /^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2})?$/,
    hasta: /^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2})?$/,
};

const texto = (valor) => (typeof valor === 'string' ? valor.trim() : '');

export function filtrosVacios() {
    return Object.fromEntries(FILTROS.map((clave) => [clave, '']));
}

/** Primer filtro con formato inválido (mismas reglas que el servidor) o null. */
export function filtroInvalido(filtros) {
    for (const [clave, patron] of Object.entries(PATRONES)) {
        const valor = texto(filtros[clave]);
        if (valor !== '' && ! patron.test(valor)) {
            return clave;
        }
    }
    if (texto(filtros.resultado) !== '' && ! RESULTADOS.some((r) => r.valor === filtros.resultado)) {
        return 'resultado';
    }
    const desde = texto(filtros.desde);
    const hasta = texto(filtros.hasta);
    if (desde !== '' && hasta !== '' && desde.slice(0, 10) > hasta.slice(0, 10)) {
        return 'hasta';
    }

    return null;
}

/** Parámetros de la consulta: omite vacíos y nunca envía tenant ni hospital. */
export function parametros(filtros, pagina = 1) {
    const salida = {};
    for (const clave of FILTROS) {
        const valor = texto(filtros[clave]);
        if (valor !== '') {
            salida[clave] = valor;
        }
    }
    salida.pagina = String(Math.max(1, Math.trunc(Number(pagina)) || 1));
    salida.por_pagina = String(POR_PAGINA);

    return salida;
}

export function etiquetaResultado(valor) {
    return RESULTADOS.find((r) => r.valor === valor)?.etiqueta ?? 'Desconocido';
}

/** Fecha UTC del evento en hora local legible; si no es fecha válida, se muestra tal cual. */
export function fechaLegible(iso, zona = 'America/Guatemala') {
    const fecha = new Date(iso);
    if (Number.isNaN(fecha.getTime())) {
        return String(iso ?? '');
    }

    return new Intl.DateTimeFormat('es-GT', { dateStyle: 'short', timeStyle: 'medium', timeZone: zona }).format(fecha);
}

export function recurso(evento) {
    return evento?.resource ? `${evento.resource.type} ${evento.resource.id ?? ''}`.trim() : '—';
}

const MENSAJES = {
    AUDIT_FILTER_INVALID: 'Revise los filtros: alguno tiene un formato no permitido.',
    TENANT_REQUIRED: 'Seleccione un hospital antes de consultar la bitácora.',
    TENANT_MISMATCH: 'La consulta no corresponde al hospital de su sesión.',
    RBAC_DENIED: 'No tiene permiso para consultar la auditoría.',
    RBAC_UNAVAILABLE: 'No se pudo verificar su permiso. Intente nuevamente.',
    AUDIT_UNAVAILABLE: 'La bitácora no está disponible en este momento.',
    TENANT_CONTEXT_UNAVAILABLE: 'No se pudo resolver el hospital de la sesión.',
};

/** Mensaje seguro por código; nunca muestra el detalle del servidor. */
export function mensajeDeError(codigo, estado) {
    if (MENSAJES[codigo]) {
        return MENSAJES[codigo];
    }
    if (estado === 401) {
        return 'Inicie sesión para continuar.';
    }
    if (estado === 403) {
        return 'No tiene permiso para consultar la auditoría.';
    }

    return 'No se pudo consultar la bitácora.';
}

/** Texto de paginación a partir de `meta` del servidor. */
export function resumenPagina(meta) {
    if (! meta || ! meta.total) {
        return 'Sin eventos para estos filtros.';
    }

    return `Página ${meta.page} de ${Math.max(1, meta.total_pages)} · ${meta.total} eventos`;
}

/** Nombre legible del actor según el catálogo del hospital; si no está (sistema, otro origen), el identificador. */
export function nombreDeActor(actorId, actores) {
    const actor = (actores ?? []).find((a) => a.user_id === actorId);

    return actor ? actor.display_name : (actorId ?? '—');
}

/** Opción «Nombre — correo» para el filtro de usuario; el valor sigue siendo el user_id que espera la API. */
export function opcionesDeActor(actores) {
    return (actores ?? []).map((a) => ({ valor: a.user_id, etiqueta: a.email ? `${a.display_name} — ${a.email}` : a.display_name }));
}

const CAUSAS = {
    hash_no_coincide: 'un evento fue modificado después de registrarse',
    eslabon_faltante: 'falta un evento o la cadena está fuera de orden',
    bifurcacion: 'dos eventos dicen seguir al mismo',
};

/** Resultado de GET /audit-events/integrity para la pantalla: { tono: 'ok'|'error', titulo, mensaje }. */
export function estadoIntegridad(r) {
    if (r?.valid) {
        return { tono: 'ok', titulo: 'Cadena íntegra', mensaje: `Se verificaron ${r.checked} de ${r.total} eventos: ninguno fue alterado ni borrado.` };
    }
    const b = r?.broken ?? {};
    const donde = b.event_id ? ` (evento ${b.event_id} de este hospital)` : '';

    return {
        tono: 'error',
        titulo: 'Cadena alterada',
        mensaje: `La verificación se detuvo en el eslabón ${b.position ?? '?'}${donde}: ${CAUSAS[b.cause] ?? 'causa desconocida'}. Informe a seguridad.`,
    };
}
