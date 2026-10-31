/**
 * Lógica pura de la pantalla de calidad de versiones (ASII-25) dentro de la aplicación.
 *
 * Reutiliza el view-model del prototipo del micro-HIS (veredictos, validaciones previas y acciones permitidas) para
 * que prototipo y aplicación no diverjan. Aquí solo cambia lo que en el prototipo dependía de la sesión de demo:
 * los permisos salen de `user.permissions` y el 401 pide iniciar sesión. El servidor sigue siendo la autoridad.
 */
import { errorMessage } from '../../../../micro-his/qa-calidad-versiones/public/prototipo/view-model.js';

export {
    allowedActions, findingText, mainReason, severityLabel, shortSha, STATUS, validatePlan, validateResult, VERDICTS,
} from '../../../../micro-his/qa-calidad-versiones/public/prototipo/view-model.js';

export const PERMISOS = { ver: 'qa.ejecuciones.ver', registrar: 'qa.ejecuciones.registrar', evaluar: 'qa.gates.evaluar' };

export const TIPOS_SUITE = ['syntax', 'lint', 'static_analysis', 'build', 'unit', 'integration', 'contract', 'e2e'];

/** Mismo formato que `can` del prototipo, a partir de los permisos efectivos de la sesión. */
export function permisosQa(user) {
    const lista = Array.isArray(user?.permissions) ? user.permissions : [];

    return { register: lista.includes(PERMISOS.registrar), evaluate: lista.includes(PERMISOS.evaluar) };
}

export function planVacio() {
    return {
        release_version: '',
        commit_sha: '',
        environment: 'ci',
        suites: [{ name: 'phpunit-unit', kind: 'unit', required: true }, { name: 'e2e-auth-rbac', kind: 'e2e', required: true }],
    };
}

/** Cuerpo del POST /qa/runs: recorta textos y normaliza el SHA a minúsculas, sin campos extra. */
export function cuerpoPlan(plan) {
    return {
        release_version: String(plan.release_version ?? '').trim(),
        commit_sha: String(plan.commit_sha ?? '').trim().toLowerCase(),
        environment: plan.environment,
        suites: (plan.suites ?? []).map((s) => ({ name: String(s.name ?? '').trim(), kind: s.kind, required: Boolean(s.required) })),
    };
}

/** Cuerpo del POST /qa/runs/{id}/results desde el formulario (números como enteros). */
export function cuerpoResultado(suite, campos) {
    const entero = (valor) => (String(valor ?? '').trim() === '' ? NaN : Number(valor));

    return {
        suite,
        total: entero(campos.total),
        failed: entero(campos.failed),
        skipped: entero(campos.skipped),
        duration_ms: entero(campos.duration_ms),
        evidence: [{ kind: 'junit', path: String(campos.path ?? '').trim(), sha256: String(campos.sha256 ?? '').trim().toLowerCase(), bytes: entero(campos.bytes) }],
    };
}

/** Mensaje de error de la API; el 401 de la aplicación pide iniciar sesión (no un perfil de demo). */
export function errorQa(status, body) {
    if (status === 401) {
        return { title: 'Sesión requerida', message: 'Inicie sesión para continuar.', retry: false };
    }

    return errorMessage(status, body);
}
