// Vistas del prototipo: lista → planificar → detalle (resultados, transiciones, gate).
import { createApi, permissionsFor } from './api.js';
import { allowedActions, errorMessage, findingText, mainReason, severityLabel, shortSha, STATUS, validatePlan, validateResult, VERDICTS } from './view-model.js';

const $ = (id) => document.getElementById(id);
function el(tag, { dataset = {}, ...props } = {}, ...children) {
    const node = Object.assign(document.createElement(tag), props);
    Object.assign(node.dataset, dataset);
    children.flat().forEach((child) => child != null && node.append(child));
    return node;
}

const context = () => ({ profile: $('profile').value, failure: $('failure').value });
const api = createApi(context);
const can = () => permissionsFor(context().profile);
let lastRetry = null;

function announce(text) { $('status').textContent = text; }

function showError(response, retry) {
    const info = errorMessage(response.status, response.body);
    $('alert-title').textContent = info.title;
    $('alert-message').textContent = info.message;
    $('alert-retry').hidden = !(info.retry && retry);
    lastRetry = retry;
    $('alert').hidden = false;
}
function clearError() { $('alert').hidden = true; }

// H-06: el foco solo vuelve al inicio al navegar; tras una acción se queda en el resultado.
function show(view, moveFocus = true) {
    for (const id of ['view-list', 'view-new', 'view-detail']) $(id).hidden = id !== view;
    if (moveFocus) $('main').focus();
}

// H-02: una acción en curso bloquea sus botones; un doble clic ya no envía dos peticiones.
async function busy(buttons, work) {
    if (buttons.some((b) => b.disabled)) return null;
    const labels = buttons.map((b) => b.textContent);
    buttons.forEach((b) => { b.disabled = true; b.setAttribute('aria-busy', 'true'); });
    announce('Procesando…');
    try {
        return await work();
    } finally {
        buttons.forEach((b, i) => { b.disabled = false; b.removeAttribute('aria-busy'); b.textContent = labels[i]; });
    }
}

function verdictBadge(gate) {
    if (!gate) return el('span', { className: 'badge tone-none', textContent: 'Sin evaluar' });
    const v = VERDICTS[gate.verdict];
    return el('span', { className: `badge tone-${v.tone}`, textContent: v.label });
}

async function renderList() {
    show('view-list');
    $('new-run').hidden = !can().register;
    $('list-loading').hidden = false;
    $('list-empty').hidden = true;
    $('run-list').replaceChildren();
    const response = await api.list();
    $('list-loading').hidden = true;
    if (!response.ok) return showError(response, renderList);
    clearError();
    const runs = response.body.data;
    $('list-empty').hidden = runs.length > 0;
    for (const run of runs) {
        $('run-list').append(el('li', { className: 'card' },
            el('a', { href: `#/ejecucion/${run.id}`, textContent: `Versión ${run.release_version}` }), ' ', verdictBadge(run.gate),
            el('p', { className: 'muted', textContent: `${STATUS[run.status]} · ${run.environment} · commit ${shortSha(run.commit_sha)}` }),
            // Cambio solicitado (Semana 12): el motivo principal del veredicto, sin abrir el detalle.
            mainReason(run.gate) && el('p', { className: 'reason', textContent: `Motivo: ${mainReason(run.gate)}` }),
        ));
    }
    announce(`${runs.length} ejecuciones cargadas.`);
}

function suiteRow(suite = { name: '', kind: 'unit', required: true }) {
    const index = $('suite-rows').children.length;
    const kinds = ['syntax', 'lint', 'static_analysis', 'build', 'unit', 'integration', 'contract', 'e2e'];
    const row = el('div', { className: 'suite-row' },
        el('div', { className: 'field' }, el('label', { htmlFor: `s-name-${index}`, textContent: 'Nombre' }), el('input', { id: `s-name-${index}`, value: suite.name, className: 's-name' })),
        el('div', { className: 'field' }, el('label', { htmlFor: `s-kind-${index}`, textContent: 'Tipo' }),
            el('select', { id: `s-kind-${index}`, className: 's-kind' }, kinds.map((k) => el('option', { value: k, textContent: k, selected: k === suite.kind })))),
        el('label', {}, el('input', { type: 'checkbox', className: 's-required', checked: suite.required, style: 'width:auto;min-height:auto' }), ' Obligatoria'),
        el('button', { type: 'button', className: 'danger', textContent: 'Quitar', ariaLabel: `Quitar suite ${index + 1}`, onclick: () => row.remove() }),
    );
    $('suite-rows').append(row);
}

function renderNew() {
    if (!can().register) { location.hash = '#/'; return; }
    show('view-new');
    if ($('suite-rows').children.length === 0) {
        suiteRow({ name: 'phpunit-unit', kind: 'unit', required: true });
        suiteRow({ name: 'e2e-auth-rbac', kind: 'e2e', required: true });
    }
}

/** @param map campo → [id del control que recibe el foco, id del mensaje de error] */
function markErrors(errors, map) {
    for (const [field, [inputId, errorId]] of Object.entries(map)) {
        $(errorId).textContent = errors[field] ?? '';
        $(inputId)?.setAttribute('aria-invalid', errors[field] ? 'true' : 'false');
    }
    const first = Object.keys(map).find((f) => errors[f]);
    // H-04: el error del grupo de suites lleva el foco al primer nombre de suite.
    const target = first === 'suites' ? document.querySelector('#suite-rows .s-name') ?? $('add-suite') : first && $(map[first][0]);
    target?.focus();
}

async function submitPlan(event) {
    event.preventDefault();
    const plan = {
        release_version: $('release').value.trim(),
        commit_sha: $('sha').value.trim(),
        environment: $('environment').value,
        suites: [...$('suite-rows').children].map((r) => ({ name: r.querySelector('.s-name').value.trim(), kind: r.querySelector('.s-kind').value, required: r.querySelector('.s-required').checked })),
    };
    const errors = validatePlan(plan);
    markErrors(errors, { release_version: ['release', 'release-error'], commit_sha: ['sha', 'sha-error'], environment: ['environment', 'environment-error'], suites: [null, 'suites-error'] });
    if (Object.keys(errors).length) return announce('Revise los campos marcados.');
    const response = await busy([event.submitter ?? $('plan-form').querySelector('[type=submit]')], () => api.plan(plan));
    if (response === null) return;
    if (!response.ok) return showError(response, () => submitPlan(event));
    clearError();
    $('plan-form').reset();
    $('suite-rows').replaceChildren();
    announce('Plan guardado.');
    location.hash = `#/ejecucion/${response.body.data.id}`;
}

function confirmAction(text, needsReason) {
    return new Promise((resolve) => {
        $('confirm-text').textContent = text;
        $('confirm-reason-field').hidden = !needsReason;
        $('confirm-reason').value = '';
        $('confirm').onclose = () => {
            const reason = $('confirm-reason').value.trim();
            resolve($('confirm').returnValue === 'ok' && (!needsReason || reason !== '') ? { reason } : null);
        };
        $('confirm').showModal();
    });
}

const ACTIONS = {
    start: { label: 'Iniciar ejecución', run: (id) => api.transition(id, 'start') },
    complete: { label: 'Completar ejecución', confirm: 'Al completar ya no se aceptan más resultados. ¿Continuar?', run: (id) => api.transition(id, 'complete') },
    abort: { label: 'Abortar', danger: true, confirm: 'La ejecución quedará abortada y el gate la bloqueará.', reason: true, run: (id, r) => api.transition(id, 'abort', r) },
    evaluate: { label: 'Evaluar quality gate', primary: true, run: (id) => api.evaluate(id) },
    record: null,
};

async function renderDetail(id, focusAfter = null) {
    show('view-detail', focusAfter === null);
    const response = await api.get(id);
    if (!response.ok) return showError(response, () => renderDetail(id));
    clearError();
    const run = response.body.data;
    $('detail-title').textContent = `Ejecución de la versión ${run.release_version}`;
    $('detail-meta').replaceChildren(...[['Estado', STATUS[run.status]], ['Commit', run.commit_sha], ['Ambiente', run.environment], ['Planificó', run.created_by], ['Creada', run.created_at], ...(run.abort_reason ? [['Motivo de aborto', run.abort_reason]] : [])]
        .flatMap(([k, v]) => [el('dt', { textContent: k }), el('dd', { textContent: v })]));
    renderGate(run.gate);
    $('suite-table').tBodies[0].replaceChildren(...run.suites.map((s) => el('tr', {},
        el('td', { dataset: { label: 'Suite' }, textContent: s.name }),
        el('td', { dataset: { label: 'Tipo' }, textContent: s.kind }),
        el('td', { dataset: { label: 'Obligatoria' }, textContent: s.required ? 'Sí' : 'No' }),
        el('td', { dataset: { label: 'Resultado' }, textContent: s.result ? `${s.result.passed}/${s.result.total} aprobadas · ${s.result.failed} fallidas · ${s.result.skipped} omitidas` : 'Pendiente' }),
        el('td', { dataset: { label: 'Evidencia' }, textContent: s.result ? s.result.evidence.map((e) => `${e.path} (${e.sha256.slice(0, 8)}…)`).join(', ') || 'Sin evidencia' : '—' }),
    )));
    const actions = allowedActions(run, can());
    $('run-actions').replaceChildren(...actions.filter((a) => ACTIONS[a]).map((a) => el('button', {
        type: 'button', textContent: ACTIONS[a].label, className: ACTIONS[a].primary ? 'primary' : ACTIONS[a].danger ? 'danger' : '',
        onclick: async () => {
            const ok = ACTIONS[a].confirm ? await confirmAction(ACTIONS[a].confirm, ACTIONS[a].reason) : { reason: null };
            if (!ok) return announce('Acción cancelada.');
            const r = await busy([...$('run-actions').querySelectorAll('button')], () => ACTIONS[a].run(run.id, ok.reason));
            if (r === null) return;
            if (!r.ok) return showError(r, null);
            announce(`${ACTIONS[a].label}: listo.`);
            renderDetail(run.id, a === 'evaluate' ? 'gate-panel' : 'detail-title');
        },
    })));
    if (focusAfter) $(focusAfter)?.focus();
    $('result-form').hidden = !actions.includes('record');
    $('r-suite').replaceChildren(...run.suites.filter((s) => !s.result).map((s) => el('option', { value: s.name, textContent: s.name })));
    $('result-form').onsubmit = (e) => submitResult(e, run.id);
}

function renderGate(gate) {
    const panel = $('gate-panel');
    panel.hidden = !gate;
    if (!gate) return;
    const v = VERDICTS[gate.verdict];
    panel.className = `gate tone-${v.tone}`;
    panel.replaceChildren(
        el('h3', { textContent: `Quality gate: ${v.label}` }),
        el('p', { textContent: v.help }),
        el('p', { className: 'muted', textContent: `Política ${gate.policy_version} · evaluó ${gate.evaluated_by} · ${gate.evaluated_at}` }),
        gate.findings.length ? el('ul', {}, gate.findings.map((f) => el('li', { title: findingText(f), textContent: `${severityLabel(f.severity)} · ${f.suite ?? 'ejecución'}: ${f.detail}` }))) : el('p', { textContent: 'Sin hallazgos.' }),
    );
}

async function submitResult(event, id) {
    event.preventDefault();
    const n = (fid) => ($(fid).value === '' ? NaN : Number($(fid).value));
    const result = { suite: $('r-suite').value, total: n('r-total'), failed: n('r-failed'), skipped: n('r-skipped'), duration_ms: n('r-duration'),
        evidence: [{ kind: 'junit', path: $('r-path').value.trim(), sha256: $('r-sha').value.trim(), bytes: n('r-bytes') }] };
    const errors = validateResult(result);
    markErrors(errors, { total: ['r-total', 'r-total-error'], failed: ['r-failed', 'r-failed-error'], evidence: ['r-path', 'r-evidence-error'] });
    if (Object.keys(errors).length) return announce('Revise los campos marcados.');
    const response = await busy([$('result-form').querySelector('[type=submit]')], () => api.record(id, result));
    if (response === null) return;
    if (!response.ok) return showError(response, () => submitResult(event, id));
    $('result-form').reset();
    announce('Resultado registrado.');
    renderDetail(id);
}

function route() {
    const hash = location.hash || '#/';
    if (hash === '#/nueva') return renderNew();
    const match = hash.match(/^#\/ejecucion\/([A-Za-z0-9-]+)$/);
    return match ? renderDetail(match[1]) : renderList();
}

// Enlaces del recorrido navegable: ?perfil=observador&falla=almacen preseleccionan el contexto de demo.
const params = new URLSearchParams(location.search);
for (const [param, id] of [['perfil', 'profile'], ['falla', 'failure']]) {
    if (params.has(param) && [...$(id).options].some((o) => o.value === params.get(param))) $(id).value = params.get(param);
}

$('plan-form').addEventListener('submit', submitPlan);
$('add-suite').addEventListener('click', () => suiteRow({ name: '', kind: 'integration', required: false }));
$('alert-close').addEventListener('click', clearError);
$('alert-retry').addEventListener('click', () => { clearError(); lastRetry?.(); });
$('profile').addEventListener('change', route);
$('failure').addEventListener('change', route);
window.addEventListener('hashchange', route);
route();
