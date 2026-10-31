// Lógica pura del prototipo (sin DOM): etiquetas, validaciones previas y acciones
// permitidas. El servidor sigue siendo la autoridad; esto solo evita viajes inútiles.

export const VERDICTS = {
    approved: { label: 'Aprobado', tone: 'ok', help: 'Todas las suites obligatorias pasaron con evidencia. La versión puede promoverse.' },
    rejected: { label: 'Rechazado', tone: 'bad', help: 'Hay pruebas obligatorias en rojo. Corrija y ejecute de nuevo; no se promueve.' },
    blocked: { label: 'Bloqueado', tone: 'warn', help: 'Falta evidencia para decidir (suite ausente, vacía o sin archivo). No es un rechazo: complete la ejecución.' },
};

export const STATUS = { planned: 'Planificada', running: 'En ejecución', completed: 'Completada', aborted: 'Abortada' };

const RULES = {
    RUN_NOT_COMPLETED: 'La ejecución todavía no terminó.',
    RUN_ABORTED: 'La ejecución fue abortada.',
    SUITE_MISSING: 'No se registró resultado.',
    SUITE_EMPTY: 'La suite no ejecutó pruebas.',
    EVIDENCE_MISSING: 'Falta la evidencia (reporte o log).',
    SUITE_BELOW_THRESHOLD: 'No alcanzó el umbral de aprobación.',
    FAILURES_TOLERATED: 'Tiene fallos tolerados por la política.',
    SKIPPED_IN_REQUIRED: 'Tiene pruebas omitidas en una suite obligatoria.',
    E2E_SLOW: 'La E2E superó la duración esperada.',
};

export function findingText(finding) {
    const base = RULES[finding.rule] ?? finding.rule;
    return finding.suite ? `${finding.suite}: ${base}` : base;
}

/** Primer hallazgo que decide el veredicto (bloqueo antes que rechazo); null si fue aprobado sin bloqueos. */
export function mainReason(gate) {
    if (!gate) return null;
    const decisive = gate.findings.find((f) => f.severity === 'blocker') ?? gate.findings.find((f) => f.severity === 'rejection');
    return decisive ? findingText(decisive) : null;
}

export function severityLabel(severity) {
    return { blocker: 'Bloquea', rejection: 'Rechaza', warning: 'Aviso' }[severity] ?? severity;
}

const SEMVER = /^\d+\.\d+\.\d+(-[0-9A-Za-z.-]+)?$/;
const SHA = /^[0-9a-f]{40}$/;
const SUITE = /^[a-z0-9][a-z0-9._-]{1,62}$/;

/** Devuelve { campo: mensaje } con los errores; objeto vacío si el plan es válido. */
export function validatePlan(plan) {
    const errors = {};
    if (!SEMVER.test(plan.release_version ?? '')) errors.release_version = 'Use SemVer, por ejemplo 1.4.0 o 1.4.0-rc.1.';
    if (!SHA.test(plan.commit_sha ?? '')) errors.commit_sha = 'Pegue el SHA completo de 40 caracteres en minúsculas.';
    if (!['local', 'ci', 'staging'].includes(plan.environment)) errors.environment = 'Elija local, ci o staging. Producción no está permitida.';
    const suites = plan.suites ?? [];
    const names = suites.map((s) => s.name);
    if (suites.length === 0 || suites.length > 30) errors.suites = 'El plan necesita entre 1 y 30 suites.';
    else if (names.some((n) => !SUITE.test(n))) errors.suites = 'Nombres de suite: 2-63 caracteres a-z, 0-9, punto, guion o guion bajo.';
    else if (new Set(names).size !== names.length) errors.suites = 'Hay nombres de suite repetidos.';
    else if (!['unit', 'e2e'].every((k) => suites.some((s) => s.kind === k && s.required))) errors.suites = 'Incluya al menos una suite obligatoria unitaria y una E2E.';
    return errors;
}

export function validateResult(result) {
    const errors = {};
    const ints = ['total', 'failed', 'skipped', 'duration_ms'];
    for (const key of ints) {
        if (!Number.isInteger(result[key]) || result[key] < 0) errors[key] = 'Entero mayor o igual a 0.';
    }
    if (!errors.total && !errors.failed && !errors.skipped && result.failed + result.skipped > result.total) {
        errors.failed = 'Fallidas más omitidas no puede superar el total.';
    }
    const ev = result.evidence?.[0];
    if (!ev || !/^(?!\/)(?!.*\.\.)[A-Za-z0-9._/-]{1,200}$/.test(ev.path ?? '')) errors.evidence = 'Ruta relativa dentro del paquete de evidencias (sin "..").';
    else if (!/^[0-9a-f]{64}$/.test(ev.sha256 ?? '')) errors.evidence = 'La huella SHA-256 debe tener 64 caracteres hexadecimales.';
    else if (!Number.isInteger(ev.bytes) || ev.bytes < 1) errors.evidence = 'Indique el tamaño real del archivo en bytes (mínimo 1).';
    return errors;
}

/** Acciones visibles según estado y permisos; lo no permitido ni se muestra. */
export function allowedActions(run, can) {
    const actions = [];
    if (!can.register) return can.evaluate && run.status === 'completed' ? ['evaluate'] : [];
    if (run.status === 'planned') actions.push('start', 'abort');
    if (run.status === 'running') {
        if (run.suites.some((s) => s.result === null)) actions.push('record');
        actions.push('complete', 'abort');
    }
    if (can.evaluate && ['completed', 'aborted'].includes(run.status)) actions.push('evaluate');
    return actions;
}

/** Traduce una respuesta de error a un mensaje para la persona usuaria. */
export function errorMessage(status, body) {
    const code = body?.code ?? '';
    if (status === 0) return { title: 'Sin conexión', message: 'No se pudo contactar al servidor. Sus datos del formulario se conservan.', retry: true };
    if (status === 401) return { title: 'Sesión requerida', message: 'Elija un perfil de demostración para continuar.', retry: false };
    if (status === 403) return { title: 'Acceso denegado', message: code === 'TENANT_MISMATCH' ? 'El hospital seleccionado no coincide con su sesión.' : 'Su perfil no tiene permiso para esta acción.', retry: false };
    if (status === 404) return { title: 'No encontrada', message: 'La ejecución no existe en su hospital.', retry: false };
    if (status === 409) return { title: 'Estado no válido', message: body?.message ?? 'La ejecución cambió de estado. Recargue el detalle.', retry: false };
    if (status === 422) return { title: 'Datos inválidos', message: body?.message ?? 'Revise los campos marcados.', retry: false };
    if (status === 503) return { title: 'Servicio no disponible', message: code === 'RBAC_UNAVAILABLE' ? 'No se pudo verificar su permiso. No se realizó ningún cambio.' : 'El registro de ejecuciones no responde. No se guardó nada; intente de nuevo.', retry: true };
    return { title: 'Error inesperado', message: `Referencia: ${body?.trace_id ?? 'sin referencia'}.`, retry: true };
}

export function shortSha(sha) {
    return typeof sha === 'string' ? sha.slice(0, 12) : '';
}
